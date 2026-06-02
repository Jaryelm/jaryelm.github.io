<?php

if (!function_exists('medidata_postulaciones_pdo')) {
    function medidata_postulaciones_pdo(): ?PDO
    {
        global $connect_postulaciones;
        return ($connect_postulaciones instanceof PDO) ? $connect_postulaciones : null;
    }
}

if (!function_exists('medidata_postulaciones_last_error')) {
    function medidata_postulaciones_last_error(): ?string
    {
        $msg = $GLOBALS['__MEDIDATA_POSTULACIONES_CONN_ERROR__'] ?? null;
        return is_string($msg) && $msg !== '' ? $msg : null;
    }
}

if (!function_exists('medidata_postulaciones_disponible')) {
    function medidata_postulaciones_disponible(): bool
    {
        return medidata_postulaciones_pdo() !== null;
    }
}

if (!function_exists('medidata_postulaciones_count')) {
    function medidata_postulaciones_count(): int
    {
        $pdo = medidata_postulaciones_pdo();
        if (!$pdo) {
            return 0;
        }
        try {
            return (int) $pdo->query('SELECT COUNT(*) FROM aplica')->fetchColumn();
        } catch (Throwable $e) {
            error_log('medidata_postulaciones_count: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('medidata_postulaciones_resolver_ruta_cv')) {
    function medidata_postulaciones_resolver_ruta_cv($cvRaw): ?string
    {
        if ($cvRaw === null || $cvRaw === '') {
            return null;
        }
        if (is_resource($cvRaw)) {
            $cvRaw = stream_get_contents($cvRaw);
        }
        $path = trim((string) $cvRaw);
        if ($path === '') {
            return null;
        }
        if ($path[0] !== '/' && preg_match('/^[0-9a-f]+$/i', $path) && strlen($path) % 2 === 0) {
            $decoded = @hex2bin($path);
            if ($decoded !== false) {
                $path = $decoded;
            }
        }
        $path = str_replace('\\', '/', $path);
        $candidates = [];
        if ($path[0] === '/') {
            $candidates[] = $path;
        }
        $fileName = basename($path);
        $uploadDirs = [
            '/home/medicasa/MedicasaDATAUpdate2/uploads/Postulantes_CV/',
            '/home4/medic9ue/uploads/',
        ];
        foreach ($uploadDirs as $dir) {
            $candidates[] = rtrim($dir, '/') . '/' . $fileName;
        }
        foreach ($candidates as $candidate) {
            if (is_readable($candidate)) {
                return $candidate;
            }
        }
        return null;
    }
}

if (!function_exists('medidata_postulaciones_cv_mime_type')) {
    function medidata_postulaciones_cv_mime_type(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return match ($ext) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
    }
}

if (!function_exists('medidata_postulaciones_tabla_aplica_lista')) {
    /**
     * @return array<int, object>
     */
    function medidata_postulaciones_tabla_aplica_lista(): array
    {
        $pdo = medidata_postulaciones_pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $stmt = $pdo->query('SELECT * FROM aplica ORDER BY fecha_registro DESC');
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('medidata_postulaciones_tabla_aplica_lista: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('medidata_postulaciones_datatables')) {
    /**
     * Respuesta server-side para DataTables (tabla aplica).
     *
     * @param array<string, mixed> $request $_GET de DataTables
     * @return array<string, mixed>
     */
    function medidata_postulaciones_datatables(array $request): array
    {
        $draw = (int) ($request['draw'] ?? 1);
        $empty = static function () use ($draw): array {
            return [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Base de datos de postulaciones no disponible.',
            ];
        };

        $pdo = medidata_postulaciones_pdo();
        if (!$pdo) {
            return $empty();
        }

        try {
            if (!function_exists('medidata_rrhh_aplica_bridge_ensure_schema')) {
                require_once __DIR__ . '/rrhh_aplica_bridge.php';
            }
            medidata_rrhh_aplica_bridge_ensure_schema();

            $start = max(0, (int) ($request['start'] ?? 0));
            $lengthRaw = (int) ($request['length'] ?? 10);
            $length = ($lengthRaw <= 0) ? 100 : min($lengthRaw, 100);
            $searchValue = trim((string) ($request['search']['value'] ?? ''));

            $recordsTotal = (int) $pdo->query('SELECT COUNT(*) FROM aplica')->fetchColumn();

            $where = '1=1';
            $params = [];
            if ($searchValue !== '') {
                $where .= ' AND (
                    numero_id LIKE :search1 OR
                    nombre_completo LIKE :search2 OR
                    puesto_aspirado LIKE :search3 OR
                    whatsapp LIKE :search4 OR
                    correo LIKE :search5
                )';
                $like = '%' . $searchValue . '%';
                $params = [
                    ':search1' => $like,
                    ':search2' => $like,
                    ':search3' => $like,
                    ':search4' => $like,
                    ':search5' => $like,
                ];
            }

            $countSql = "SELECT COUNT(*) FROM aplica WHERE {$where}";
            $countStmt = $pdo->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $recordsFiltered = (int) $countStmt->fetchColumn();

            $orderColumn = (int) ($request['order'][0]['column'] ?? 7);
            $orderDir = strtoupper((string) ($request['order'][0]['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
            $columns = [
                0 => 'numero_id',
                1 => 'nombre_completo',
                2 => 'puesto_aspirado',
                5 => 'whatsapp',
                6 => 'correo',
                7 => 'fecha_registro',
            ];
            $orderBy = $columns[$orderColumn] ?? 'fecha_registro';

            $sql = "SELECT id, numero_id, nombre_completo, puesto_aspirado, whatsapp, correo,
                           fecha_registro, CONVERT(cv USING utf8) AS cv,
                           COALESCE(estado_rrhh, IF(seleccionado = 1, 'Incorporado', 'Pendiente')) AS estado_rrhh,
                           COALESCE(id_candidate_rrhh, 0) AS id_candidate_rrhh,
                           motivo_descarte
                    FROM aplica
                    WHERE {$where}
                    ORDER BY {$orderBy} {$orderDir}, id DESC
                    LIMIT :start, :length";

            $stmt = $pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
            $stmt->bindValue(':start', $start, PDO::PARAM_INT);
            $stmt->bindValue(':length', $length, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $data = [];
            foreach ($rows as $row) {
                $cvRaw = $row['cv'] ?? '';
                $tieneCv = $cvRaw !== '' && $cvRaw !== null;
                $cvDisponible = $tieneCv && medidata_postulaciones_resolver_ruta_cv($cvRaw) !== null;
                $enriched = medidata_postulaciones_enriquecer_fila($row);
                $data[] = [
                    'id' => (int) $row['id'],
                    'numero_id' => $row['numero_id'] ?? '',
                    'nombre_completo' => $row['nombre_completo'] ?? '',
                    'puesto_aspirado' => $row['puesto_aspirado'] ?? '',
                    'whatsapp' => $row['whatsapp'] ?? '',
                    'correo' => $row['correo'] ?? '',
                    'fecha_registro' => $row['fecha_registro'] ?? '',
                    'tiene_cv' => $tieneCv,
                    'cv_disponible' => $cvDisponible,
                    'estado_rrhh' => $enriched['estado_rrhh'] ?? 'Pendiente',
                    'vacante_sugerida' => $enriched['vacante_sugerida'] ?? '',
                    'vacante_sugerida_id' => $enriched['vacante_sugerida_id'],
                    'match_type' => $enriched['match_type'] ?? 'none',
                    'vacantes_match' => $enriched['vacantes_match'] ?? [],
                    'id_candidate_rrhh' => (int) ($enriched['id_candidate_rrhh'] ?? 0),
                    'motivo_descarte' => $row['motivo_descarte'] ?? '',
                ];
            }

            return [
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ];
        } catch (Throwable $e) {
            error_log('medidata_postulaciones_datatables: ' . $e->getMessage());
            return [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Error al cargar postulantes.',
            ];
        }
    }
}
