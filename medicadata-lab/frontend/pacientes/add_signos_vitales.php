<?php
session_start();
require_once('../../backend/bd/Conexion.php');

date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['error' => 'Sesión no válida. Reinicie sesión e intente de nuevo.']);
        exit;
    }

    $userIdSession = (int) $_SESSION['id'];
    if ($userIdSession < 1) {
        echo json_encode(['error' => 'Sesión no válida.']);
        exit;
    }

    if (
        empty($_POST['blood_pressure']) || empty($_POST['map_pressure']) || empty($_POST['temperature']) ||
        empty($_POST['heart_rate']) || empty($_POST['respiratory_rate']) || empty($_POST['oxygen_saturation']) ||
        empty($_POST['weight']) || empty($_POST['stature']) || empty($_POST['glucose']) ||
        empty($_POST['idpa'])
    ) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    $stmtName = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmtName->execute([$userIdSession]);
    $nombreDb = trim((string) $stmtName->fetchColumn());

    $processedBy = $nombreDb !== '' ? $nombreDb : trim((string) ($_POST['processed_by'] ?? ''));
    if ($processedBy === '') {
        throw new Exception('No se pudo obtener el nombre del usuario que registra.');
    }

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

    $fechaPv = isset($_POST['fecha']) ? trim((string) $_POST['fecha']) : '';
    $horaPv = isset($_POST['hora']) ? trim((string) $_POST['hora']) : '';
    $tienesFh = ($fechaPv !== '' && $horaPv !== '');

    if ($tienesFh) {
        $sql = "INSERT INTO signos_vitales (
                    fecha, hora,
                    processed_by, reviews_by, processed_by_user_id,
                    reviewed_by_user_id, reviewed_at,
                    weight, stature,
                    blood_pressure, map_pressure, temperature, heart_rate,
                    respiratory_rate, oxygen_saturation, glucose, idpa
                ) VALUES (
                    :fecha, :hora,
                    :processedBy, '', :processedByUid,
                    NULL, NULL,
                    :weight, :stature,
                    :bloodPressure, :mapPressure, :temperature, :heartRate,
                    :respiratoryRate, :oxygenSaturation, :glucose, :idpa
                )";
        $stmt = $connect->prepare($sql);
        $stmt->execute([
            ':fecha' => $fechaPv,
            ':hora' => $horaPv,
            ':processedBy' => $processedBy,
            ':processedByUid' => $userIdSession,
            ':weight' => $weight,
            ':stature' => $stature,
            ':bloodPressure' => $bloodPressure,
            ':mapPressure' => $mapPressure,
            ':temperature' => $temperature,
            ':heartRate' => $heartRate,
            ':respiratoryRate' => $respiratoryRate,
            ':oxygenSaturation' => $oxygenSaturation,
            ':glucose' => $glucose,
            ':idpa' => $idpa,
        ]);
    } else {
    $sql = "INSERT INTO signos_vitales (
                processed_by, reviews_by, processed_by_user_id,
                reviewed_by_user_id, reviewed_at,
                weight, stature,
                blood_pressure, map_pressure, temperature, heart_rate,
                respiratory_rate, oxygen_saturation, glucose, idpa
            ) VALUES (
                :processedBy, '', :processedByUid,
                NULL, NULL,
                :weight, :stature,
                :bloodPressure, :mapPressure, :temperature, :heartRate,
                :respiratoryRate, :oxygenSaturation, :glucose, :idpa
            )";
    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':processedBy' => $processedBy,
        ':processedByUid' => $userIdSession,
        ':weight' => $weight,
        ':stature' => $stature,
        ':bloodPressure' => $bloodPressure,
        ':mapPressure' => $mapPressure,
        ':temperature' => $temperature,
        ':heartRate' => $heartRate,
        ':respiratoryRate' => $respiratoryRate,
        ':oxygenSaturation' => $oxygenSaturation,
        ':glucose' => $glucose,
        ':idpa' => $idpa,
    ]);
    }

    $fetchSql = "SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC";
    $fetchStmt = $connect->prepare($fetchSql);
    $fetchStmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $fetchStmt->execute();
    $records = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => 'Signos vitales guardados correctamente.',
        'data' => $records,
    ]);
} catch (PDOException $e) {
    error_log('add_signos_vitales PDO: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al guardar en la base de datos (¿aplicó la migración de firmas?).']);
} catch (Exception $e) {
    error_log('add_signos_vitales: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
