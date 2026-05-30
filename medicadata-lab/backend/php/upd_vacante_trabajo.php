<?php
include_once '../registros/session_check.php';
$conexion_rrhh = $connect_rrhh; // Alias for compatibility

if (isset($_POST['upd_vacante'])) {
    $id = $_POST['id'];
    $id_position = $_POST['id_position'];
    $vacant_name = $_POST['vacant_name'];
    $requesting_department = $_POST['requesting_department'];
    $available_slots = $_POST['available_slots'];
    $reason = $_POST['reason'];
    $priority = $_POST['priority'] ?? 'Media';
    $rrhh_responsible = $_POST['rrhh_responsible'] ?? null;
    $requesting_boss = $_POST['requesting_boss'] ?? null;
    $internal_observations = $_POST['internal_observations'] ?? null;
    $publication_channel = $_POST['publication_channel'] ?? null;
    $benefits = $_POST['benefits'];
    $init_date = $_POST['init_date'];
    $end_date = $_POST['end_date'];
    $updated_by = $name;

    try {
        $sql = "UPDATE vacant_positions SET 
                    id_position = ?, vacant_name = ?, requesting_department = ?, 
                    available_slots = ?, reason = ?, priority = ?, 
                    rrhh_responsible = ?, requesting_boss = ?, 
                    internal_observations = ?, publication_channel = ?, 
                    benefits = ?, init_date = ?, end_date = ?, updated_by = ? 
                WHERE id = ?";
        
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([
            $id_position, $vacant_name, $requesting_department,
            $available_slots, $reason, $priority, $rrhh_responsible,
            $requesting_boss, $internal_observations,
            $publication_channel, $benefits, $init_date,
            $end_date, $updated_by, $id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Vacante actualizada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la vacante']);
        }
    } catch (Exception $e) {
        error_log("Error upd_vacante_trabajo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>
<body>
</body>
</html>
