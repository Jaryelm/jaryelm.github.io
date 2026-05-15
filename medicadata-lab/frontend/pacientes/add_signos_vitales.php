<?php
require_once('../../backend/bd/Conexion.php');

date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json');

try {
    if (
        empty($_POST['processed_by']) ||
        empty($_POST['blood_pressure']) || empty($_POST['map_pressure']) || empty($_POST['temperature']) ||
        empty($_POST['heart_rate']) || empty($_POST['respiratory_rate']) || empty($_POST['oxygen_saturation']) ||
        empty($_POST['weight']) || empty($_POST['stature']) || empty($_POST['glucose']) ||
        empty($_POST['idpa'])
    ) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    $processedBy = $_POST['processed_by'];
    $reviewsBy = $_POST['reviews_by'] ?? '';
    $weight = $_POST['weight'];
    $stature = $_POST['stature'];
    $bloodPressure = $_POST['blood_pressure'];
    $mapPressure = $_POST['map_pressure'];
    $temperature = $_POST['temperature'];
    $heartRate = $_POST['heart_rate'];
    $respiratoryRate = $_POST['respiratory_rate'];
    $oxygenSaturation = $_POST['oxygen_saturation'];
    $glucose = $_POST['glucose'];
    $idpa = $_POST['idpa'];

    $sql = "INSERT INTO signos_vitales (
                processed_by, reviews_by, weight, stature, 
                blood_pressure, map_pressure, temperature, heart_rate, 
                respiratory_rate, oxygen_saturation, glucose, idpa
            ) VALUES (
                :processedBy, :reviewsBy, :weight, :stature, 
                :bloodPressure, :mapPressure, :temperature, :heartRate, 
                :respiratoryRate, :oxygenSaturation, :glucose, :idpa
            )";
    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':processedBy' => $processedBy,
        ':reviewsBy' => $reviewsBy,
        ':weight' => $weight,
        ':stature' => $stature,
        ':bloodPressure' => $bloodPressure,
        ':mapPressure' => $mapPressure,
        ':temperature' => $temperature,
        ':heartRate' => $heartRate,
        ':respiratoryRate' => $respiratoryRate,
        ':oxygenSaturation' => $oxygenSaturation,
        ':glucose' => $glucose,
        ':idpa' => $idpa
    ]);

    $fetchSql = "SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC";
    $fetchStmt = $connect->prepare($fetchSql);
    $fetchStmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $fetchStmt->execute();
    $records = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => "Signos vitales guardados correctamente.",
        "data" => $records
    ]);
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    echo json_encode(["error" => "Error al guardar en la base de datos."]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
