<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['add_vacante'])) {
    $id_position = $_POST['id_position'];
    $benefits = $_POST['benefits'];
    $init_date = $_POST['init_date'];
    $end_date = $_POST['end_date'];
    $created_by = $_POST['created_by'];

    try {
        $sql = "INSERT INTO vacant_positions (id_position, benefits, init_date, end_date, created_by) VALUES (?, ?, ?, ?, ?)";
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([$id_position, $benefits, $init_date, $end_date, $created_by]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Vacante registrada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar la vacante']);
        }
    } catch (Exception $e) {
        error_log("Error add_vacante_trabajo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
