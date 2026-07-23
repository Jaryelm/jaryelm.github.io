<?php
/**
 * Agenda institucional RRHH: eventos públicos y compartidos con usuarios de MEDIDATA.
 */
declare(strict_types=1);

require_once __DIR__ . '/../registros/rrhh_guard.php';

if (!function_exists('medidata_calendar_ensure_event_shares')) {
    function medidata_calendar_ensure_event_shares(?PDO $pdo = null): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $pdo = $pdo ?: medidata_rrhh_pdo();
        if (!$pdo) {
            return;
        }
        try {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS rrhh_event_shares (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    event_id INT NOT NULL,
                    user_id INT NOT NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY uq_rrhh_event_user (event_id, user_id),
                    KEY idx_rrhh_share_user (user_id),
                    KEY idx_rrhh_share_event (event_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );
        } catch (Throwable $e) {
            error_log('medidata_calendar_ensure_event_shares: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_calendar_save_event_shares')) {
    /**
     * @param list<int|string> $userIds
     */
    function medidata_calendar_save_event_shares(int $eventId, array $userIds, ?PDO $pdo = null): void
    {
        if ($eventId <= 0) {
            return;
        }
        $pdo = $pdo ?: medidata_rrhh_pdo();
        if (!$pdo) {
            return;
        }
        medidata_calendar_ensure_event_shares($pdo);
        $clean = [];
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $clean[$uid] = $uid;
            }
        }
        $pdo->prepare('DELETE FROM rrhh_event_shares WHERE event_id = ?')->execute([$eventId]);
        if ($clean === []) {
            return;
        }
        $ins = $pdo->prepare('INSERT INTO rrhh_event_shares (event_id, user_id) VALUES (?, ?)');
        foreach ($clean as $uid) {
            $ins->execute([$eventId, $uid]);
        }
    }
}

if (!function_exists('medidata_calendar_event_share_ids')) {
    /** @return list<int> */
    function medidata_calendar_event_share_ids(int $eventId, ?PDO $pdo = null): array
    {
        if ($eventId <= 0) {
            return [];
        }
        $pdo = $pdo ?: medidata_rrhh_pdo();
        if (!$pdo) {
            return [];
        }
        medidata_calendar_ensure_event_shares($pdo);
        $stmt = $pdo->prepare('SELECT user_id FROM rrhh_event_shares WHERE event_id = ?');
        $stmt->execute([$eventId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }
}

if (!function_exists('medidata_calendar_fetch_institucional_raw')) {
    /**
     * Eventos RRHH visibles para un usuario MEDIDATA (públicos o compartidos).
     * @return list<array<string,mixed>>
     */
    function medidata_calendar_fetch_institucional_raw(int $userId): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $userId <= 0) {
            return [];
        }
        medidata_rrhh_ensure_recurrence_columns($pdo);
        medidata_calendar_ensure_event_shares($pdo);

        $sql = "
            SELECT
                e.id,
                e.title,
                CONCAT(e.start_date, IF(e.start_time IS NOT NULL, CONCAT(' ', e.start_time), '')) AS start,
                CONCAT(e.end_date, IF(e.end_time IS NOT NULL, CONCAT(' ', e.end_time), '')) AS end,
                e.start_date, e.start_time, e.end_date, e.end_time,
                e.color,
                e.description,
                e.all_day,
                e.is_public,
                e.recurrence,
                e.recurrence_until,
                e.id_user,
                t.name AS event_type_name
            FROM rrhh_custom_events e
            LEFT JOIN rrhh_calendar_event_types t ON e.id_event_type = t.id
            WHERE e.deleted = 0
              AND (
                    e.is_public = 1
                 OR EXISTS (
                        SELECT 1 FROM rrhh_event_shares s
                        WHERE s.event_id = e.id AND s.user_id = ?
                    )
              )
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        $winStart = strtotime('-31 days 00:00:00');
        $winEnd = strtotime('+366 days 23:59:59');
        foreach ($rows as $custom) {
            $rec = (string) ($custom['recurrence'] ?? 'none');
            if ($rec === '' || $rec === 'none') {
                $custom['raw_id'] = $custom['id'];
                $custom['id'] = 'rrhh_inst_' . $custom['id'];
                $custom['allDay'] = !empty($custom['all_day']);
                $custom['type'] = 'institutional';
                $out[] = $custom;
            } elseif (function_exists('medidata_rrhh_expand_custom_event')) {
                foreach (medidata_rrhh_expand_custom_event($custom, $winStart, $winEnd) as $occ) {
                    $occ['type'] = 'institutional';
                    $out[] = $occ;
                }
            }
        }
        return $out;
    }
}

if (!function_exists('medidata_calendar_institucional_as_programacion_events')) {
    /**
     * Adapta eventos institucionales al formato de escritorios (events médicos).
     * @return list<array<string,mixed>>
     */
    function medidata_calendar_institucional_as_programacion_events(int $userId): array
    {
        $adapted = [];
        foreach (medidata_calendar_fetch_institucional_raw($userId) as $ev) {
            $start = (string) ($ev['start'] ?? '');
            $end = (string) ($ev['end'] ?? $start);
            if ($start === '') {
                continue;
            }
            // Normalizar fecha sin hora → 00:00:00 para lógica de escritorios.
            if (strpos($start, ' ') === false) {
                $start .= ' 00:00:00';
            }
            if (strpos($end, ' ') === false) {
                $end .= ' 00:00:00';
            }
            $title = (string) ($ev['title'] ?? 'Evento');
            $adapted[] = [
                'id' => $ev['id'],
                'title' => $title,
                'start' => $start,
                'end' => $end,
                'color' => $ev['color'] ?? '#035c67',
                'patient_name' => 'Institucional',
                'patient_surname' => '',
                'doctor_name' => 'Recursos Humanos',
                'doctor_surname' => '',
                'specialty' => (string) ($ev['event_type_name'] ?? 'Agenda institucional'),
                'area_name' => 'Agenda institucional',
                'room_number' => '',
                'insurer' => '',
                'policy_number' => '',
                'certificate_number' => '',
                'surgery' => '',
                'hospitalization' => '',
                'assistant' => '',
                'anesthetist' => '',
                'circulating' => '',
                'technician' => '',
                'instrumentist' => '',
                'evaluation' => (string) ($ev['description'] ?? ''),
                'is_institutional' => 1,
                'is_public' => (int) ($ev['is_public'] ?? 0),
            ];
        }
        return $adapted;
    }
}
