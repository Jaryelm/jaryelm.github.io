<?php

require_once __DIR__ . '/postulaciones_guard.php';
require_once __DIR__ . '/rrhh_guard.php';

if (!function_exists('medidata_rrhh_web_puestos_catalogo')) {
    /**
     * Lista oficial del formulario web (puesto al que aspiras).
     *
     * @return array<int, string>
     */
    function medidata_rrhh_web_puestos_catalogo(): array
    {
        return [
            'Auxiliar de Enfermería II',
            'Técnico Radiotecnología',
            'Instrumentista',
            'Paramédico',
            'Técnico en Laboratorio',
            'Servicio al Cliente',
            'Auxiliar de Farmacia',
            'Secretariado - Transcripción',
            'Auxiliar de Almacén',
            'Auxiliar de Limpieza',
            'Auxiliar Contable',
            'Mantenimiento',
            'Conserje',
            'Conductor',
            'Otros Administrativos',
            'Otros Profesionales Médicos',
            'Practicantes',
        ];
    }
}

if (!function_exists('medidata_rrhh_normalize_label')) {
    function medidata_rrhh_normalize_label(string $text): string
    {
        $text = trim(mb_strtolower($text, 'UTF-8'));
        $text = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $text
        );
        $text = preg_replace('/[^a-z0-9\s\-]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return trim($text);
    }
}

if (!function_exists('medidata_rrhh_web_position_aliases')) {
    /**
     * Etiqueta web → posibles nombres de posición en catálogo RRHH.
     *
     * @return array<string, array<int, string>>
     */
    function medidata_rrhh_web_position_aliases(): array
    {
        return [
            'auxiliar de enfermeria ii' => ['Auxiliar de Enfermería II', 'Auxiliar de Enfermeria II'],
            'tecnico radiotecnologia' => ['Técnico Radiotecnología', 'Técnico Radiotecnologia', 'Tecnico Radiotecnologia'],
            'instrumentista' => ['Instrumentista'],
            'paramedico' => ['Paramédico', 'Paramedico'],
            'tecnico en laboratorio' => ['Técnico en Laboratorio', 'Tecnico en Laboratorio'],
            'servicio al cliente' => ['Servicio al Cliente'],
            'auxiliar de farmacia' => ['Auxiliar de Farmacia'],
            'secretariado - transcripcion' => ['Secretariado - Transcripción', 'Secretariado - Transcripcion'],
            'auxiliar de almacen' => ['Auxiliar de Almacén', 'Auxiliar de Almacen'],
            'auxiliar de limpieza' => ['Auxiliar de Limpieza'],
            'auxiliar contable' => ['Auxiliar Contable'],
            'mantenimiento' => ['Mantenimiento'],
            'conserje' => ['Conserje'],
            'conductor' => ['Conductor'],
            'otros administrativos' => ['Otros Administrativos'],
            'otros profesionales medicos' => ['Otros Profesionales Médicos', 'Otros Profesionales Medicos'],
            'practicantes' => ['Practicantes', 'Practicante'],
        ];
    }
}

if (!function_exists('medidata_rrhh_aplica_bridge_ensure_schema')) {
    function medidata_rrhh_aplica_bridge_ensure_schema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $pdoPost = medidata_postulaciones_pdo();
        if ($pdoPost) {
            try {
                $cols = $pdoPost->query('SHOW COLUMNS FROM aplica')->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('estado_rrhh', $cols, true)) {
                    $pdoPost->exec(
                        "ALTER TABLE aplica
                         ADD COLUMN estado_rrhh ENUM('Pendiente','Incorporado','Descartado') NOT NULL DEFAULT 'Pendiente' AFTER seleccionado"
                    );
                }
                if (!in_array('id_candidate_rrhh', $cols, true)) {
                    $pdoPost->exec('ALTER TABLE aplica ADD COLUMN id_candidate_rrhh INT NULL DEFAULT NULL AFTER estado_rrhh');
                }
                if (!in_array('motivo_descarte', $cols, true)) {
                    $pdoPost->exec('ALTER TABLE aplica ADD COLUMN motivo_descarte VARCHAR(500) NULL DEFAULT NULL AFTER id_candidate_rrhh');
                }
                if (!in_array('fecha_gestion_rrhh', $cols, true)) {
                    $pdoPost->exec('ALTER TABLE aplica ADD COLUMN fecha_gestion_rrhh DATETIME NULL DEFAULT NULL AFTER motivo_descarte');
                }
                $pdoPost->exec(
                    "UPDATE aplica SET estado_rrhh = 'Incorporado'
                     WHERE seleccionado = 1 AND estado_rrhh = 'Pendiente'"
                );
            } catch (Throwable $e) {
                error_log('medidata_rrhh_aplica_bridge_ensure_schema postulaciones: ' . $e->getMessage());
            }
        }

        $pdoRrhh = medidata_rrhh_pdo();
        if ($pdoRrhh) {
            try {
                $cols = $pdoRrhh->query('SHOW COLUMNS FROM candidates')->fetchAll(PDO::FETCH_COLUMN);
                if (!in_array('id_aplica', $cols, true)) {
                    $pdoRrhh->exec('ALTER TABLE candidates ADD COLUMN id_aplica INT NULL DEFAULT NULL AFTER id_vacant_position');
                }
            } catch (Throwable $e) {
                error_log('medidata_rrhh_aplica_bridge_ensure_schema rrhh: ' . $e->getMessage());
            }
        }
    }
}

