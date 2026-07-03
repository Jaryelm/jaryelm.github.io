<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = intval($_GET['idpa'] ?? 0);

    if (!$idpa) {
        throw new Exception("ID del paciente no proporcionado.");
    }

    // Obtener todos los registros de procedimientos de este paciente
    $stmt = $connect->prepare("SELECT turno, procedimiento_realizado, procesado_por, fecha_registro FROM procedimientos WHERE idpa = :idpa ORDER BY fecha_registro DESC");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode(["success" => true, "data" => $result]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontraron datos para este paciente."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
