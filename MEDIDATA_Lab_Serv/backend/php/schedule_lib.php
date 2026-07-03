<?php
/**
 * Horarios laborales — descanso y horas efectivas semanales.
 */

if (!function_exists('medidata_schedule_ensure_schema')) {
    function medidata_schedule_ensure_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $cols = $pdo->query('SHOW COLUMNS FROM schedules')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('break_minutes', $cols, true)) {
                $pdo->exec(
                    'ALTER TABLE schedules ADD COLUMN break_minutes INT NOT NULL DEFAULT 0 AFTER name'
                );
            }
            if (!in_array('weekly_effective_hours', $cols, true)) {
                $pdo->exec(
                    'ALTER TABLE schedules ADD COLUMN weekly_effective_hours DECIMAL(6,2) NULL DEFAULT NULL AFTER break_minutes'
                );
            }

            $detailCols = $pdo->query('SHOW COLUMNS FROM schedule_details')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('apply_break', $detailCols, true)) {
                $pdo->exec(
                    'ALTER TABLE schedule_details ADD COLUMN apply_break TINYINT(1) NOT NULL DEFAULT 1 AFTER exit_time'
                );
            }
        } catch (Throwable $e) {
            error_log('medidata_schedule_ensure_schema: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_schedule_break_options')) {
    /** @return array<int, string> */
    function medidata_schedule_break_options(): array
    {
        return [
            0 => 'No aplica',
            30 => '30 minutos',
            60 => '1 hora',
        ];
    }
}

if (!function_exists('medidata_schedule_normalize_break_minutes')) {
    function medidata_schedule_normalize_break_minutes($value): int
    {
        $v = (int) $value;
        return in_array($v, [0, 30, 60], true) ? $v : 0;
    }
}

if (!function_exists('medidata_schedule_time_to_minutes')) {
    function medidata_schedule_time_to_minutes(string $time): int
    {
        $time = trim($time);
        if ($time === '') {
            return 0;
        }
        $parts = explode(':', substr($time, 0, 5));
        $h = (int) ($parts[0] ?? 0);
        $m = (int) ($parts[1] ?? 0);
        return ($h * 60) + $m;
    }
}

if (!function_exists('medidata_schedule_shift_minutes')) {
    /**
     * Duración del turno en minutos. Si la salida es anterior a la entrada, asume cruce de medianoche.
     */
    function medidata_schedule_shift_minutes(int $entry, int $exit): int
    {
        if ($entry === $exit) {
            return 0;
        }
        if ($exit > $entry) {
            return $exit - $entry;
        }
        return (1440 - $entry) + $exit;
    }
}

if (!function_exists('medidata_schedule_calc_weekly')) {
    /**
     * @param array<int, array<string, mixed>> $details
     * @return array{gross_minutes:int,break_minutes_total:int,effective_minutes:int,effective_hours:float,active_days:int}
     */
    function medidata_schedule_calc_weekly(array $details, int $breakMinutes): array
    {
        $breakMinutes = medidata_schedule_normalize_break_minutes($breakMinutes);
        $gross = 0;
        $activeDays = 0;
        $breakTotal = 0;

        foreach ($details as $d) {
            if (!is_array($d)) {
                continue;
            }
            $entryRaw = trim((string) ($d['entry_time'] ?? ''));
            $exitRaw = trim((string) ($d['exit_time'] ?? ''));
            if ($entryRaw === '' || $exitRaw === '') {
                continue;
            }

            $entry = medidata_schedule_time_to_minutes($entryRaw);
            $exit = medidata_schedule_time_to_minutes($exitRaw);
            $shiftMinutes = medidata_schedule_shift_minutes($entry, $exit);
            if ($shiftMinutes <= 0) {
                continue;
            }

            $gross += $shiftMinutes;
            $activeDays++;

            $applyBreak = !isset($d['apply_break']) || (string) $d['apply_break'] === '1' || $d['apply_break'] === true || $d['apply_break'] === 1;
            if ($breakMinutes > 0 && $applyBreak) {
                $breakTotal += $breakMinutes;
            }
        }

        $effective = max(0, $gross - $breakTotal);

        return [
            'gross_minutes' => $gross,
            'break_minutes_total' => $breakTotal,
            'effective_minutes' => $effective,
            'effective_hours' => round($effective / 60, 2),
            'active_days' => $activeDays,
        ];
    }
}

if (!function_exists('medidata_schedule_break_label')) {
    function medidata_schedule_break_label(int $breakMinutes): string
    {
        $opts = medidata_schedule_break_options();
        return $opts[medidata_schedule_normalize_break_minutes($breakMinutes)] ?? 'No aplica';
    }
}

if (!function_exists('medidata_schedule_format_hours')) {
    function medidata_schedule_format_hours($hours): string
    {
        $h = round((float) $hours, 2);
        if (abs($h - round($h)) < 0.001) {
            return (string) (int) round($h) . ' hrs';
        }
        return number_format($h, 1, '.', '') . ' hrs';
    }
}

if (!function_exists('medidata_schedule_details_from_db')) {
    /** @return array<int, array{day:string,entry_time:string,exit_time:string,apply_break:int}> */
    function medidata_schedule_details_from_db(PDO $pdo, int $scheduleId): array
    {
        $stmt = $pdo->prepare(
            'SELECT day, entry_time, exit_time, apply_break FROM schedule_details WHERE id_schedule = ? ORDER BY FIELD(day, \'Lu\', \'Ma\', \'Mi\', \'Ju\', \'Vi\', \'Sa\', \'Do\')'
        );
        $stmt->execute([$scheduleId]);
        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $rows[] = [
                'day' => (string) $row['day'],
                'entry_time' => substr((string) $row['entry_time'], 0, 5),
                'exit_time' => substr((string) $row['exit_time'], 0, 5),
                'apply_break' => (int) ($row['apply_break'] ?? 1),
            ];
        }
        return $rows;
    }
}
