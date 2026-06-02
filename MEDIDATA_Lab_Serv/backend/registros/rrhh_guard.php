<?php

if (!function_exists('medidata_rrhh_pdo')) {
    /**
     * Devuelve la conexión PDO a medic9ue_medi_rrhh_interviews o null.
     */
    function medidata_rrhh_pdo(): ?PDO
    {
        global $connect_rrhh;
        return ($connect_rrhh instanceof PDO) ? $connect_rrhh : null;
    }
}

if (!function_exists('medidata_rrhh_last_error')) {
    function medidata_rrhh_last_error(): ?string
    {
        $msg = $GLOBALS['__MEDIDATA_RRHH_CONN_ERROR__'] ?? null;
        return is_string($msg) && $msg !== '' ? $msg : null;
    }
}

if (!function_exists('medidata_rrhh_disponible')) {
    function medidata_rrhh_disponible(): bool
    {
        return medidata_rrhh_pdo() !== null;
    }
}

if (!function_exists('medidata_rrhh_json_fail')) {
    function medidata_rrhh_json_fail(int $code = 503): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($code);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Base de datos de Recursos Humanos no disponible. Verifique medic9ue_medi_rrhh_interviews.',
        ]);
        exit;
    }
}

if (!function_exists('medidata_rrhh_json_require')) {
    function medidata_rrhh_json_require(): PDO
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            medidata_rrhh_json_fail();
        }
        return $pdo;
    }
}

if (!function_exists('medidata_rrhh_vacantes_filtro')) {
    function medidata_rrhh_vacantes_filtro(): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return [];
        }
        try {
            $sql = "SELECT v.id, v.vacant_name AS name
                    FROM vacantes_trabajo v
                    WHERE v.deleted = 0
                    ORDER BY v.vacant_name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('medidata_rrhh_vacantes_filtro: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('medidata_rrhh_fetch_postulantes')) {
    /**
     * @param string $statusCondition Condición SQL sobre p.status (solo valores fijos del código).
     */
    function medidata_rrhh_fetch_postulantes(string $statusCondition, int $idVacante = 0): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return [];
        }

        try {
            $query = "SELECT p.*, v.vacant_name AS vacancy_name
                      FROM postulantes p
                      LEFT JOIN vacantes_trabajo v ON p.id_vacant_position = v.id
                      LEFT JOIN puestos_trabajo pt ON v.id_position = pt.id
                      WHERE p.deleted = 0 AND ({$statusCondition})";

            if ($idVacante > 0) {
                $query .= ' AND p.id_vacant_position = :id_vacante';
            }

            $query .= ' ORDER BY p.id DESC';

            $stmt = $pdo->prepare($query);
            if ($idVacante > 0) {
                $stmt->bindValue(':id_vacante', $idVacante, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Throwable $e) {
            error_log('medidata_rrhh_fetch_postulantes: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('medidata_rrhh_fetch_eventos_calendario')) {
    function medidata_rrhh_fetch_eventos_calendario(): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return [];
        }

        $events = [];

        try {
            $stmtInterviews = $pdo->prepare("
                SELECT
                    i.id,
                    CONCAT('Entrevista: ', p.fullname) AS title,
                    CONCAT(i.date_interview, ' ', i.time_interview) AS start,
                    DATE_ADD(CONCAT(i.date_interview, ' ', i.time_interview), INTERVAL 1 HOUR) AS end,
                    CASE
                        WHEN i.status = 'Programada' THEN '#035c67'
                        WHEN i.status = 'En Proceso' THEN '#06adbf'
                        WHEN i.status = 'Terminada' THEN '#81D43A'
                        ELSE '#8D8D8D'
                    END AS color,
                    p.fullname AS candidate_name,
                    p.dni AS candidate_dni,
                    p.email AS candidate_email,
                    p.phonenumber AS candidate_phone,
                    i.status AS interview_status,
                    pt.name AS position_name,
                    'interview' AS type
                FROM interviews i
                INNER JOIN postulantes p ON i.id_candidate = p.id
                LEFT JOIN vacantes_trabajo v ON p.id_vacant_position = v.id
                LEFT JOIN puestos_trabajo pt ON v.id_position = pt.id
                WHERE i.deleted = 0
            ");
            $stmtInterviews->execute();
            $events = array_merge($events, $stmtInterviews->fetchAll(PDO::FETCH_ASSOC));

            $stmtVacantes = $pdo->prepare("
                SELECT
                    v.id,
                    CONCAT('Cierre Vacante: ', pt.name) AS title,
                    v.end_date AS start,
                    v.end_date AS end,
                    '#FC3B56' AS color,
                    pt.name AS position_name,
                    v.benefits,
                    'vacancy_end' AS type
                FROM vacantes_trabajo v
                JOIN puestos_trabajo pt ON v.id_position = pt.id
                WHERE v.deleted = 0
            ");
            $stmtVacantes->execute();
            $events = array_merge($events, $stmtVacantes->fetchAll(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            error_log('medidata_rrhh_fetch_eventos_calendario: ' . $e->getMessage());
        }

        return $events;
    }
}

if (!function_exists('medidata_rrhh_fetch_conteos_dashboard')) {
    function medidata_rrhh_fetch_conteos_dashboard(): array
    {
        $conteos = [
            'vacantes_activas' => 0,
            'postulantes' => 0,
            'entrevistas_hoy' => 0,
        ];

        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return $conteos;
        }

        try {
            $conteos['vacantes_activas'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM vacantes_trabajo WHERE deleted = 0 AND end_date >= CURDATE()"
            )->fetchColumn();
            $conteos['postulantes'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM postulantes WHERE deleted = 0"
            )->fetchColumn();
            // Candidatos en etapa activa de entrevista (alineado con entrevista.php).
            $conteos['entrevistas_hoy'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM candidates
                 WHERE deleted = 0
                   AND status IN ('Entrevista', 'Agendado')"
            )->fetchColumn();
        } catch (Throwable $e) {
            error_log('medidata_rrhh_fetch_conteos_dashboard: ' . $e->getMessage());
        }

        return $conteos;
    }
}

if (!function_exists('medidata_rrhh_is_safe_return_url')) {
    function medidata_rrhh_is_safe_return_url(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || preg_match('/^\s*javascript:/i', $url)) {
            return false;
        }
        if (str_starts_with($url, '/')) {
            return !str_contains($url, '..');
        }
        if (!preg_match('#^https?://#i', $url)) {
            return !str_contains($url, '..');
        }
        $host = parse_url($url, PHP_URL_HOST);
        $current = $_SERVER['HTTP_HOST'] ?? '';
        return is_string($host) && $host !== '' && strcasecmp($host, $current) === 0;
    }
}

if (!function_exists('medidata_rrhh_is_detalle_postulante_url')) {
    function medidata_rrhh_is_detalle_postulante_url(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            return false;
        }
        return (bool) preg_match('/detalle_postulante(_usr)?\.php$/i', basename($path));
    }
}

if (!function_exists('medidata_rrhh_detalle_volver_url')) {
    function medidata_rrhh_detalle_volver_url(string $defaultPage): string
    {
        $return = trim((string) ($_GET['return'] ?? ''));
        if ($return !== '' && medidata_rrhh_is_safe_return_url($return)) {
            return $return;
        }

        $referer = trim((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        if (
            $referer !== ''
            && medidata_rrhh_is_safe_return_url($referer)
            && !medidata_rrhh_is_detalle_postulante_url($referer)
        ) {
            return $referer;
        }

        return $defaultPage;
    }
}
