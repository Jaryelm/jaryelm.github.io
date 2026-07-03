<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera JSON
header('Content-Type: application/json');

try {
    // Guardar datos en log para depuración
    file_put_contents('debug_medifarma.log', print_r($_POST, true));

    // Validar datos obligatorios
    if (empty($_POST['idpa']) || empty($_POST['medico_operando']) || empty($_POST['cirugia_realizar']) ||
        empty($_POST['nombre_solicitante']) || empty($_POST['medicamentos']) ||
        empty($_POST['material']) || empty($_POST['cantidad']) || empty($_POST['procesado_por'])) {
        throw new Exception("Todos los campos obligatorios deben estar completos.");
    }

    // Sanitizar entrada
    $idpa = intval($_POST['idpa']);
    $medico_operando = htmlspecialchars($_POST['medico_operando'], ENT_QUOTES, 'UTF-8');
    $cirugia_realizar = htmlspecialchars($_POST['cirugia_realizar'], ENT_QUOTES, 'UTF-8');
    $nombre_solicitante = htmlspecialchars($_POST['nombre_solicitante'], ENT_QUOTES, 'UTF-8');
    $medicamentos = htmlspecialchars($_POST['medicamentos'], ENT_QUOTES, 'UTF-8');
    $material = htmlspecialchars($_POST['material'], ENT_QUOTES, 'UTF-8');
    $cantidad = intval($_POST['cantidad']);
    $procesado_por = htmlspecialchars($_POST['procesado_por'], ENT_QUOTES, 'UTF-8');
    $created_at = date('Y-m-d H:i:s');

    // Insertar en la base de datos
    $sql = "INSERT INTO medicamentos_medifarma (idpa, medico_operando, cirugia_realizar, nombre_solicitante, 
            medicamentos, material, cantidad, procesado_por, created_at) 
            VALUES (:idpa, :medico_operando, :cirugia_realizar, :nombre_solicitante, 
            :medicamentos, :material, :cantidad, :procesado_por, :created_at)";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':idpa' => $idpa,
        ':medico_operando' => $medico_operando,
        ':cirugia_realizar' => $cirugia_realizar,
        ':nombre_solicitante' => $nombre_solicitante,
        ':medicamentos' => $medicamentos,
        ':material' => $material,
        ':cantidad' => $cantidad,
        ':procesado_por' => $procesado_por,
        ':created_at' => $created_at
    ]);

    echo json_encode(["success" => "Registro guardado correctamente."]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
