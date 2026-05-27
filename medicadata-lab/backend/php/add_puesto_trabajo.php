<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['add_puesto'])) {
    $id_positions = $_POST['id_position']; 
    $description = $_POST['description'];
    $requirements = $_POST['requirements'];
    $created_by = $_POST['created_by'];

    try {
        $sql = "INSERT INTO positions_details (id_positions, description, requirements, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([$id_positions, $description, $requirements, $created_by]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Puesto registrado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar el puesto']);
        }
    } catch (Exception $e) {
        error_log("Error add_puesto_trabajo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
