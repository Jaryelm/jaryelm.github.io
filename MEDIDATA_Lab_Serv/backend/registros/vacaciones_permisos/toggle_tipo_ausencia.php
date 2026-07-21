<?php
require_once '../../bd/Conexion.php';

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;
    
    $type_id = $_POST['type_id'] ?? '';
    $current_status = $_POST['current_status'] ?? 0;
    
    if (empty($type_id)) {
        echo json_encode(['status' => 'error', 'message' => 'ID no proporcionado.']);
        exit;
    }
    
    $new_status = ($current_status == 1) ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE medic9ue_hr_leaves.hr_absence_types SET status = ? WHERE type_id = ?");
    $stmt->execute([$new_status, $type_id]);
    
    echo json_encode(['status' => 'success', 'message' => 'Estado actualizado correctamente.']);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>
