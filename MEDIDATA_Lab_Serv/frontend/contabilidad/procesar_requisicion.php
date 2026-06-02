<?php
require_once('../../backend/bd/Conexion.php');
session_start();

// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

if (!isset($_POST['id']) || !isset($_POST['accion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $connect->beginTransaction();

    $id = $_POST['id'];
    $accion = $_POST['accion'];
    $usuario_autorizacion = $_SESSION['id'];
    $fecha_autorizacion = date('Y-m-d H:i:s'); // Ya tomará la hora de Honduras
    $estado = ($accion === 'aprobar') ? 'aprobado' : 'rechazado';

    // Actualizar el estado de la requisición
    $stmt = $connect->prepare("UPDATE requisiciones SET 
        estado = ?,
        usuario_autorizacion = ?,
        fecha_autorizacion = ?
        WHERE id = ?");

    $stmt->execute([$estado, $usuario_autorizacion, $fecha_autorizacion, $id]);

    // Si se aprueba, actualizar el inventario
    if ($accion === 'aprobar') {
        // Obtener los detalles de la requisición
        $stmt = $connect->prepare("SELECT * FROM requisicion_detalles WHERE requisicion_id = ?");
        $stmt->execute([$id]);
        $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Actualizar el stock de cada producto
        foreach ($detalles as $detalle) {
            $stmt = $connect->prepare("UPDATE product SET 
                stock = stock - ? 
                WHERE idprcd = ?");
            $stmt->execute([$detalle['cantidad'], $detalle['producto_id']]);
        }
    }

    $connect->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 