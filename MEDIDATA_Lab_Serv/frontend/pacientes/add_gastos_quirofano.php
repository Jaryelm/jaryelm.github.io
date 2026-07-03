<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    $created_at = date('Y-m-d H:i:s'); // Fecha y hora actual

    // Capturar los datos y asignar "No Registrado" si están vacíos
    $procesado_por = !empty($_POST['procesado_por']) ? $_POST['procesado_por'] : "No Registrado";
    $insumo_material_descartable = !empty($_POST['insumo_material_descartable']) ? $_POST['insumo_material_descartable'] : "No Registrado";
    $cantidad_material_descartable = !empty($_POST['cantidad_material_descartable']) ? $_POST['cantidad_material_descartable'] : "No Registrado";
    $insumo_medicamentos = !empty($_POST['insumo_medicamentos']) ? $_POST['insumo_medicamentos'] : "No Registrado";
    $cantidad_medicamentos = !empty($_POST['cantidad_medicamentos']) ? $_POST['cantidad_medicamentos'] : "No Registrado";
    $insumo_anestesicos = !empty($_POST['insumo_anestesicos']) ? $_POST['insumo_anestesicos'] : "No Registrado";
    $cantidad_anestesicos = !empty($_POST['cantidad_anestesicos']) ? $_POST['cantidad_anestesicos'] : "No Registrado";
    $insumo_equipo_medico = !empty($_POST['insumo_equipo_medico']) ? $_POST['insumo_equipo_medico'] : "No Registrado";
    $cantidad_equipo_medico = !empty($_POST['cantidad_equipo_medico']) ? $_POST['cantidad_equipo_medico'] : "No Registrado";
    $idpa = !empty($_POST['idpa']) ? $_POST['idpa'] : "No Registrado";
    $medico_referente = !empty($_POST['medico_referente']) ? $_POST['medico_referente'] : "No Registrado";
    $cirujano_principal = !empty($_POST['cirujano_principal']) ? $_POST['cirujano_principal'] : "No Registrado";

    // Insertar en la base de datos
    $stmt = $connect->prepare("
        INSERT INTO gastos_quirofano 
        (fecha, hora, procesado_por, insumo_material_descartable, cantidad_material_descartable, 
        insumo_medicamentos, cantidad_medicamentos, insumo_anestesicos, cantidad_anestesicos,
        insumo_equipo_medico, cantidad_equipo_medico, idpa, medico_referente, cirujano_principal, created_at) 
        VALUES (:fecha, :hora, :procesado_por, :insumo_material, :cantidad_material, 
        :insumo_medicamentos, :cantidad_medicamentos, :insumo_anestesicos, :cantidad_anestesicos, 
        :insumo_equipo_medico, :cantidad_equipo_medico, :idpa, :medico_referente, :cirujano_principal, :created_at)
    ");

    $stmt->execute([
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':procesado_por' => $procesado_por,
        ':insumo_material' => $insumo_material_descartable,
        ':cantidad_material' => $cantidad_material_descartable,
        ':insumo_medicamentos' => $insumo_medicamentos,
        ':cantidad_medicamentos' => $cantidad_medicamentos,
        ':insumo_anestesicos' => $insumo_anestesicos,
        ':cantidad_anestesicos' => $cantidad_anestesicos,
        ':insumo_equipo_medico' => $insumo_equipo_medico,
        ':cantidad_equipo_medico' => $cantidad_equipo_medico,
        ':idpa' => $idpa,
        ':medico_referente' => $medico_referente,
        ':cirujano_principal' => $cirujano_principal,
        ':created_at' => $created_at
    ]);

    echo json_encode(["success" => "Registro guardado correctamente."]);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>