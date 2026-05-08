<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

// Establecer la zona horaria local
date_default_timezone_set('America/Tegucigalpa');

try {
    // Solo temperatura, idpa, procesado_por y turno son obligatorios
    $required_fields = ['idpa', 'procesado_por', 'turno', 'temps'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            throw new Exception("Falta el campo obligatorio: " . ($field === 'temps' ? 'temperatura' : $field));
        }
    }

    $fechaHoraLocal = date('Y-m-d H:i:s');

    // Campos opcionales: usar cadena vacía si no se envían
    $frecuenciac = isset($_POST['frecuenciac']) ? trim($_POST['frecuenciac']) : '';
    $tensiona = isset($_POST['tensiona']) ? trim($_POST['tensiona']) : '';
    $spo_2 = isset($_POST['spo_2']) ? trim($_POST['spo_2']) : '';
    $peso_kg = isset($_POST['peso_kg']) ? trim($_POST['peso_kg']) : '';
    $talla_temp = isset($_POST['talla_temp']) ? trim($_POST['talla_temp']) : '';
    $imc_temp = isset($_POST['imc_temp']) ? trim($_POST['imc_temp']) : '';
    $glucap_temp = isset($_POST['glucap_temp']) ? trim($_POST['glucap_temp']) : '';
    $fresp_temp = isset($_POST['fresp_temp']) ? trim($_POST['fresp_temp']) : '';

    $stmt = $connect->prepare("
        INSERT INTO grafica_temperatura (idpa, temps, turno, procesado_por, frecuenciac, tensiona, spo_2, peso_kg, talla_temp, imc_temp, glucap_temp, fresp_temp, created_at)
        VALUES (:idpa, :temps, :turno, :procesado_por, :frecuenciac, :tensiona, :spo_2, :peso_kg, :talla_temp, :imc_temp, :glucap_temp, :fresp_temp, :created_at)
    ");
    $stmt->execute([
        ':idpa' => $_POST['idpa'],
        ':temps' => trim($_POST['temps']),
        ':turno' => trim($_POST['turno']),
        ':procesado_por' => trim($_POST['procesado_por']),
        ':frecuenciac' => $frecuenciac,
        ':tensiona' => $tensiona,
        ':spo_2' => $spo_2,
        ':peso_kg' => $peso_kg,
        ':talla_temp' => $talla_temp,
        ':imc_temp' => $imc_temp,
        ':glucap_temp' => $glucap_temp,
        ':fresp_temp' => $fresp_temp,
        ':created_at' => $fechaHoraLocal
    ]);
    
    echo json_encode(['message' => 'Datos guardados correctamente.']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
