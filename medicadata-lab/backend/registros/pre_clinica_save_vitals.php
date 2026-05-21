<?php
/**
 * Pre-clínica: guardar nuevo registro de signos vitales.
 */
declare(strict_types=1);

session_start();

date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json; charset=utf-8');

try {
    require_once dirname(__DIR__) . '/bd/Conexion.php';
} catch (Throwable $e) {
    error_log('pre_clinica_save_vitals Conexion: ' . $e->getMessage());
    echo json_encode(['error' => 'No se pudo conectar a la base de datos. Verifique el servidor MySQL.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['error' => 'Sesión no válida. Reinicie sesión e intente de nuevo.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userIdSession = (int) $_SESSION['id'];
    if ($userIdSession < 1) {
        echo json_encode(['error' => 'Sesión no válida.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $tipo_paciente = isset($_POST['tipo_paciente']) ? trim((string) $_POST['tipo_paciente']) : 'paciente';
    $id_paciente = isset($_POST['id_paciente']) ? trim((string) $_POST['id_paciente']) : '';

    if ($id_paciente === '' || $id_paciente === '0') {
        throw new RuntimeException('Seleccione un paciente, pulse Consultar y vuelva a guardar.');
    }

    $required_fields = [
        'blood_pressure', 'map_pressure', 'temperature', 'heart_rate',
        'respiratory_rate', 'oxygen_saturation', 'weight', 'stature', 'glucose',
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim((string) $_POST[$field]) === '') {
            throw new RuntimeException('Complete todos los signos vitales (' . str_replace('_', ' ', $field) . ' vacío).');
        }
    }

    $stmtName = $connect->prepare('SELECT name, username FROM users WHERE id = ? LIMIT 1');
    $stmtName->execute([$userIdSession]);
    $urow = $stmtName->fetch(PDO::FETCH_ASSOC);
    $nombreDb = $urow ? trim((string) ($urow['name'] ?? '')) : '';
    $userDb = $urow ? trim((string) ($urow['username'] ?? '')) : '';
    $fallbackPost = trim((string) ($_POST['processed_by'] ?? ''));
    $processedBy = $nombreDb !== '' ? $nombreDb : ($userDb !== '' ? $userDb : ($fallbackPost !== '' ? $fallbackPost : 'Usuario sistema'));

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
                    fecha, hora,
                    processed_by, reviews_by,
                    processed_by_user_id,
                    reviewed_by_user_id,
                    reviewed_at,
                    weight, stature, blood_pressure, map_pressure, temperature,
                    heart_rate, respiratory_rate, oxygen_saturation, glucose, idpa
                ) VALUES (
                    :fecha, :hora,
                    :processedBy, '',
                    :processedByUid,
                    NULL,
                    NULL,
                    :weight, :stature, :bloodPressure, :mapPressure, :temperature,
                    :heartRate, :respiratoryRate, :oxygenSaturation, :glucose, :idpa
                )";
        $params = [
            ':idpa' => (int) $id_paciente,
        ];
    } else {
        $sql = "INSERT INTO signos_vitales_outpatients (
                    fecha, hora,
                    processed_by, reviews_by,
                    processed_by_user_id,
                    reviewed_by_user_id,
                    reviewed_at,
                    weight, stature, blood_pressure, map_pressure, temperature,
                    heart_rate, respiratory_rate, oxygen_saturation, glucose, id_outpatient
                ) VALUES (
                    :fecha, :hora,
                    :processedBy, '',
                    :processedByUid,
                    NULL,
                    NULL,
                    :weight, :stature, :bloodPressure, :mapPressure, :temperature,
                    :heartRate, :respiratoryRate, :oxygenSaturation, :glucose, :id_outpatient
                )";
        $params = [
            ':id_outpatient' => (int) $id_paciente,
        ];
    }

    $stmt_params = array_merge($params, [
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
    ]);

    $stmt = $connect->prepare($sql);
    $stmt->execute($stmt_params);

    echo json_encode([
        'success' => 'Signos vitales guardados correctamente.',
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    error_log('pre_clinica_save_vitals PDO: ' . $e->getMessage());
    $msg = $e->getMessage();
    $hint = '';
    if (strpos($msg, 'signos_vitales_outpatients') !== false && strpos($msg, "doesn't exist") !== false) {
        $hint = ' Cree la tabla signos_vitales_outpatients en MySQL (como en producción).';
    }
    if (
        strpos($msg, 'processed_by_user_id') !== false
        || strpos($msg, 'reviewed_by_user_id') !== false
        || strpos($msg, 'Unknown column') !== false
    ) {
        echo json_encode([
            'error' => 'La tabla signos_vitales debe estar actualizada con las columnas de expediente (processed_by, weight, blood_pressure, processed_by_user_id, etc.). Actualice el esquema desde producción o ejecute la migración de signos vitales.' . $hint,
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['error' => $msg . $hint], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
