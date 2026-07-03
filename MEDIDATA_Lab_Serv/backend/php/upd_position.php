<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['upd_position'])) {
    $id = (int)$_POST['id'];
    $name_pos = trim($_POST['name_pos']);
    $updated_by = $_POST['updated_by'] ?? $name;

    if (empty($name_pos)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de la posición es obligatorio']);
        exit;
    }

    try {
        // Check if exists in other records
        $check = $connect->prepare("SELECT id FROM positions WHERE name = ? AND id <> ?");
        $check->execute([$name_pos, $id]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Esta posición ya está registrada con otro ID']);
            exit;
        }

        $sql = "UPDATE positions SET name = ?, updated_by = ? WHERE id = ?";
        $stmt = $connect->prepare($sql);
        $result = $stmt->execute([$name_pos, $updated_by, $id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Posición actualizada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la posición']);
        }
    } catch (Exception $e) {
        error_log("Error upd_position: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
