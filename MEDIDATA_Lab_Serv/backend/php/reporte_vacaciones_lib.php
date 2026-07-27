<?php
/**
 * Librería de reportes de Vacaciones y Permisos.
 * Centraliza la generación de filas para que el endpoint JSON (fetch_reporte.php) y
 * el export a Word (export_reporte_word.php) usen exactamente la misma lógica.
 *
 * Los datos de la solicitud (tablas hr_*) viven centralizados en la BD principal
 * medic9ue_medi_data ($connect), junto con el nombre del colaborador; el departamento
 * se resuelve vía esquema calificado (medic9ue_medi_rrhh_interviews.departaments).
 */

if (!function_exists('medidata_reporte_departamentos_map')) {
    /** @return array<int,string>  id_departamento => nombre */
    function medidata_reporte_departamentos_map(PDO $connect): array
    {
        try {
            $rows = $connect->query("SELECT id, name FROM medic9ue_medi_rrhh_interviews.departaments")->fetchAll(PDO::FETCH_ASSOC);
            $map = [];
            foreach ($rows as $r) {
                $map[(int) $r['id']] = (string) $r['name'];
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('medidata_reporte_empleados_map')) {
    /**
     * @return array<int,array{nombre:string,codigo:string,id_departamento:?int,departamento:string}>
     *         indexado por id_user
     */
    function medidata_reporte_empleados_map(PDO $connect): array
    {
        $deptMap = medidata_reporte_departamentos_map($connect);
        $sql = "
            SELECT id_user, nombre, codigo, id_departamento FROM (
                SELECT id_user, CONCAT(nomadm,' ',apeadm) AS nombre, numide AS codigo, id_departamento FROM staff_administrative WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nodoc,' ',apdoc), ceddoc, id_departamento FROM doctor WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nomnur,' ',apenur), numide, id_departamento FROM nurse WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nomsg,' ',apesg), numide, id_departamento FROM staff_general_services WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nommf,' ',apemf), numide, id_departamento FROM staff_medifarma WHERE id_user IS NOT NULL
            ) s
        ";
        $map = [];
        foreach ($connect->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $uid = (int) $r['id_user'];
            $idDep = ($r['id_departamento'] !== null && $r['id_departamento'] !== '') ? (int) $r['id_departamento'] : null;
            $map[$uid] = [
                'nombre'          => trim((string) $r['nombre']),
                'codigo'          => (string) ($r['codigo'] ?? ''),
                'id_departamento' => $idDep,
                'departamento'    => $idDep !== null ? ($deptMap[$idDep] ?? '—') : '—',
            ];
        }
        return $map;
    }
}

if (!function_exists('medidata_reporte_estado_label')) {
    function medidata_reporte_estado_label(string $st): string
    {
        $m = [
            'Pending'     => 'Pendiente',
            'In_Progress' => 'En proceso',
            'Approved'    => 'Aprobada',
            'Rejected'    => 'Rechazada',
            'Cancelled'   => 'Cancelada',
        ];
        return $m[$st] ?? $st;
    }
}

if (!function_exists('medidata_absence_payroll_classification')) {
    /**
     * Clasificación para nómina/planilla derivada del tipo de ausencia.
     * No existe módulo de planilla; esta clasificación se deriva de category + is_paid +
     * is_cash_payout para que el área de planilla la consuma vía reporte/export.
     */
    function medidata_absence_payroll_classification(string $category, $is_paid, $deducts_vacation, $is_cash_payout): string
    {
        if (!empty($is_cash_payout)) {
            return 'Vacaciones — pago en efectivo';
        }
        switch ($category) {
            case 'Vacation':      return 'Vacaciones (con goce)';
            case 'Medical_Leave': return 'Incapacidad';
            case 'License':       return !empty($is_paid) ? 'Licencia con goce' : 'Licencia sin goce';
            case 'Permission':    return !empty($is_paid) ? 'Permiso con goce' : 'Permiso sin goce';
            default:              return !empty($is_paid) ? 'Con goce' : 'Sin goce';
        }
    }
}

