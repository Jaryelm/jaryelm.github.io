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
    if (!$connect) throw new Exception("No db connection");
    
    // Kardex: Log de transacciones de días de vacaciones por empleado
    $sql = "
        SELECT 
            k.transaction_id,
            k.user_id,
            u.name AS user_name,
            k.transaction_type,
            k.affected_days AS days_amount,
            k.transaction_date AS created_at,
            k.description AS comments
        FROM medic9ue_hr_leaves.hr_vacation_transactions k
        LEFT JOIN users u ON k.user_id = u.id
        ORDER BY k.transaction_date DESC
    ";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
