<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = intval($_GET['idpa'] ?? 0);

    if (!$idpa) {
        throw new Exception("ID del paciente no proporcionado.");
    }

    $stmt = $connect->prepare("SELECT motivo, diagnostico FROM solicitud_alta WHERE idpa = :idpa LIMIT 1");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo json_encode(["success" => true, "motivo" => $result['motivo'], "diagnostico" => $result['diagnostico']]);
    } else {
        echo json_encode(["success" => false, "message" => "No se encontraron datos para este paciente."]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