if (!function_exists('medidata_reporte_generar')) {
    /**
     * Genera un reporte según los filtros. Devuelve columnas + filas listas para tabla/export.
     *
     * $f admite: tipo_reporte (solicitudes|vacaciones_programadas|incapacidades|saldos),
     *            user_id, id_departamento, estado, type_id, desde (Y-m-d), hasta (Y-m-d).
     *
     * @return array{titulo:string, columns:array<int,array{title:string,data:string}>, rows:array<int,array<string,mixed>>}
     */
    function medidata_reporte_generar(PDO $connect, array $f): array
    {
        $tipo = $f['tipo_reporte'] ?? 'solicitudes';
        $empMap = medidata_reporte_empleados_map($connect);
        $filtroDep = (isset($f['id_departamento']) && $f['id_departamento'] !== '') ? (int) $f['id_departamento'] : null;
        $filtroUser = (isset($f['user_id']) && $f['user_id'] !== '') ? (int) $f['user_id'] : null;

        // ---- Reporte de SALDOS (vacaciones pendientes por colaborador) ----
        if ($tipo === 'saldos') {
            $sql = "
                SELECT user_id,
                       SUM(CASE WHEN transaction_type IN ('Annual_Accrual','Manual_HR_Adjustment') THEN affected_days ELSE 0 END) AS otorgados,
                       SUM(CASE WHEN transaction_type = 'Vacation_Consumption' THEN ABS(affected_days) ELSE 0 END) AS disfrutados,
                       SUM(CASE WHEN transaction_type = 'Cash_Payout' THEN ABS(affected_days) ELSE 0 END) AS pagados,
                       SUM(affected_days) AS pendientes
                FROM hr_vacation_transactions
                GROUP BY user_id
            ";
            $rows = [];
            foreach ($connect->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $t) {
                $uid = (int) $t['user_id'];
                $emp = $empMap[$uid] ?? null;
                if ($filtroUser !== null && $uid !== $filtroUser) continue;
                if ($filtroDep !== null && (!$emp || $emp['id_departamento'] !== $filtroDep)) continue;
                $rows[] = [
                    'colaborador'  => $emp['nombre'] ?? ('Usuario ' . $uid),
                    'departamento' => $emp['departamento'] ?? '—',
                    'otorgados'    => round((float) $t['otorgados'], 2),
                    'disfrutados'  => round((float) $t['disfrutados'], 2),
                    'pagados'      => round((float) $t['pagados'], 2),
                    'pendientes'   => round((float) $t['pendientes'], 2),
                ];
            }
            usort($rows, fn($a, $b) => strcmp($a['colaborador'], $b['colaborador']));
            return [
                'titulo'  => 'Saldos de vacaciones (pendientes)',
                'columns' => [
                    ['title' => 'Colaborador',  'data' => 'colaborador'],
                    ['title' => 'Departamento', 'data' => 'departamento'],
                    ['title' => 'Otorgados',    'data' => 'otorgados'],
                    ['title' => 'Disfrutados',  'data' => 'disfrutados'],
                    ['title' => 'Pagados',      'data' => 'pagados'],
                    ['title' => 'Pendientes',   'data' => 'pendientes'],
                ],
                'rows' => $rows,
            ];
        }

        // ---- Reportes basados en solicitudes ----
        $esIncapacidad = ($tipo === 'incapacidades');
        $esNomina      = ($tipo === 'nomina');

        $where = [];
        $params = [];
        if ($filtroUser !== null) { $where[] = 'r.user_id = ?';   $params[] = $filtroUser; }
        if (!empty($f['type_id'])) { $where[] = 'r.type_id = ?';  $params[] = (int) $f['type_id']; }
        if (!empty($f['estado']) && !$esNomina) { $where[] = 'r.request_status = ?'; $params[] = $f['estado']; }
        if (!empty($f['desde']))   { $where[] = 'r.end_date >= ?'; $params[] = $f['desde']; }
        if (!empty($f['hasta']))   { $where[] = 'r.start_date <= ?'; $params[] = $f['hasta']; }

        if ($esIncapacidad) {
            $where[] = "t.category = 'Medical_Leave'";
        } elseif ($tipo === 'vacaciones_programadas') {
            $where[] = "t.category = 'Vacation'";
            $where[] = "r.request_status IN ('Pending','In_Progress','Approved')";
            if (empty($f['desde']) && empty($f['hasta'])) {
                $where[] = "r.end_date >= CURDATE()"; // futuras si no se indica rango
            }
        } elseif ($esNomina) {
            // Para nómina solo interesan las ausencias efectivamente aprobadas.
            $where[] = "r.request_status = 'Approved'";
        }

        $sql = "
            SELECT r.request_id, r.user_id, r.start_date, r.end_date, r.start_time, r.end_time,
                   r.days_amount, r.request_status, r.issuing_institution, r.medical_leave_number,
                   r.is_cash_payout, r.created_at, t.name AS type_name, t.category, t.is_paid, t.deducts_vacation
            FROM hr_absence_requests r
            JOIN hr_absence_types t ON r.type_id = t.type_id
        ";
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY r.start_date DESC';

        $stmt = $connect->prepare($sql);
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $uid = (int) $r['user_id'];
            $emp = $empMap[$uid] ?? null;
            if ($filtroDep !== null && (!$emp || $emp['id_departamento'] !== $filtroDep)) continue;

            $row = [
                'colaborador'  => $emp['nombre'] ?? ('Usuario ' . $uid),
                'departamento' => $emp['departamento'] ?? '—',
                'tipo'         => (string) $r['type_name'],
                'desde'        => (string) $r['start_date'],
                'hasta'        => (string) $r['end_date'],
                'dias'         => round((float) $r['days_amount'], 2),
                'estado'       => medidata_reporte_estado_label((string) $r['request_status']),
            ];
            if ($esIncapacidad) {
                $row['institucion'] = (string) ($r['issuing_institution'] ?? '');
                $row['numero']      = (string) ($r['medical_leave_number'] ?? '');
            }
            if ($esNomina) {
                $row['clasificacion'] = medidata_absence_payroll_classification(
                    (string) $r['category'],
                    $r['is_paid'] ?? 0,
                    $r['deducts_vacation'] ?? 0,
                    $r['is_cash_payout'] ?? 0
                );
            }
            $rows[] = $row;
        }

        $columns = [
            ['title' => 'Colaborador',  'data' => 'colaborador'],
            ['title' => 'Departamento', 'data' => 'departamento'],
            ['title' => 'Tipo',         'data' => 'tipo'],
            ['title' => 'Desde',        'data' => 'desde'],
            ['title' => 'Hasta',        'data' => 'hasta'],
            ['title' => 'Días',         'data' => 'dias'],
            ['title' => 'Estado',       'data' => 'estado'],
        ];
        $titulo = 'Solicitudes de ausencia';
        if ($esIncapacidad) {
            $titulo = 'Incapacidades';
            $columns[] = ['title' => 'Institución emisora', 'data' => 'institucion'];
            $columns[] = ['title' => 'N.º incapacidad',      'data' => 'numero'];
        } elseif ($tipo === 'vacaciones_programadas') {
            $titulo = 'Vacaciones programadas';
        } elseif ($esNomina) {
            $titulo = 'Clasificación para nómina';
            // Insertar la clasificación como 4.ª columna (después de Tipo).
            array_splice($columns, 3, 0, [['title' => 'Clasificación nómina', 'data' => 'clasificacion']]);
        }

        return ['titulo' => $titulo, 'columns' => $columns, 'rows' => $rows];
    }
}
