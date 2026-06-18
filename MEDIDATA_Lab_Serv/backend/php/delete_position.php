<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    try {
        $sql = "DELETE FROM positions WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $result = $stmt->execute([$id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Posición eliminada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar la posición']);
        }
    } catch (Exception $e) {
        error_log("Error delete_position: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
