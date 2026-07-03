<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['add_position'])) {
    $name_pos = trim($_POST['name_pos']);
    $created_by = $_POST['created_by'] ?? $name;

    if (empty($name_pos)) {
        echo json_encode(['success' => false, 'message' => 'El nombre de la posición es obligatorio']);
        exit;
    }

    try {
        // Check if exists
        $check = $connect->prepare("SELECT id FROM positions WHERE name = ?");
        $check->execute([$name_pos]);
        if ($check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Esta posición ya está registrada']);
            exit;
        }

        $sql = "INSERT INTO positions (name, created_by) VALUES (?, ?)";
        $stmt = $connect->prepare($sql);
        $result = $stmt->execute([$name_pos, $created_by]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Posición registrada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la posición']);
        }
    } catch (Exception $e) {
        error_log("Error add_position: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
