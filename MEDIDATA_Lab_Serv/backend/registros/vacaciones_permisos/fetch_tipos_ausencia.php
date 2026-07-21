<?php
require_once '../../bd/Conexion.php';

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;
    
    $stmt = $pdo->prepare("SELECT type_id, code, name, category, is_paid, deducts_vacation, requires_document, requires_special_auth, status FROM medic9ue_hr_leaves.hr_absence_types ORDER BY name ASC");
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
