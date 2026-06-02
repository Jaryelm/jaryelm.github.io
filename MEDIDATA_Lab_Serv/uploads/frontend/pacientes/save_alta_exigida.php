<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

try {
    if (!isset($_POST['idpa']) || !isset($_POST['motivo']) || !isset($_POST['diagnostico'])) {
        throw new Exception("Faltan datos obligatorios.");
    }

    $idpa = intval($_POST['idpa']);
    $motivo = trim($_POST['motivo']);
    $diagnostico = trim($_POST['diagnostico']);

    // Verificar si ya existe un registro para este paciente
    $checkStmt = $connect->prepare("SELECT COUNT(*) AS total FROM solicitud_alta WHERE idpa = :idpa");
    $checkStmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $checkStmt->execute();
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($result['total'] > 0) {
        // Enviar una advertencia clara al usuario
        echo json_encode([
            "success" => false,
            "type" => "warning", // Tipo de alerta
            "message" => "Ya existe una solicitud registrada para este paciente. Este registro es único y no puede duplicarse. Si necesitas modificarlo, contacta al soporte TI de MEDICASA."
        ]);
        exit;
    }

    // Insertar los datos en la tabla si no existe un registro previo
    $stmt = $connect->prepare("INSERT INTO solicitud_alta (idpa, motivo, diagnostico, fecha_registro) VALUES (:idpa, :motivo, :diagnostico, NOW())");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':motivo', $motivo, PDO::PARAM_STR);
    $stmt->bindParam(':diagnostico', $diagnostico, PDO::PARAM_STR);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Solicitud guardada correctamente."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "type" => "error", "message" => $e->getMessage()]);
}
