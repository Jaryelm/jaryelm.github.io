<?php
include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/staff_user_link_lib.php';
header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_POST['id'] ?? 0);
$state = isset($_POST['state']) ? (int) $_POST['state'] : null;

if ($id <= 0 || ($state !== 0 && $state !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida.']);
    exit;
}

try {
    $stmt = $connect->prepare('UPDATE users SET state = :state WHERE id = :id LIMIT 1');
    $ok = $stmt->execute([':state' => (string) $state, ':id' => $id]);

    // Caso médico/ficha + usuario: al desactivar el usuario se desactiva tambien su ficha de personal.
    medidata_link_staff_state_from_user($connect, $id, $state);

    if ($ok) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
    }
} catch (Throwable $e) {
    error_log('toggle_user_state: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado.']);
}
