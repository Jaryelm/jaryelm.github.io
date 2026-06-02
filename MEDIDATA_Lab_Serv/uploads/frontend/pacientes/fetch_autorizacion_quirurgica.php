<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = intval($_GET['idpa'] ?? 0);

    if (!$idpa) {
        throw new Exception("ID del paciente no proporcionado.");
    }

    $stmt = $connect->prepare("SELECT consistente_en, intervencion_quirurgica FROM autorizacion_quirurgica WHERE idpa = :idpa LIMIT 1");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo json_encode([
            "success" => true,
            "consistente_en" => $result['consistente_en'],
            "intervencion_quirurgica" => $result['intervencion_quirurgica']
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontraron datos para este paciente."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
