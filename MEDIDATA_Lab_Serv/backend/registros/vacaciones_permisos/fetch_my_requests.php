<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    global $connect_hr_leaves;
    if (!$connect_hr_leaves) throw new Exception("Sin conexión.");

    $user_id = $_SESSION['id'];

    $sql = "
        SELECT 
            r.request_id,
            t.name as type_name,
            r.start_date,
            r.end_date,
            r.start_time,
            r.end_time,
            r.days_amount,
            r.request_status,
            r.comments,
            (
                SELECT comments 
                FROM hr_absence_approval_logs h 
                WHERE h.request_id = r.request_id 
                ORDER BY step_order DESC LIMIT 1
            ) as last_comment,
            (
                SELECT decision_status 
                FROM hr_absence_approval_logs h 
                WHERE h.request_id = r.request_id 
                ORDER BY step_order DESC LIMIT 1
            ) as last_action
        FROM hr_absence_requests r
        JOIN hr_absence_types t ON r.type_id = t.type_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ";

    $stmt = $connect_hr_leaves->prepare($sql);
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($requests as $req) {
        $r_status = $req['request_status'];
        $badge_class = 'badge ';
        $status_es = '';
        
        switch ($r_status) {
            case 'Pending': 
                $badge_class .= 'bg-warning text-dark'; 
                $status_es = 'Pendiente'; 
                break;
            case 'Approved': 
                $badge_class .= 'bg-success'; 
                $status_es = 'Aprobada'; 
                break;
            case 'Rejected': 
                $badge_class .= 'bg-danger'; 
                $status_es = 'Rechazada'; 
                break;
            default: 
                $badge_class .= 'bg-secondary'; 
                $status_es = $r_status; 
                break;
        }

        $status_badge = "<span class='{$badge_class}'>{$status_es}</span>";
        
        $fechas = $req['start_date'];
        if ($req['end_date'] != $req['start_date']) {
            $fechas .= " al " . $req['end_date'];
        }
        
        if ($req['start_time'] && $req['end_time']) {
            $fechas .= " (" . date('H:i', strtotime($req['start_time'])) . " - " . date('H:i', strtotime($req['end_time'])) . ")";
        }

        $data[] = [
            'type_name' => $req['type_name'],
            'colaborador' => $_SESSION['name'] ?? 'Usuario',
            'fechas' => $fechas,
            'days_amount' => round((float)$req['days_amount'], 2),
            'status' => $status_badge,
            'comments' => $req['comments'],
            'last_comment' => $req['last_comment'] ?? '-'
        ];
    }

    echo json_encode(['data' => $data]);

} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
?>
