<?php
include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/staff_user_link_lib.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_POST['id'] ?? $_POST['idnur'] ?? 0);
$state = isset($_POST['state']) ? (int) $_POST['state'] : null;

if ($id <= 0 || ($state !== 0 && $state !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida.']);
    exit;
}

try {
    $stmt = $connect->prepare('UPDATE nurse SET state = :state WHERE idnur = :id LIMIT 1');
    $ok = $stmt->execute([':state' => (string) $state, ':id' => $id]);
    medidata_link_user_state_from_staff($connect, 'nurse', 'idnur', $id, $state);
    echo json_encode(['success' => (bool) $ok, 'message' => $ok ? 'Estado actualizado.' : 'No se pudo actualizar el estado.']);
} catch (Throwable $e) {
    error_log('toggle_nurse_state: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cambiar el estado.']);
}
