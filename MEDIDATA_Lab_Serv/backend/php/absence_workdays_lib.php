<?php
/**
 * Cálculo de días hábiles para solicitudes de ausencia (día completo).
 *
 * Un "día hábil" es un día que:
 *   1) El colaborador labora según su horario ACTIVO (schedule_details con turno válido), y
 *   2) NO es feriado (hr_holiday_calendar).
 *
 * De este modo una solicitud del 2026-07-24 al 2026-07-30 NO cuenta el domingo
 * (si el horario no incluye domingo) ni los feriados que caigan en el rango.
 *
 * Requiere:
 *   - $connect            (BD principal: staff_* / doctor / nurse y, vía esquema calificado, schedules)
 *   - $connect_hr_leaves  (BD de ausencias: hr_holiday_calendar)  — opcional
 */

require_once __DIR__ . '/schedule_lib.php';

if (!function_exists('medidata_absence_weekday_codes')) {
    /** Índice date('w') (0=Dom..6=Sáb) -> código usado en schedule_details. */
    function medidata_absence_weekday_codes(): array
    {
        return ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];
    }
}

if (!function_exists('medidata_absence_resolve_schedule_id')) {
    /**
     * Resuelve el id_horario (horario activo) del colaborador a partir de su id_user,
     * buscando en las tablas de personal de la BD principal.
     */
    function medidata_absence_resolve_schedule_id(PDO $connect, int $user_id): ?int
    {
        $stmt = $connect->prepare("
            SELECT id_horario FROM (
                SELECT id_horario FROM staff_administrative     WHERE id_user = ?
                UNION SELECT id_horario FROM doctor             WHERE id_user = ?
                UNION SELECT id_horario FROM nurse              WHERE id_user = ?
                UNION SELECT id_horario FROM staff_general_services WHERE id_user = ?
                UNION SELECT id_horario FROM staff_medifarma    WHERE id_user = ?
            ) h WHERE id_horario IS NOT NULL LIMIT 1
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row && !empty($row['id_horario'])) ? (int) $row['id_horario'] : null;
    }
}

if (!function_exists('medidata_absence_working_weekday_set')) {
    /**
     * Devuelve el conjunto de códigos de día (Do,Lu,...) en los que el horario tiene
     * un turno válido (entrada/salida presentes y duración > 0).
     *
     * @return array<string,bool>  mapa código => true
     */
    function medidata_absence_working_weekday_set(PDO $connect, int $schedule_id): array
    {
        $stmt = $connect->prepare("
            SELECT day, entry_time, exit_time
            FROM medic9ue_medi_rrhh_interviews.schedule_details
            WHERE id_schedule = ?
        ");
        $stmt->execute([$schedule_id]);

        $set = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $entryRaw = trim((string) ($d['entry_time'] ?? ''));
            $exitRaw  = trim((string) ($d['exit_time'] ?? ''));
            if ($entryRaw === '' || $exitRaw === '') {
                continue;
            }
            $shift = medidata_schedule_shift_minutes(
                medidata_schedule_time_to_minutes($entryRaw),
                medidata_schedule_time_to_minutes($exitRaw)
            );
            if ($shift > 0) {
                $set[(string) $d['day']] = true;
            }
        }
        return $set;
    }
}

if (!function_exists('medidata_absence_holiday_set')) {
    /**
     * Feriados dentro del rango [start,end] como mapa 'Y-m-d' => true.
     * Si la conexión/tabla no está disponible, devuelve vacío (no bloquea el cálculo).
     */
    function medidata_absence_holiday_set(?PDO $connect_hr_leaves, string $start_date, string $end_date): array
    {
        if (!$connect_hr_leaves) {
            return [];
        }
        try {
            $stmt = $connect_hr_leaves->prepare(
                "SELECT `date` FROM hr_holiday_calendar WHERE `date` BETWEEN ? AND ?"
            );
            $stmt->execute([$start_date, $end_date]);
            $set = [];
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $d) {
                $set[substr((string) $d, 0, 10)] = true;
            }
            return $set;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('medidata_absence_count_working_days')) {
    /**
     * Cuenta los días hábiles (según horario activo, excluyendo feriados) entre dos fechas,
     * ambas inclusive.
     *
     * @return array{
     *   days:int, has_schedule:bool, schedule_id:?int,
     *   working_codes:array<int,string>, holidays:array<int,string>,
     *   nonworking:array<int,string>, total_calendar:int
     * }
     */
    function medidata_absence_count_working_days(
        PDO $connect,
        ?PDO $connect_hr_leaves,
        int $user_id,
        string $start_date,
        string $end_date
    ): array {
        $codes = medidata_absence_weekday_codes();

        $schedule_id  = medidata_absence_resolve_schedule_id($connect, $user_id);
        $working_set  = $schedule_id ? medidata_absence_working_weekday_set($connect, $schedule_id) : [];
        $has_schedule = $schedule_id !== null && !empty($working_set);
        $holiday_set  = medidata_absence_holiday_set($connect_hr_leaves, $start_date, $end_date);

        $cursor = strtotime($start_date);
        $limit  = strtotime($end_date);

        $days = 0;
        $total = 0;
        $nonworking = [];
        if ($cursor !== false && $limit !== false && $cursor <= $limit) {
            $oneDay = 86400;
            for ($t = $cursor; $t <= $limit; $t += $oneDay) {
                $total++;
                $ymd  = date('Y-m-d', $t);
                $code = $codes[(int) date('w', $t)];

                $isHoliday = isset($holiday_set[$ymd]);
                // Sin horario activo: se asume día laborable (sólo se descuentan feriados),
                // preservando un comportamiento seguro para colaboradores sin horario asignado.
                $isWorkday = $has_schedule ? isset($working_set[$code]) : true;

                if ($isWorkday && !$isHoliday) {
                    $days++;
                } else {
                    $nonworking[] = $ymd;
                }
            }
        }

        return [
            'days'           => $days,
            'has_schedule'   => $has_schedule,
            'schedule_id'    => $schedule_id,
            'working_codes'  => array_keys($working_set),
            'holidays'       => array_keys($holiday_set),
            'nonworking'     => $nonworking,
            'total_calendar' => $total,
        ];
    }
}
