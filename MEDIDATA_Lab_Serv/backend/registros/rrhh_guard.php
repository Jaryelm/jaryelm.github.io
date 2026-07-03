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

if (!function_exists('medidata_rrhh_recurrence_options')) {
    /**
     * Reglas de recurrencia permitidas para eventos del calendario.
     *
     * @return string[]
     */
    function medidata_rrhh_recurrence_options(): array
    {
        return ['none', 'daily', 'weekly', 'monthly', 'yearly', 'weekdays'];
    }
}

if (!function_exists('medidata_rrhh_ensure_recurrence_columns')) {
    /**
     * Garantiza que rrhh_custom_events tenga las columnas de recurrencia.
     * Auto-sanado para despliegues antiguos (idempotente; corre una vez).
     */
    function medidata_rrhh_ensure_recurrence_columns(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `rrhh_custom_events` LIKE 'recurrence'");
            if (!$stmt || !$stmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec("ALTER TABLE `rrhh_custom_events`
                    ADD COLUMN `recurrence` VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `is_public`");
            }
            $stmt2 = $pdo->query("SHOW COLUMNS FROM `rrhh_custom_events` LIKE 'recurrence_until'");
            if (!$stmt2 || !$stmt2->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec("ALTER TABLE `rrhh_custom_events`
                    ADD COLUMN `recurrence_until` DATE NULL DEFAULT NULL AFTER `recurrence`");
            }
        } catch (Throwable $e) {
            error_log('medidata_rrhh_ensure_recurrence_columns: ' . $e->getMessage());
        }
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

if (!function_exists('medidata_rrhh_departament_name')) {
    /**
     * Nombre del departamento por id (columna legacy department en positions_details).
     */
    function medidata_rrhh_departament_name(PDO $pdo, int $idDepartament): string
    {
        if ($idDepartament <= 0) {
            return '';
        }
        try {
            $stmt = $pdo->prepare('SELECT name FROM departaments WHERE id = ? LIMIT 1');
            $stmt->execute([$idDepartament]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? trim((string) $row['name']) : '';
        } catch (Throwable $e) {
            error_log('medidata_rrhh_departament_name: ' . $e->getMessage());
            return '';
        }
    }
}

if (!function_exists('medidata_rrhh_job_profile_max_bytes')) {
    function medidata_rrhh_job_profile_max_bytes(): int
    {
        return 5 * 1024 * 1024; // 5 MB
    }
}

if (!function_exists('medidata_rrhh_job_profile_upload')) {
    /**
     * Valida y lee el documento opcional de perfil de puesto.
     *
     * @return array{content: ?string, mime: ?string, error: ?string}
     */
    function medidata_rrhh_job_profile_upload(?array $file): array
    {
        $empty = ['content' => null, 'mime' => null, 'error' => null];
        if (!$file || !isset($file['error'])) {
            return $empty;
        }
        if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return $empty;
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['content' => null, 'mime' => null, 'error' => 'No se pudo subir el documento de perfil.'];
        }

        $maxBytes = medidata_rrhh_job_profile_max_bytes();
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0) {
            return ['content' => null, 'mime' => null, 'error' => 'El archivo seleccionado está vacío.'];
        }
        if ($size > $maxBytes) {
            return [
                'content' => null,
                'mime' => null,
                'error' => 'El documento supera el tamaño máximo permitido (5 MB).',
            ];
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExt = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExt, true)) {
            return [
                'content' => null,
                'mime' => null,
                'error' => 'Formato no permitido. Use PDF, Word o imagen (JPG/PNG).',
            ];
        }

        $content = file_get_contents((string) $file['tmp_name']);
        if ($content === false) {
            return ['content' => null, 'mime' => null, 'error' => 'No se pudo leer el archivo subido.'];
        }

        return [
            'content' => $content,
            'mime' => trim((string) ($file['type'] ?? 'application/octet-stream')),
            'error' => null,
        ];
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
            $query = "SELECT p.*, p2.name AS vacancy_name
                      FROM postulantes p
                      LEFT JOIN vacant_positions v ON p.id_vacant_position = v.id
                      LEFT JOIN positions_details pd ON v.id_position = pd.id
                      LEFT JOIN medic9ue_medi_data.positions p2 ON pd.id_positions = p2.id
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

if (!function_exists('medidata_rrhh_expand_custom_event')) {
    /**
     * Expande un evento recurrente (fila maestra) en ocurrencias dentro de la
     * ventana [$winStart, $winEnd] (timestamps), acotado por recurrence_until y
     * por un tope de ocurrencias. Cada ocurrencia conserva la duracion del
     * maestro y lleva raw_id = id del maestro.
     *
     * @param array<string,mixed> $m
     * @return array<int,array<string,mixed>>
     */
    function medidata_rrhh_expand_custom_event(array $m, int $winStart, int $winEnd, int $occCap = 400): array
    {
        $recurrence = (string) ($m['recurrence'] ?? 'none');
        if (!in_array($recurrence, medidata_rrhh_recurrence_options(), true) || $recurrence === 'none') {
            return [];
        }

        $isAllDay = !empty($m['all_day']);
        $startDate = (string) ($m['start_date'] ?? '');
        if ($startDate === '' || strpos($startDate, '0000-00-00') !== false) {
            return [];
        }
        $startTime = $isAllDay ? null : (!empty($m['start_time']) ? (string) $m['start_time'] : null);
        $endDate = (string) ($m['end_date'] ?? '');
        if ($endDate === '' || strpos($endDate, '0000-00-00') !== false) {
            $endDate = $startDate;
        }
        $endTime = $isAllDay ? null : (!empty($m['end_time']) ? (string) $m['end_time'] : null);

        // Duracion del maestro.
        $spanDays = (int) round((strtotime($endDate) - strtotime($startDate)) / 86400);
        if ($spanDays < 0) {
            $spanDays = $isAllDay ? 1 : 0;
        }
        $baseStartTs = strtotime($startDate . ' ' . ($startTime ?: '00:00:00'));
        $baseEndTs = strtotime($endDate . ' ' . ($endTime ?: '00:00:00'));
        $durationSec = max(0, $baseEndTs - $baseStartTs);

        $untilTs = $winEnd;
        if (!empty($m['recurrence_until']) && strpos((string) $m['recurrence_until'], '0000-00-00') === false) {
            $untilTs = min(strtotime(((string) $m['recurrence_until']) . ' 23:59:59'), $winEnd);
        }

        $makeOcc = function (string $D) use ($m, $isAllDay, $startTime, $endTime, $spanDays, $durationSec): array {
            if ($isAllDay) {
                $occEndDate = date('Y-m-d', strtotime($D) + max(1, $spanDays) * 86400);
                $start = $D;
                $end = $occEndDate;
                $occStartDate = $D;
            } else {
                $start = $D . ' ' . ($startTime ?: '00:00:00');
                $endTs = strtotime($start) + $durationSec;
                $end = date('Y-m-d H:i:s', $endTs);
                $occStartDate = $D;
                $occEndDate = date('Y-m-d', $endTs);
            }
            return array_merge($m, [
                'id' => 'custom_' . $m['id'] . '_occ_' . str_replace('-', '', $D),
                'raw_id' => $m['id'],
                'start' => $start,
                'end' => $end,
                'start_date' => $occStartDate,
                'start_time' => $startTime,
                'end_date' => $occEndDate,
                'end_time' => $endTime,
                'allDay' => $isAllDay,
            ]);
        };

        $occ = [];
        $count = 0;
        $guard = 0;
        $anchor = new DateTime($startDate);
        $anchorDay = (int) $anchor->format('j');
        $anchorMon = (int) $anchor->format('n');
        $anchorYear = (int) $anchor->format('Y');

        if ($recurrence === 'daily' || $recurrence === 'weekly' || $recurrence === 'weekdays') {
            $cur = clone $anchor;
            $stepDays = $recurrence === 'weekly' ? 7 : 1;
            while ($guard++ < 6000 && $count < $occCap) {
                $ts = $cur->getTimestamp();
                if ($ts > $untilTs) {
                    break;
                }
                if ($ts >= $winStart) {
                    $emit = true;
                    if ($recurrence === 'weekdays') {
                        $dow = (int) $cur->format('N'); // 1=lun ... 7=dom
                        $emit = $dow <= 5;
                    }
                    if ($emit) {
                        $occ[] = $makeOcc($cur->format('Y-m-d'));
                        $count++;
                    }
                }
                $cur->modify('+' . $stepDays . ' day');
            }
        } elseif ($recurrence === 'monthly') {
            for ($i = 0; $guard++ < 6000 && $count < $occCap; $i++) {
                $candMon = $anchorMon + $i;
                $candYear = $anchorYear + intdiv($candMon - 1, 12);
                $candMon = (($candMon - 1) % 12) + 1;
                if (!checkdate($candMon, $anchorDay, $candYear)) {
                    continue; // mes sin ese dia (p. ej. 31)
                }
                $D = sprintf('%04d-%02d-%02d', $candYear, $candMon, $anchorDay);
                $ts = strtotime($D);
                if ($ts > $untilTs) {
                    break;
                }
                if ($ts >= $winStart) {
                    $occ[] = $makeOcc($D);
                    $count++;
                }
            }
        } elseif ($recurrence === 'yearly') {
            for ($i = 0; $guard++ < 6000 && $count < $occCap; $i++) {
                $candYear = $anchorYear + $i;
                if (!checkdate($anchorMon, $anchorDay, $candYear)) {
                    continue; // 29-feb en anios no bisiestos
                }
                $D = sprintf('%04d-%02d-%02d', $candYear, $anchorMon, $anchorDay);
                $ts = strtotime($D);
                if ($ts > $untilTs) {
                    break;
                }
                if ($ts >= $winStart) {
                    $occ[] = $makeOcc($D);
                    $count++;
                }
            }
        }

        return $occ;
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
        $mainDb = defined('dbname') ? (string) dbname : 'medic9ue_medi_data';
        $userId = (int) ($_SESSION['id'] ?? 0);

        try {
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
                LEFT JOIN {$mainDb}.positions pt ON pd.id_positions = pt.id
                WHERE i.deleted = 0 AND (i.id_interviewer = ? OR i.id_interviewer IS NULL)
            ");
            $stmtInterviews->execute([$userId]);
            foreach ($stmtInterviews->fetchAll(PDO::FETCH_ASSOC) as $inv) {
                $inv['id'] = 'interview_' . $inv['id'];
                $d = trim((string) ($inv['raw_start_date'] ?? ''));
                $t = trim((string) ($inv['raw_start_time'] ?? ''));
                if ($d === '' || strpos($d, '0000-00-00') !== false) {
                    $d = date('Y-m-d');
                }
                $inv['start'] = $t === '' ? $d : $d . ' ' . $t;
                $inv['end'] = date('Y-m-d H:i:s', strtotime($inv['start']) + 3600);
                unset($inv['raw_start_date'], $inv['raw_start_time']);
                $events[] = $inv;
            }

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
                JOIN {$mainDb}.positions pt ON pd.id_positions = pt.id
                WHERE vp.deleted = 0
            ");
            $stmtVacantes->execute();
            foreach ($stmtVacantes->fetchAll(PDO::FETCH_ASSOC) as $vac) {
                $vac['id'] = 'vacancy_' . $vac['id'];
                $d = trim((string) ($vac['raw_end_date'] ?? ''));
                if ($d === '' || strpos($d, '0000-00-00') !== false) {
                    $d = date('Y-m-d');
                }
                $vac['start'] = $d;
                $vac['end'] = $d;
                unset($vac['raw_end_date']);
                $events[] = $vac;
            }

            medidata_rrhh_ensure_recurrence_columns($pdo);
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
                    e.recurrence,
                    e.recurrence_until,
                    t.name AS event_type_name,
                    'custom' AS type
                FROM rrhh_custom_events e
                LEFT JOIN rrhh_calendar_event_types t ON e.id_event_type = t.id
                WHERE e.deleted = 0 AND (e.id_user = ? OR e.is_public = 1)
            ");
            $stmtCustom->execute([$userId]);
            $winStart = strtotime('-31 days 00:00:00');
            $winEnd = strtotime('+366 days 23:59:59');
            foreach ($stmtCustom->fetchAll(PDO::FETCH_ASSOC) as $custom) {
                $rec = (string) ($custom['recurrence'] ?? 'none');
                if ($rec === '' || $rec === 'none') {
                    $custom['raw_id'] = $custom['id'];
                    $custom['id'] = 'custom_' . $custom['id'];
                    $custom['allDay'] = !empty($custom['all_day']);
                    $events[] = $custom;
                } else {
                    foreach (medidata_rrhh_expand_custom_event($custom, $winStart, $winEnd) as $occ) {
                        $events[] = $occ;
                    }
                }
            }

            $currentYear = (int) date('Y');
            $yearFrom = $currentYear - 1;
            $yearTo = $currentYear + 5;

            $stmtBirthdays = $pdo->prepare("
                SELECT
                    CONCAT(IFNULL(nodoc, ''), ' ', IFNULL(apdoc, '')) AS FullName,
                    'DOCTOR' AS Ocupation,
                    MONTH(nacd) AS m,
                    DAY(nacd) AS d
                FROM {$mainDb}.doctor
                WHERE nacd IS NOT NULL
                UNION ALL
                SELECT
                    CONCAT(IFNULL(nomnur, ''), ' ', IFNULL(apenur, '')) AS FullName,
                    'ENFERMERO(A)' AS Ocupation,
                    MONTH(nacinur) AS m,
                    DAY(nacinur) AS d
                FROM {$mainDb}.nurse
                WHERE nacinur IS NOT NULL
                UNION ALL
                SELECT
                    CONCAT(IFNULL(nomadm, ''), ' ', IFNULL(apeadm, '')) AS FullName,
                    'ADMINISTRATIVO' AS Ocupation,
                    MONTH(nacadm) AS m,
                    DAY(nacadm) AS d
                FROM {$mainDb}.staff_administrative
                WHERE nacadm IS NOT NULL AND state = '1'
                UNION ALL
                SELECT
                    CONCAT(IFNULL(nomsg, ''), ' ', IFNULL(apesg, '')) AS FullName,
                    'SERVICIOS GENERALES' AS Ocupation,
                    MONTH(nacsg) AS m,
                    DAY(nacsg) AS d
                FROM {$mainDb}.staff_general_services
                WHERE nacsg IS NOT NULL AND state = '1'
            ");
            $stmtBirthdays->execute();
            foreach ($stmtBirthdays->fetchAll(PDO::FETCH_ASSOC) as $b) {
                if (empty($b['m']) || empty($b['d'])) {
                    continue;
                }
                $month = (int) $b['m'];
                $day = (int) $b['d'];
                $fullName = trim((string) ($b['FullName'] ?? ''));
                $ocupation = (string) ($b['Ocupation'] ?? '');
                for ($yr = $yearFrom; $yr <= $yearTo; $yr++) {
                    if (!checkdate($month, $day, $yr)) {
                        continue;
                    }
                    $eventDate = sprintf('%04d-%02d-%02d', $yr, $month, $day);
                    $events[] = [
                        'id' => 'birthday_' . md5($fullName . $ocupation) . '_' . $yr,
                        'title' => '🎉 Cumpleaños: ' . $fullName . ' (' . $ocupation . ')',
                        'start' => $eventDate,
                        'end' => $eventDate,
                        'color' => '#FC3B56',
                        'allDay' => true,
                        'type' => 'birthday',
                    ];
                }
            }

            $stmtAnniversaries = $pdo->prepare("
                SELECT
                    CONCAT(IFNULL(nomadm, ''), ' ', IFNULL(apeadm, '')) AS FullName,
                    'ADMINISTRATIVO' AS Ocupation,
                    fecha_ingreso AS ingreso
                FROM {$mainDb}.staff_administrative
                WHERE fecha_ingreso IS NOT NULL AND state = '1'
                UNION ALL
                SELECT
                    CONCAT(IFNULL(nomsg, ''), ' ', IFNULL(apesg, '')) AS FullName,
                    'SERVICIOS GENERALES' AS Ocupation,
                    fecha_ingreso AS ingreso
                FROM {$mainDb}.staff_general_services
                WHERE fecha_ingreso IS NOT NULL AND state = '1'
            ");
            $stmtAnniversaries->execute();
            foreach ($stmtAnniversaries->fetchAll(PDO::FETCH_ASSOC) as $a) {
                $ingreso = (string) ($a['ingreso'] ?? '');
                if ($ingreso === '' || $ingreso === '0000-00-00') {
                    continue;
                }
                $ts = strtotime($ingreso);
                if ($ts === false) {
                    continue;
                }
                $month = (int) date('n', $ts);
                $day = (int) date('j', $ts);
                $startYear = (int) date('Y', $ts);
                $fullName = trim((string) ($a['FullName'] ?? ''));
                $ocupation = (string) ($a['Ocupation'] ?? '');
                for ($yr = $yearFrom; $yr <= $yearTo; $yr++) {
                    if ($yr <= $startYear) {
                        continue;
                    }
                    if (!checkdate($month, $day, $yr)) {
                        continue;
                    }
                    $years = $yr - $startYear;
                    $eventDate = sprintf('%04d-%02d-%02d', $yr, $month, $day);
                    $events[] = [
                        'id' => 'anniversary_' . md5($fullName . $ocupation) . '_' . $yr,
                        'title' => '🏆 Aniversario: ' . $fullName . ' (' . $years . ' año' . ($years === 1 ? '' : 's') . ')',
                        'start' => $eventDate,
                        'end' => $eventDate,
                        'color' => '#1E88E5',
                        'allDay' => true,
                        'type' => 'anniversary',
                    ];
                }
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
                "SELECT COUNT(*) FROM vacant_positions WHERE deleted = 0 AND end_date >= CURDATE()"
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
