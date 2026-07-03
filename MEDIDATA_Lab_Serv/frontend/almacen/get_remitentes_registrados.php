<?php
require '../../backend/bd/Conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id_factura'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de factura requerido']);
    exit;
}

$id_factura = intval($_GET['id_factura']);

try {
    // Verificar si la tabla existe
    $stmt_check = $connect->prepare("SHOW TABLES LIKE 'remitentes_honorarios'");
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        // La tabla no existe, devolver array vacío
        echo json_encode([]);
        exit;
    }
    
    // Obtener remitentes registrados para la factura
    $sql = "SELECT 
                rh.id,
                rh.factura,
                rh.monto_comision,
                rh.fecha_registro,
                CONCAT(d.nodoc, ' ', d.apdoc) as nombre_medico,
                s.nombre_servicio
            FROM remitentes_honorarios rh
            JOIN doctor d ON rh.id_doctor_remitente = d.idodc
            JOIN servicios_hospital s ON rh.id_servicio = s.id
            WHERE rh.id_factura = ?
            ORDER BY rh.fecha_registro DESC";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute([$id_factura]);
    $remitentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($remitentes);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener remitentes: ' . $e->getMessage()]);
}
?> 