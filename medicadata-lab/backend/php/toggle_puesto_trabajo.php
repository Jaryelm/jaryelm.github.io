<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['id']) || !isset($_POST['deleted'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();
$id = (int) $_POST['id'];
$deleted = (int) $_POST['deleted'] ? 1 : 0;

try {
    $stmt = $pdo->prepare('UPDATE positions_details SET deleted = ? WHERE id = ?');
    $ok = $stmt->execute([$deleted, $id]);
    echo json_encode([
        'success' => (bool) $ok,
        'message' => $ok ? 'Estado actualizado' : 'No se pudo actualizar',
    ]);
} catch (Throwable $e) {
    error_log('toggle_puesto_trabajo: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
