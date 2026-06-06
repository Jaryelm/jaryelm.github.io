<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$pdo = medidata_rrhh_json_require();
$id = (int)$_POST['id'];

try {
    // Soft delete toggle
    $stmt = $pdo->prepare("UPDATE salary_levels SET deleted = 1 - deleted WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Estado del nivel salarial actualizado' : 'No se pudo actualizar el estado'
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>