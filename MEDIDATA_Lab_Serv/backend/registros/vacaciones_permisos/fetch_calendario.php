<?php
require_once '../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol'])) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) throw new Exception("Sin conexión.");

    // Mapa id_user => nombre (una sola consulta a la BD principal)
    $nombres = [];
    if (isset($connect) && $connect) {
        $qn = $connect->query("
            SELECT id_user, text FROM (
                SELECT id_user, CONCAT(nomadm, ' ', apeadm) AS text FROM staff_administrative WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nodoc, ' ', apdoc) FROM doctor WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nomnur, ' ', apenur) FROM nurse WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nomsg, ' ', apesg) FROM staff_general_services WHERE id_user IS NOT NULL
                UNION ALL SELECT id_user, CONCAT(nommf, ' ', apemf) FROM staff_medifarma WHERE id_user IS NOT NULL
            ) s WHERE id_user IS NOT NULL
        ");
        foreach ($qn->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $nombres[(int) $r['id_user']] = $r['text'];
        }
    }

    $events = [];

    // Ausencias aprobadas: Vacaciones, Permisos, Incapacidad, Licencias
    $colores = [
        'Vacation'      => '#28a745', // verde
        'Permission'    => '#17a2b8', // teal
        'Medical_Leave' => '#dc3545', // rojo
        'License'       => '#6f42c1', // morado
    ];
    $stmt = $connect_hr_leaves->query("
        SELECT r.request_id, r.user_id, r.start_date, r.end_date, r.start_time, r.end_time, t.category, t.name AS type_name
        FROM hr_absence_requests r
        JOIN hr_absence_types t ON r.type_id = t.type_id
        WHERE r.request_status = 'Approved'
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $name  = $nombres[(int) $row['user_id']] ?? ('Usuario ID ' . $row['user_id']);
        $title = $name . ' - ' . $row['type_name'];
        $color = $colores[$row['category']] ?? '#6c757d';

        $start = $row['start_date'];
        $end   = $row['end_date'];
        if ($row['start_time'] && $row['end_time']) {
            $start .= 'T' . $row['start_time'];
            $end   .= 'T' . $row['end_time'];
            $allDay = false;
        } else {
            // FullCalendar: la fecha fin es exclusiva en eventos all-day
            $end = date('Y-m-d', strtotime($end . ' +1 day'));
            $allDay = true;
        }

        $events[] = [
            'id'      => 'abs_' . $row['request_id'],
            'title'   => $title,
            'start'   => $start,
            'end'     => $end,
            'allDay'  => $allDay,
            'color'   => $color,
            'type'    => $row['category'],
        ];
    }

    // Feriados (día completo, como fondo)
    try {
        $qh = $connect_hr_leaves->query("SELECT holiday_id, `date`, description FROM hr_holiday_calendar");
        foreach ($qh->fetchAll(PDO::FETCH_ASSOC) as $h) {
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
    } catch (Throwable $e) {
        // Si la tabla de feriados no existe/está vacía, se ignora.
    }

    echo json_encode($events);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
