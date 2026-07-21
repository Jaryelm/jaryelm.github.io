<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    global $connect_hr_leaves, $connect;
    if (!$connect_hr_leaves) throw new Exception("No db connection to hr_leaves");
    if (!$connect) throw new Exception("No db connection to main db");
    
    // As per user rules, referential integrity across DBs is handled at the application layer.
    // Fetch users manually or use a JOIN if the DB user has access to both.
    // Assuming the DB user can query both medic9ue_hr_leaves and the main DB:
    
    $stmt = $connect->prepare("
        SELECT 
            r.request_id,
            r.user_id,
            u.name AS user_name,
            t.name AS type_name,
            r.start_date,
            r.end_date,
            r.days_amount,
            r.request_status,
            r.created_at
        FROM medic9ue_hr_leaves.hr_absence_requests r
        LEFT JOIN medic9ue_hr_leaves.hr_absence_types t ON r.type_id = t.type_id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
