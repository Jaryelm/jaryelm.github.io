<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['id']) && isset($_POST['state'])) {
    $id = (int)$_POST['id'];
    $stateInt = (int)$_POST['state'];
    $status = ($stateInt === 1) ? 'Activo' : 'Inactivo';

    try {
        $query = "UPDATE departaments SET status = ? WHERE id = ? LIMIT 1";
        $stmt = $connect_rrhh->prepare($query);
        $result = $stmt->execute([$status, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado']);
        }
    } catch (Exception $e) {
        error_log("Error toggle_departament_state: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>