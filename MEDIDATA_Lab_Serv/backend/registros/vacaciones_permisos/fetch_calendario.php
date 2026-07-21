<?php
require_once '../../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol'])) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($connect_hr_leaves)) throw new Exception("Sin conexión.");
    
    // Fetch approved absences for the calendar
    // In FullCalendar we need title, start, end, className, etc.
    $stmt = $connect_hr_leaves->query("
        SELECT r.request_id, r.user_id, r.start_date, r.end_date, r.start_time, r.end_time, t.category, t.name as type_name 
        FROM hr_absence_requests r 
        JOIN hr_absence_types t ON r.type_id = t.type_id 
        WHERE r.request_status = 'Approved'
    ");
    
    $events = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        // Resolve user name
        $stmt_user = $pdo->prepare("SELECT CONCAT(nomadm, ' ', apeadm) as name FROM staff_administrative WHERE id_user = ? LIMIT 1");
        $stmt_user->execute([$row['user_id']]);
        $name = $stmt_user->fetchColumn();
        if(!$name) {
            $stmt_u = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) as name FROM users WHERE id = ? LIMIT 1");
            $stmt_u->execute([$row['user_id']]);
            $name = $stmt_u->fetchColumn() ?: 'Usuario ID '.$row['user_id'];
        }
        
        $title = $name . ' - ' . $row['type_name'];
        $className = 'bg-info';
        if($row['category'] == 'Vacation') $className = 'bg-success';
        if($row['category'] == 'Medical_Leave') $className = 'bg-danger';
        
        $start = $row['start_date'];
        $end = $row['end_date'];
        
        // Fullcalendar end date is exclusive for all-day events, so we add 1 day if it's all day
        if($row['start_time'] && $row['end_time']){
            $start .= 'T' . $row['start_time'];
            $end .= 'T' . $row['end_time'];
            $allDay = false;
        } else {
            $end = date('Y-m-d', strtotime($end . ' +1 day'));
            $allDay = true;
        }

        $events[] = [
            'id' => 'abs_'.$row['request_id'],
            'title' => $title,
            'start' => $start,
            'end' => $end,
            'allDay' => $allDay,
            'className' => $className,
            'type' => $row['category']
        ];
    }
    
    echo json_encode($events);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
