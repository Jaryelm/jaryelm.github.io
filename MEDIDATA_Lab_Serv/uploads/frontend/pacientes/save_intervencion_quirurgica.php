<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

try {
    if (!isset($_POST['idpa']) || empty($_POST['consistente_en']) || empty($_POST['intervencion_quirurgica'])) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    $idpa = intval($_POST['idpa']);
    $consistente_en = trim($_POST['consistente_en']);
    $intervencion_quirurgica = trim($_POST['intervencion_quirurgica']);
    $fecha_registro = date('Y-m-d H:i:s'); // Hora local en PHP

    // Verificar si ya existe un registro
    $checkStmt = $connect->prepare("SELECT COUNT(*) AS total FROM autorizacion_quirurgica WHERE idpa = :idpa");
    $checkStmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        echo json_encode([
            "success" => false,
            "type" => "warning",
            "message" => "Ya existe una autorización quirúrgica registrada para este paciente. No se puede duplicar."
        ]);
        exit;
    }

    // Insertar datos
    $stmt = $connect->prepare("INSERT INTO autorizacion_quirurgica (idpa, consistente_en, intervencion_quirurgica, fecha_registro) VALUES (:idpa, :consistente_en, :intervencion_quirurgica, :fecha_registro)");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':consistente_en', $consistente_en, PDO::PARAM_STR);
    $stmt->bindParam(':intervencion_quirurgica', $intervencion_quirurgica, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_registro', $fecha_registro);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Autorización quirúrgica guardada correctamente."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "type" => "error", "message" => $e->getMessage()]);
}
