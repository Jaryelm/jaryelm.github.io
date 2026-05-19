<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/bd/Conexion.php';

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

    $tipo_paciente = $_POST['tipo_paciente'] ?? 'paciente';
    $id_paciente = $_POST['id_paciente'] ?? '';

    if (empty($id_paciente)) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $required_fields = [
        'blood_pressure', 'map_pressure', 'temperature', 'heart_rate',
        'respiratory_rate', 'oxygen_saturation', 'weight', 'stature', 'glucose'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || $_POST[$field] === '') {
            throw new Exception("El campo " . str_replace('_', ' ', $field) . " es obligatorio.");
        }
    }

    $stmtName = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmtName->execute([$userIdSession]);
    $nombreDb = trim((string) $stmtName->fetchColumn());

    $processedBy = $nombreDb !== '' ? $nombreDb : trim((string) ($_POST['processed_by'] ?? ''));

    $weight = $_POST['weight'];
    $stature = $_POST['stature'];
    $bloodPressure = $_POST['blood_pressure'];
    $mapPressure = $_POST['map_pressure'];
    $temperature = $_POST['temperature'];
    $heartRate = $_POST['heart_rate'];
    $respiratoryRate = $_POST['respiratory_rate'];
    $oxygenSaturation = $_POST['oxygen_saturation'];
    $glucose = $_POST['glucose'];

    $fechaPv = date('Y-m-d');
    $horaPv = date('H:i:s');

    if ($tipo_paciente === 'paciente') {
        $sql = "INSERT INTO signos_vitales (
                    fecha, hora, processed_by, reviews_by, processed_by_user_id,
                    weight, stature, blood_pressure, map_pressure, temperature, 
                    heart_rate, respiratory_rate, oxygen_saturation, glucose, idpa
                ) VALUES (
                    :fecha, :hora, :processedBy, '', :processedByUid,
                    :weight, :stature, :bloodPressure, :mapPressure, :temperature, 
                    :heartRate, :respiratoryRate, :oxygenSaturation, :glucose, :idpa
                )";
        $params = [
            ':idpa' => $id_paciente
        ];
    } else {
        $sql = "INSERT INTO signos_vitales_outpatients (
                    fecha, hora, processed_by, reviews_by,
                    weight, stature, blood_pressure, map_pressure, temperature, 
                    heart_rate, respiratory_rate, oxygen_saturation, glucose, id_outpatient
                ) VALUES (
                    :fecha, :hora, :processedBy, '',
                    :weight, :stature, :bloodPressure, :mapPressure, :temperature, 
                    :heartRate, :respiratoryRate, :oxygenSaturation, :glucose, :id_outpatient
                )";
        $params = [
            ':id_outpatient' => $id_paciente
        ];
    }

    $common_params = [
        ':fecha' => $fechaPv,
        ':hora' => $horaPv,
        ':processedBy' => $processedBy,
        ':weight' => $weight,
        ':stature' => $stature,
        ':bloodPressure' => $bloodPressure,
        ':mapPressure' => $mapPressure,
        ':temperature' => $temperature,
        ':heartRate' => $heartRate,
        ':respiratoryRate' => $respiratoryRate,
        ':oxygen_saturation' => $oxygenSaturation, // Note: fixing param name if needed, usually :oxygenSaturation
        ':glucose' => $glucose
    ];

    // Rectify param names
    $stmt_params = array_merge($params, [
        ':fecha' => $fechaPv,
        ':hora' => $horaPv,
        ':processedBy' => $processedBy,
        ':weight' => $weight,
        ':stature' => $stature,
        ':bloodPressure' => $bloodPressure,
        ':mapPressure' => $mapPressure,
        ':temperature' => $temperature,
        ':heartRate' => $heartRate,
        ':respiratoryRate' => $respiratoryRate,
        ':oxygenSaturation' => $oxygenSaturation,
        ':glucose' => $glucose
    ]);
    
    if ($tipo_paciente === 'paciente') {
        $stmt_params[':processedByUid'] = $userIdSession;
    }

    $stmt = $connect->prepare($sql);
    $stmt->execute($stmt_params);

    echo json_encode([
        'success' => 'Signos vitales guardados correctamente.'
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>