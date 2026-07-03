<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'];
    if (empty($idpa)) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $sql = "SELECT medicamento_tratamiento, fecha_hora, procesado_por 
            FROM control_medicamentos 
            WHERE idpa = :idpa 
            ORDER BY created_at DESC";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $records]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
