<?php
require('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = intval($_GET['idpa'] ?? 0);

    if (!$idpa) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $stmt = $connect->prepare("SELECT COUNT(*) as total FROM autorizacion_quirurgica WHERE idpa = :idpa AND consistente_en IS NOT NULL AND intervencion_quirurgica IS NOT NULL");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $hasData = $result['total'] > 0;

    echo json_encode(["success" => true, "hasData" => $hasData]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
