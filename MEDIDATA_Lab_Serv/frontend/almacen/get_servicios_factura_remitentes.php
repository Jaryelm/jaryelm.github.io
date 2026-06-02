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
    // Obtener servicios de la factura que no están asignados a remitentes
    $sql = "SELECT DISTINCT s.id, s.nombre_servicio
            FROM order_details od
            JOIN servicios_hospital s ON od.service_id = s.id
            WHERE od.order_id = ? 
            AND od.item_type = 'servicio'
            AND s.id NOT IN (
                SELECT id_servicio 
                FROM remitentes_honorarios 
                WHERE id_factura = ?
            )
            ORDER BY s.nombre_servicio";
    
    $stmt = $connect->prepare($sql);
    $stmt->execute([$id_factura, $id_factura]);
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($servicios);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener servicios: ' . $e->getMessage()]);
}
?> 