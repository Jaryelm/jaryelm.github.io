<?php
include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/staff_user_link_lib.php';
header('Content-Type: application/json; charset=utf-8');

$idodc = (int) ($_POST['id'] ?? $_POST['idodc'] ?? 0);
$state = isset($_POST['state']) ? (int) $_POST['state'] : null;

if ($idodc <= 0 || ($state !== 0 && $state !== 1)) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida.']);
    exit;
}

try {
    $stmt = $connect->prepare('UPDATE doctor SET state = :state WHERE idodc = :idodc LIMIT 1');
    $ok = $stmt->execute([':state' => (string) $state, ':idodc' => $idodc]);

    // Caso médico+usuario: al desactivar el médico se retira tambien el acceso del usuario enlazado.
    medidata_link_user_state_from_staff($connect, 'doctor', 'idodc', $idodc, $state);

    if ($ok && $stmt->rowCount() >= 0) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
    }
} catch (Throwable $e) {
    error_log('toggle_doctor_state: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
