<?php
/**
 * Eventos del calendario del DEPARTAMENTO para un jefe: ausencias aprobadas de su equipo
 * + feriados. RRHH/Admin que no encabecen departamento ven todas las ausencias aprobadas.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/jefe_lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión.');

    $uid = (int) $_SESSION['id'];
    $isAdminHr = in_array($_SESSION['rol'] ?? '', ['Administrador', 'Recursos_Humanos'], true);
    $equipo = isset($connect) && $connect ? medidata_jefe_equipo_user_ids($connect, $uid) : [];

    // Si no es jefe ni admin/hr, no ve nada.
    if (!$equipo && !$isAdminHr) {
        echo json_encode([]);
        exit;
    }

    // Mapa id_user => nombre (BD principal).
    $nombres = [];
    if (isset($connect) && $connect) {
        try {
            foreach ($connect->query("SELECT id, name, username FROM users")->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $nom = trim((string) ($u['name'] ?? ''));
                if ($nom === '') $nom = trim((string) ($u['username'] ?? ''));
                $nombres[(int) $u['id']] = $nom;
            }
        } catch (Throwable $e) { /* fallback al id */ }
    }

    $colores = ['Vacation' => '#28a745', 'Permission' => '#17a2b8', 'Medical_Leave' => '#dc3545', 'License' => '#6f42c1'];
    $events = [];

    // Ausencias aprobadas (filtradas por equipo si el usuario es jefe).
    $sql = "
        SELECT r.request_id, r.user_id, r.start_date, r.end_date, r.start_time, r.end_time, t.category, t.name AS type_name
        FROM hr_absence_requests r
        JOIN hr_absence_types t ON r.type_id = t.type_id
        WHERE r.request_status = 'Approved'
    ";
    $params = [];
    if ($equipo) {
        $in = implode(',', array_fill(0, count($equipo), '?'));
        $sql .= " AND r.user_id IN ($in)";
        $params = $equipo;
    }
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nombre = $nombres[(int) $row['user_id']] ?? ('Usuario ' . $row['user_id']);
        $start = $row['start_date'];
        $end = $row['end_date'];
        if ($row['start_time'] && $row['end_time']) {
            $start .= 'T' . $row['start_time'];
            $end .= 'T' . $row['end_time'];
            $allDay = false;
        } else {
            $end = date('Y-m-d', strtotime($end . ' +1 day'));
            $allDay = true;
        }
        $events[] = [
            'id'     => 'abs_' . $row['request_id'],
            'title'  => $nombre . ' - ' . $row['type_name'],
            'start'  => $start,
            'end'    => $end,
            'allDay' => $allDay,
            'color'  => $colores[$row['category']] ?? '#6c757d',
            'type'   => $row['category'],
        ];
    }

    // Feriados.
    try {
        foreach ($connect->query("SELECT holiday_id, `date`, description FROM hr_holiday_calendar")->fetchAll(PDO::FETCH_ASSOC) as $h) {
            $events[] = [
                'id'        => 'hol_' . $h['holiday_id'],
                'title'     => 'Feriado: ' . $h['description'],
                'start'     => $h['date'],
                'allDay'    => true,
                'color'     => '#f39c12',
                'rendering' => 'background',
                'type'      => 'Holiday',
            ];
        }
    } catch (Throwable $e) { /* sin feriados */ }

    echo json_encode($events);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
