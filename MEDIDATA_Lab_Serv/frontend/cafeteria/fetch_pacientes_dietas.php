<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    $sql = "
    SELECT 
        patients.nompa AS nombre_paciente,
        patients.numhs AS dni_paciente, 
        patients.apepa AS apellido_paciente, 
        consult.habitacion_no AS habitacion, 
        control_dieta.created_at AS fecha_hora, 
        control_dieta.turno, 
        control_dieta.tipo_dieta, 
        control_dieta.procesado_por, 
        control_dieta.estado,
        control_dieta.id AS id_dieta
    FROM control_dieta
    INNER JOIN consult ON control_dieta.idpa = consult.idpa
    INNER JOIN patients ON consult.idpa = patients.idpa
    GROUP BY control_dieta.id
    ORDER BY control_dieta.created_at DESC
";
    $stmt = $connect->prepare($sql);
    $stmt->execute();

    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($records)) {
        echo json_encode(['error' => 'No se encontraron registros en el control de dieta.']);
        exit;
    }

    echo json_encode(['data' => $records]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
?>
