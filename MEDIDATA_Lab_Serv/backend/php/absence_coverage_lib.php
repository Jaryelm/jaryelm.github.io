<?php
/**
 * Cobertura de ausencias/permisos aprobados para integraciones (p. ej. reloj biométrico).
 * Dado un colaborador y una fecha (y opcionalmente la hora de marca), indica si ese momento
 * está cubierto por una ausencia o permiso APROBADO, para que el control de asistencia no lo
 * trate como falta o tardanza injustificada.
 *
 * Cruce entre BDs en PHP: las solicitudes viven en medic9ue_hr_leaves ($connect_hr_leaves);
 * el vínculo con la marca es por user_id (= users.id, que a su vez resuelve uid_biometrico).
 */

if (!function_exists('medidata_absence_coverage_category_label')) {
    function medidata_absence_coverage_category_label(string $category): string
    {
        $m = [
            'Vacation'      => 'Vacaciones',
            'Permission'    => 'Permiso',
            'Medical_Leave' => 'Incapacidad',
            'License'       => 'Licencia',
        ];
        return $m[$category] ?? 'Ausencia';
    }
}

if (!function_exists('medidata_absence_coverage_fetch')) {
    /**
     * @param int[] $userIds
     * @return array<int, array<int, array<string,mixed>>>  user_id => lista de ausencias aprobadas
     */
    function medidata_absence_coverage_fetch(?PDO $connect_hr_leaves, array $userIds, string $desde, string $hasta): array
    {
        if (!$connect_hr_leaves) return [];
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($v) => $v > 0)));
        if (!$userIds) return [];
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) return [];

        try {
            $in = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "
                SELECT r.user_id, r.start_date, r.end_date, r.start_time, r.end_time,
                       t.category, t.name AS type_name
                FROM hr_absence_requests r
                JOIN hr_absence_types t ON r.type_id = t.type_id
                WHERE r.request_status = 'Approved'
                  AND r.user_id IN ($in)
                  AND r.start_date <= ? AND r.end_date >= ?
            ";
            $params = array_merge($userIds, [$hasta, $desde]);
            $stmt = $connect_hr_leaves->prepare($sql);
            $stmt->execute($params);

            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $map[(int) $r['user_id']][] = $r;
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('medidata_absence_coverage_label')) {
    /**
     * Etiqueta de cobertura para un colaborador en una fecha (y hora opcional de la marca).
     * Devuelve '' si no hay cobertura.
     *
     * @param array<int, array<string,mixed>> $absencesForUser
     */
    function medidata_absence_coverage_label(array $absencesForUser, string $fecha, ?string $horaEntrada = null): string
    {
        if (!$absencesForUser) return '';
        $fTs = strtotime($fecha);
        if ($fTs === false) return '';
        $fecha = date('Y-m-d', $fTs);

        foreach ($absencesForUser as $a) {
            $ini = substr((string) $a['start_date'], 0, 10);
            $fin = substr((string) $a['end_date'], 0, 10);
            if ($fecha < $ini || $fecha > $fin) {
                continue;
            }
            $cat = medidata_absence_coverage_category_label((string) $a['category']);
            $st = $a['start_time'] ?? null;
            $et = $a['end_time'] ?? null;

            // Permiso por horas (mismo día): mostrar la franja.
            if ($st && $et && $ini === $fin && $ini === $fecha) {
                return $cat . ' ' . substr((string) $st, 0, 5) . '–' . substr((string) $et, 0, 5);
            }
            // Ausencia de día completo.
            return $cat;
        }
        return '';
    }
}