if (!function_exists('medidata_rrhh_estados_candidato')) {
    /** @return array<int, string> */
    function medidata_rrhh_estados_candidato(): array
    {
        return [
            'En Espera',
            'Formulario Empleados',
            'Entrevista',
            'Agendado',
            'Entrevistado',
            'Pruebas Psicometricas',
            'Descartado',
            'Llenando Expediente',
            'Contratado',
        ];
    }
}

if (!function_exists('medidata_rrhh_match_aplica_a_vacante')) {
    /**
     * Sugiere vacante(s) abiertas según puesto_aspirado del sitio web.
     *
     * @return array{
     *   web_puesto: string,
     *   match_type: string,
     *   position_names: array<int, string>,
     *   vacantes: array<int, array<string, mixed>>,
     *   suggested_vacante_id: int|null,
     *   suggested_label: string
     * }
     */
    function medidata_rrhh_match_aplica_a_vacante(string $puestoAspirado): array
    {
        $result = [
            'web_puesto' => $puestoAspirado,
            'match_type' => 'none',
            'position_names' => [],
            'vacantes' => [],
            'suggested_vacante_id' => null,
            'suggested_label' => 'Sin vacante abierta',
        ];

        $pdo = medidata_rrhh_pdo();
        if (!$pdo || trim($puestoAspirado) === '') {
            return $result;
        }

        $normalized = medidata_rrhh_normalize_label($puestoAspirado);
        $aliases = medidata_rrhh_web_position_aliases();
        $positionNames = [];

        if (isset($aliases[$normalized])) {
            $positionNames = $aliases[$normalized];
            $result['match_type'] = 'alias';
        } else {
            foreach (medidata_rrhh_web_puestos_catalogo() as $webLabel) {
                if (medidata_rrhh_normalize_label($webLabel) === $normalized) {
                    $key = medidata_rrhh_normalize_label($webLabel);
                    $positionNames = $aliases[$key] ?? [$webLabel];
                    $result['match_type'] = 'exact';
                    break;
                }
            }
        }

        if (!$positionNames) {
            $positionNames = [trim($puestoAspirado)];
            $result['match_type'] = 'fuzzy';
        }

        $result['position_names'] = $positionNames;

        try {
            $placeholders = implode(',', array_fill(0, count($positionNames), '?'));
            $sql = "SELECT v.id, v.vacant_name, v.priority, v.end_date, pt.name AS position_name
                    FROM vacantes_trabajo v
                    INNER JOIN puestos_trabajo pt ON v.id_position = pt.id
                    WHERE v.deleted = 0
                      AND v.status = 'Abierta'
                      AND v.end_date >= CURDATE()
                      AND pt.name IN ({$placeholders})
                    ORDER BY
                        FIELD(v.priority, 'Urgente', 'Alta', 'Media', 'Baja'),
                        v.end_date ASC,
                        v.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($positionNames);
            $vacantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$vacantes && $result['match_type'] === 'fuzzy') {
                $like = '%' . str_replace(' ', '%', $normalized) . '%';
                $sqlLike = "SELECT v.id, v.vacant_name, v.priority, v.end_date, pt.name AS position_name
                            FROM vacantes_trabajo v
                            INNER JOIN puestos_trabajo pt ON v.id_position = pt.id
                            WHERE v.deleted = 0
                              AND v.status = 'Abierta'
                              AND v.end_date >= CURDATE()
                              AND LOWER(REPLACE(pt.name, 'í', 'i')) LIKE ?
                            ORDER BY v.end_date ASC
                            LIMIT 5";
                $stmtLike = $pdo->prepare($sqlLike);
                $stmtLike->execute([$like]);
                $vacantes = $stmtLike->fetchAll(PDO::FETCH_ASSOC);
                if ($vacantes) {
                    $result['match_type'] = 'fuzzy';
                }
            }

            $result['vacantes'] = array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'vacant_name' => (string) $row['vacant_name'],
                    'position_name' => (string) $row['position_name'],
                    'priority' => (string) ($row['priority'] ?? ''),
                    'end_date' => (string) ($row['end_date'] ?? ''),
                ];
            }, $vacantes);

            if (count($vacantes) === 1) {
                $result['suggested_vacante_id'] = (int) $vacantes[0]['id'];
                $result['suggested_label'] = $vacantes[0]['vacant_name'] . ' (' . $vacantes[0]['position_name'] . ')';
            } elseif (count($vacantes) > 1) {
                $result['match_type'] = 'ambiguous';
                $result['suggested_label'] = count($vacantes) . ' vacantes abiertas — seleccione una';
            }
        } catch (Throwable $e) {
            error_log('medidata_rrhh_match_aplica_a_vacante: ' . $e->getMessage());
        }

        return $result;
    }
}

