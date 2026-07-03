<?php
require_once('../bd/Conexion.php');
session_start();

// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

try {
    $connect->beginTransaction();

    // Recibir datos del formulario
    $solicitante = $_POST['solicitante'];
    $fecha = date('Y-m-d H:i:s');
    $justificacion = $_POST['justificacion'];
    $sucursal_descargo = $_POST['sucursal_descargo'];
    $bodega_descargo = $_POST['bodega_descargo'];
    $sucursal_cargo = $_POST['sucursal_cargo'];
    $bodega_cargo = $_POST['bodega_cargo'];
    $estado = 'pendiente';
    $usuario_solicitud = $_SESSION['id'];
    $tipo = isset($_POST['tipo_requisicion']) ? $_POST['tipo_requisicion'] : 'interna';
    $nombre_paciente = ($tipo === 'externa' && !empty($_POST['paciente'])) ? $_POST['paciente'] : null;

    // Insertar la requisición principal
    $stmt = $connect->prepare("INSERT INTO requisiciones (
        tipo,
        nombre_paciente,
        solicitante_id,
        fecha_solicitud,
        justificacion,
        sucursal_descargo,
        bodega_descargo,
        sucursal_cargo,
        bodega_cargo,
        estado,
        usuario_solicitud
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $tipo,
        $nombre_paciente,
        $solicitante,
        $fecha,
        $justificacion,
        $sucursal_descargo,
        $bodega_descargo,
        $sucursal_cargo,
        $bodega_cargo,
        $estado,
        $usuario_solicitud
    ]);

    $requisicion_id = $connect->lastInsertId();

    // Insertar los artículos de la requisición
    foreach ($_POST['articulos'] as $articulo) {
        $stmt = $connect->prepare("INSERT INTO requisicion_detalles (
            requisicion_id,
            producto_id,
            cantidad
        ) VALUES (?, ?, ?)");

        $stmt->execute([
            $requisicion_id,
            $articulo['codigo'],
            $articulo['cantidad']
        ]);
    }

    $connect->commit();
    echo json_encode(['success' => true, 'message' => 'Requisición guardada correctamente']);

} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 