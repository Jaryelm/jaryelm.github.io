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
            $sql = "SELECT v.id, p.name
                    FROM vacant_positions v
                    JOIN positions_details pd ON v.id_position = pd.id
                    JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id
                    WHERE v.deleted = 0
                    ORDER BY p.name ASC";
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
            $mainDb = defined('dbname') ? dbname : 'medic9ue_medi_data';
            $query = "SELECT c.*, p2.name AS vacancy_name
                      FROM candidates c
                      LEFT JOIN vacant_positions v ON c.id_vacant_position = v.id
                      LEFT JOIN positions_details pd ON v.id_position = pd.id
                      LEFT JOIN $mainDb.positions p2 ON pd.id_positions = p2.id
                      WHERE c.deleted = 0 AND ({$statusCondition})";

            if ($idVacante > 0) {
                $query .= ' AND c.id_vacant_position = :id_vacante';
            }

            $query .= ' ORDER BY c.id DESC';

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
        $mainDb = defined('dbname') ? dbname : 'medic9ue_medi_data';

        try {
            $userId = $_SESSION['id'] ?? 0;
            // 1. Entrevistas Programadas
            $stmtInterviews = $pdo->prepare("
                SELECT
                    i.id,
                    CONCAT('Entrevista: ', c.fullname) AS title,
                    i.date_interview AS raw_start_date,
                    i.time_interview AS raw_start_time,
                    CASE
                        WHEN i.status = 'Programada' THEN '#035c67'
                        WHEN i.status = 'En Proceso' THEN '#06adbf'
                        WHEN i.status = 'Terminada' THEN '#81D43A'
                        ELSE '#8D8D8D'
                    END AS color,
                    c.id AS candidate_id,
                    c.fullname AS candidate_name,
                    c.dni AS candidate_dni,
                    c.email AS candidate_email,
                    c.phonenumber AS candidate_phone,
                    i.status AS interview_status,
                    pt.name AS position_name,
                    'interview' AS type
                FROM interviews i
                INNER JOIN candidates c ON i.id_candidate = c.id
                LEFT JOIN vacant_positions vp ON c.id_vacant_position = vp.id
                LEFT JOIN positions_details pd ON vp.id_position = pd.id
                LEFT JOIN $mainDb.positions pt ON pd.id_positions = pt.id
                WHERE i.deleted = 0 AND (i.id_interviewer = ? OR i.id_interviewer IS NULL)
            ");
            $stmtInterviews->execute([$userId]);
            $interviews = $stmtInterviews->fetchAll(PDO::FETCH_ASSOC);
            foreach ($interviews as &$inv) {
                $inv['id'] = 'interview_' . $inv['id']; // Unique ID
                $d = trim((string)$inv['raw_start_date']);
                $t = trim((string)$inv['raw_start_time']);
                if ($d === '' || strpos($d, '0000-00-00') !== false) {
                    $d = date('Y-m-d');
                }
                $inv['start'] = $t === '' ? $d : "$d $t";
                $inv['end'] = date('Y-m-d H:i:s', strtotime($inv['start']) + 3600); // +1 hora
                $events[] = $inv;
            }

            // 2. Cierres de Vacantes
            $stmtVacantes = $pdo->prepare("
                SELECT
                    vp.id,
                    CONCAT('Cierre Vacante: ', pt.name) AS title,
                    vp.end_date AS raw_end_date,
                    '#FC3B56' AS color,
                    pt.name AS position_name,
                    vp.benefits,
                    'vacancy_end' AS type
                FROM vacant_positions vp
                JOIN positions_details pd ON vp.id_position = pd.id
                JOIN $mainDb.positions pt ON pd.id_positions = pt.id
                WHERE vp.deleted = 0
            ");
            $stmtVacantes->execute();
            $vacantes = $stmtVacantes->fetchAll(PDO::FETCH_ASSOC);
            foreach ($vacantes as &$vac) {
                $vac['id'] = 'vacancy_' . $vac['id']; // Unique ID
                $d = trim((string)$vac['raw_end_date']);
                if ($d === '' || strpos($d, '0000-00-00') !== false) {
                    $d = date('Y-m-d');
                }
                $vac['start'] = $d;
                $vac['end'] = $d;
                $events[] = $vac;
            }

            // 3. Eventos Personalizados
            $stmtCustom = $pdo->prepare("
                SELECT
                    e.id,
                    e.title,
                    CONCAT(e.start_date, IF(e.start_time IS NOT NULL, CONCAT(' ', e.start_time), '')) AS start,
                    CONCAT(e.end_date, IF(e.end_time IS NOT NULL, CONCAT(' ', e.end_time), '')) AS end,
                    e.start_date, e.start_time, e.end_date, e.end_time,
                    e.color,
                    e.id_event_type,
                    e.description,
                    e.all_day,
                    e.is_public,
                    t.name AS event_type_name,
                    'custom' AS type
                FROM rrhh_custom_events e
                LEFT JOIN rrhh_calendar_event_types t ON e.id_event_type = t.id
                WHERE e.deleted = 0 AND (e.id_user = ? OR e.is_public = 1)
            ");
            $stmtCustom->execute([$userId]);
            $customs = $stmtCustom->fetchAll(PDO::FETCH_ASSOC);
            foreach ($customs as &$c) {
                $c['raw_id'] = $c['id'];
                $c['id'] = 'custom_' . $c['id']; // Unique ID
                $c['allDay'] = (bool)$c['all_day'];
                $events[] = $c;
            }

            // 4. Cumpleaños de Staff (Solo año vigente)
            $currentYear = date('Y');
            $stmtBirthdays = $pdo->prepare("
                SELECT 
                    CONCAT(IFNULL(nodoc, ''), ' ', IFNULL(apdoc, '')) AS FullName,
                    'DOCTOR' AS Ocupation,
                    MONTH(nacd) AS m,
                    DAY(nacd) AS d
                FROM $mainDb.doctor
                WHERE nacd IS NOT NULL
                UNION ALL
                SELECT 
                    CONCAT(IFNULL(nomnur, ''), ' ', IFNULL(apenur, '')) AS FullName,
                    'ENFERMERO(A)' AS Ocupation,
                    MONTH(nacinur) AS m,
                    DAY(nacinur) AS d
                FROM $mainDb.nurse
                WHERE nacinur IS NOT NULL
                UNION ALL
                SELECT 
                    CONCAT(IFNULL(nomadm, ''), ' ', IFNULL(apeadm, '')) AS FullName,
                    'ADMINISTRATIVO' AS Ocupation,
                    MONTH(nacadm) AS m,
                    DAY(nacadm) AS d
                FROM $mainDb.staff_administrative
                WHERE nacadm IS NOT NULL AND state = '1'
            ");
            $stmtBirthdays->execute();
            $birthdays = $stmtBirthdays->fetchAll(PDO::FETCH_ASSOC);
            foreach ($birthdays as $b) {
                if (empty($b['m']) || empty($b['d'])) continue; // Saltamos si el mes o dia es inválido

                $month = str_pad($b['m'], 2, '0', STR_PAD_LEFT);
                $day = str_pad($b['d'], 2, '0', STR_PAD_LEFT);
                $eventDate = $currentYear . '-' . $month . '-' . $day;

                $events[] = [
                    'id' => 'birthday_' . md5($b['FullName'] . $b['Ocupation']),
                    'title' => '🎉 Cumpleaños: ' . trim($b['FullName']) . ' (' . $b['Ocupation'] . ')',
                    'start' => $eventDate,
                    'color' => '#FC3B56', // Red/Pink typically used for birthdays
                    'allDay' => true,
                    'type' => 'birthday'
                ];
            }
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
                "SELECT COUNT(*) FROM vacant_positions WHERE deleted = 0 AND status = 'Abierta' AND end_date >= CURDATE()"
            )->fetchColumn();
            $conteos['postulantes'] = (int) $pdo->query(
                "SELECT COUNT(*) FROM candidates WHERE deleted = 0"
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