if (!function_exists('medidata_rrhh_fetch_vacantes_abiertas')) {
    /** @return array<int, array<string, mixed>> */
    function medidata_rrhh_fetch_vacantes_abiertas(): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $sql = "SELECT v.id, v.vacant_name, pt.name AS position_name, v.priority, v.end_date
                    FROM vacantes_trabajo v
                    INNER JOIN puestos_trabajo pt ON v.id_position = pt.id
                    WHERE v.deleted = 0 AND v.status = 'Abierta' AND v.end_date >= CURDATE()
                    ORDER BY pt.name ASC, v.vacant_name ASC";
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('medidata_rrhh_fetch_vacantes_abiertas: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('medidata_rrhh_normalize_dni')) {
    function medidata_rrhh_normalize_dni(string $dni): string
    {
        $dni = trim($dni);
        $clean = preg_replace('/\D+/', '', $dni);
        return ($clean !== null && $clean !== '') ? $clean : $dni;
    }
}

if (!function_exists('medidata_postulaciones_fetch_aplica')) {
    function medidata_postulaciones_fetch_aplica(int $id): ?array
    {
        $pdo = medidata_postulaciones_pdo();
        if (!$pdo || $id <= 0) {
            return null;
        }
        medidata_rrhh_aplica_bridge_ensure_schema();
        try {
            $stmt = $pdo->prepare('SELECT * FROM aplica WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            error_log('medidata_postulaciones_fetch_aplica: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('medidata_rrhh_incorporar_aplica')) {
    /**
     * @return array{success: bool, message: string, candidate_id?: int}
     */
    function medidata_rrhh_incorporar_aplica(int $idAplica, int $idVacante, string $usuario): array
    {
        medidata_rrhh_aplica_bridge_ensure_schema();

        $pdoPost = medidata_postulaciones_pdo();
        $pdoRrhh = medidata_rrhh_pdo();
        if (!$pdoPost || !$pdoRrhh) {
            return ['success' => false, 'message' => 'Conexión a base de datos no disponible.'];
        }

        $aplica = medidata_postulaciones_fetch_aplica($idAplica);
        if (!$aplica) {
            return ['success' => false, 'message' => 'Postulación web no encontrada.'];
        }

        $estado = $aplica['estado_rrhh'] ?? 'Pendiente';
        if ($estado === 'Incorporado') {
            return ['success' => false, 'message' => 'Esta postulación ya fue incorporada al proceso.'];
        }
        if ($estado === 'Descartado') {
            return ['success' => false, 'message' => 'Esta postulación está descartada. Restaure desde descarte antes de incorporar.'];
        }

        if ($idVacante <= 0) {
            return ['success' => false, 'message' => 'Debe seleccionar una vacante válida.'];
        }

        try {
            $stmtVac = $pdoRrhh->prepare(
                "SELECT v.id FROM vacantes_trabajo v
                 WHERE v.id = ? AND v.deleted = 0 AND v.status = 'Abierta' AND v.end_date >= CURDATE()
                 LIMIT 1"
            );
            $stmtVac->execute([$idVacante]);
            if (!$stmtVac->fetchColumn()) {
                return ['success' => false, 'message' => 'La vacante seleccionada no está abierta o no existe.'];
            }

            $dni = medidata_rrhh_normalize_dni((string) ($aplica['numero_id'] ?? ''));
            if ($dni === '') {
                return ['success' => false, 'message' => 'El postulante no tiene DNI válido.'];
            }

            $stmtDup = $pdoRrhh->prepare(
                'SELECT id FROM candidates WHERE id_vacant_position = ? AND dni = ? AND deleted = 0 LIMIT 1'
            );
            $stmtDup->execute([$idVacante, $dni]);
            $existingId = (int) $stmtDup->fetchColumn();
            if ($existingId > 0) {
                return [
                    'success' => false,
                    'message' => 'Ya existe un candidato con ese DNI en la vacante seleccionada (ID ' . $existingId . ').',
                ];
            }

            $fullname = trim((string) ($aplica['nombre_completo'] ?? ''));
            $email = trim((string) ($aplica['correo'] ?? ''));
            $phone = trim((string) ($aplica['whatsapp'] ?? ''));
            if ($fullname === '' || $email === '' || $phone === '') {
                return ['success' => false, 'message' => 'Faltan datos obligatorios en la postulación web.'];
            }

            $pdoRrhh->beginTransaction();
            $pdoPost->beginTransaction();

            $sqlIns = 'INSERT INTO candidates (
                id_vacant_position, id_aplica, fullname, dni, phonenumber, email, direction,
                referral_source, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmtIns = $pdoRrhh->prepare($sqlIns);
            $stmtIns->execute([
                $idVacante,
                $idAplica,
                $fullname,
                $dni,
                $phone,
                $email,
                'Pendiente de completar',
                'Sitio Web',
                'En Espera',
                $usuario,
            ]);
            $candidateId = (int) $pdoRrhh->lastInsertId();

            $stmtUpd = $pdoPost->prepare(
                "UPDATE aplica SET
                    estado_rrhh = 'Incorporado',
                    seleccionado = 1,
                    id_candidate_rrhh = ?,
                    fecha_gestion_rrhh = NOW(),
                    motivo_descarte = NULL
                 WHERE id = ?"
            );
            $stmtUpd->execute([$candidateId, $idAplica]);

            $pdoRrhh->commit();
            $pdoPost->commit();

            return [
                'success' => true,
                'message' => 'Candidato incorporado al proceso correctamente.',
                'candidate_id' => $candidateId,
            ];
        } catch (Throwable $e) {
            if ($pdoRrhh->inTransaction()) {
                $pdoRrhh->rollBack();
            }
            if ($pdoPost->inTransaction()) {
                $pdoPost->rollBack();
            }
            error_log('medidata_rrhh_incorporar_aplica: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al incorporar: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('medidata_rrhh_descartar_aplica')) {
    /** @return array{success: bool, message: string} */
    function medidata_rrhh_descartar_aplica(int $idAplica, string $usuario, string $motivo = ''): array
    {
        medidata_rrhh_aplica_bridge_ensure_schema();
        unset($usuario);

        $pdoPost = medidata_postulaciones_pdo();
        if (!$pdoPost) {
            return ['success' => false, 'message' => 'Conexión a postulaciones no disponible.'];
        }

        $aplica = medidata_postulaciones_fetch_aplica($idAplica);
        if (!$aplica) {
            return ['success' => false, 'message' => 'Postulación no encontrada.'];
        }
        if (($aplica['estado_rrhh'] ?? '') === 'Incorporado') {
            return ['success' => false, 'message' => 'No puede descartar una postulación ya incorporada. Descarte al candidato en el proceso interno.'];
        }

        try {
            $stmt = $pdoPost->prepare(
                "UPDATE aplica SET
                    estado_rrhh = 'Descartado',
                    seleccionado = 0,
                    motivo_descarte = ?,
                    fecha_gestion_rrhh = NOW()
                 WHERE id = ?"
            );
            $motivo = trim($motivo) !== '' ? trim($motivo) : null;
            $stmt->execute([$motivo, $idAplica]);
            return ['success' => true, 'message' => 'Postulación descartada.'];
        } catch (Throwable $e) {
            error_log('medidata_rrhh_descartar_aplica: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al descartar.'];
        }
    }
}

if (!function_exists('medidata_rrhh_reasignar_aplica')) {
    /**
     * Reasigna vacante: si pendiente, incorpora; si incorporado, mueve candidato.
     *
     * @return array{success: bool, message: string, candidate_id?: int}
     */
    function medidata_rrhh_reasignar_aplica(int $idAplica, int $idVacante, string $usuario): array
    {
        medidata_rrhh_aplica_bridge_ensure_schema();

        $aplica = medidata_postulaciones_fetch_aplica($idAplica);
        if (!$aplica) {
            return ['success' => false, 'message' => 'Postulación no encontrada.'];
        }

        $estado = $aplica['estado_rrhh'] ?? 'Pendiente';
        if ($estado === 'Pendiente') {
            return medidata_rrhh_incorporar_aplica($idAplica, $idVacante, $usuario);
        }
        if ($estado === 'Descartado') {
            return ['success' => false, 'message' => 'No puede reasignar una postulación descartada.'];
        }

        $candidateId = (int) ($aplica['id_candidate_rrhh'] ?? 0);
        if ($candidateId <= 0) {
            return medidata_rrhh_incorporar_aplica($idAplica, $idVacante, $usuario);
        }

        $pdoRrhh = medidata_rrhh_pdo();
        if (!$pdoRrhh) {
            return ['success' => false, 'message' => 'Base RRHH no disponible.'];
        }

        try {
            $dni = medidata_rrhh_normalize_dni((string) ($aplica['numero_id'] ?? ''));
            $stmtDup = $pdoRrhh->prepare(
                'SELECT id FROM candidates WHERE id_vacant_position = ? AND dni = ? AND deleted = 0 AND id <> ? LIMIT 1'
            );
            $stmtDup->execute([$idVacante, $dni, $candidateId]);
            if ($stmtDup->fetchColumn()) {
                return ['success' => false, 'message' => 'Ya existe otro candidato con ese DNI en la vacante destino.'];
            }

            $stmt = $pdoRrhh->prepare(
                'UPDATE candidates SET id_vacant_position = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND deleted = 0'
            );
            $stmt->execute([$idVacante, $usuario, $candidateId]);

            return [
                'success' => true,
                'message' => 'Vacante reasignada correctamente.',
                'candidate_id' => $candidateId,
            ];
        } catch (Throwable $e) {
            error_log('medidata_rrhh_reasignar_aplica: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al reasignar vacante.'];
        }
    }
}

if (!function_exists('medidata_rrhh_cambiar_estado_candidato')) {
    /** @return array{success: bool, message: string} */
    function medidata_rrhh_cambiar_estado_candidato(int $candidateId, string $nuevoEstado, string $usuario, string $observaciones = ''): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return ['success' => false, 'message' => 'Datos inválidos o RRHH no disponible.'];
        }

        $permitidos = medidata_rrhh_estados_candidato();
        if (!in_array($nuevoEstado, $permitidos, true)) {
            return ['success' => false, 'message' => 'Estado no válido.'];
        }

        try {
            $sql = 'UPDATE candidates SET status = ?, updated_by = ?, updated_at = NOW()';
            $params = [$nuevoEstado, $usuario];
            if (trim($observaciones) !== '') {
                $sql .= ', rrhh_observations = ?';
                $params[] = trim($observaciones);
            }
            $sql .= ' WHERE id = ? AND deleted = 0';
            $params[] = $candidateId;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Candidato no encontrado.'];
            }

            if ($nuevoEstado === 'Descartado' && trim($observaciones) === '') {
                // observaciones opcionales
            }

            return ['success' => true, 'message' => 'Estado actualizado a «' . $nuevoEstado . '».'];
        } catch (Throwable $e) {
            error_log('medidata_rrhh_cambiar_estado_candidato: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar estado.'];
        }
    }
}

if (!function_exists('medidata_postulaciones_enriquecer_fila')) {
    /** @param array<string, mixed> $row */
    function medidata_postulaciones_enriquecer_fila(array $row): array
    {
        $puesto = (string) ($row['puesto_aspirado'] ?? '');
        $match = medidata_rrhh_match_aplica_a_vacante($puesto);
        $estado = $row['estado_rrhh'] ?? 'Pendiente';
        if ($estado === '' || $estado === null) {
            $estado = ((int) ($row['seleccionado'] ?? 0) === 1) ? 'Incorporado' : 'Pendiente';
        }

        $row['estado_rrhh'] = $estado;
        $row['vacante_sugerida'] = $match['suggested_label'];
        $row['vacante_sugerida_id'] = $match['suggested_vacante_id'];
        $row['match_type'] = $match['match_type'];
        $row['vacantes_match'] = $match['vacantes'];
        $row['id_candidate_rrhh'] = isset($row['id_candidate_rrhh']) ? (int) $row['id_candidate_rrhh'] : 0;

        return $row;
    }
}
