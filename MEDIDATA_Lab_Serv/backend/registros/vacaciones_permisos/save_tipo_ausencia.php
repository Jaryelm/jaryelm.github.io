<?php
require_once '../../bd/Conexion.php';

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;
    
    $type_id = $_POST['type_id'] ?? '';
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $is_paid = isset($_POST['is_paid']) ? 1 : 0;
    $deducts_vacation = isset($_POST['deducts_vacation']) ? 1 : 0;
    $requires_document = isset($_POST['requires_document']) ? 1 : 0;
    
    if (empty($code) || empty($name) || empty($category)) {
        echo json_encode(['success' => false, 'message' => 'El código, nombre y categoría son obligatorios.']);
        exit;
    }
    
    if (empty($type_id)) {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO medic9ue_hr_leaves.hr_absence_types (code, name, category, is_paid, deducts_vacation, requires_document, status) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$code, $name, $category, $is_paid, $deducts_vacation, $requires_document]);
        echo json_encode(['success' => true, 'message' => 'Tipo de ausencia agregado correctamente.']);
    } else {
        // Update
        $stmt = $pdo->prepare("UPDATE medic9ue_hr_leaves.hr_absence_types SET code = ?, name = ?, category = ?, is_paid = ?, deducts_vacation = ?, requires_document = ? WHERE type_id = ?");
        $stmt->execute([$code, $name, $category, $is_paid, $deducts_vacation, $requires_document, $type_id]);
        echo json_encode(['success' => true, 'message' => 'Tipo de ausencia actualizado correctamente.']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>
