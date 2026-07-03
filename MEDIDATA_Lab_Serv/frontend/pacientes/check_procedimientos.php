<?php
require('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'] ?? null;

    if (!$idpa) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM procedimientos WHERE idpa = :idpa AND turno IS NOT NULL AND procedimiento_realizado IS NOT NULL");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $isComplete = $result['total'] > 0;

    echo json_encode(["success" => true, "hasData" => $isComplete]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
