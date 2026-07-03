<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

$id_factura = isset($_GET['id_factura']) ? intval($_GET['id_factura']) : 0;
$id_doctor = isset($_GET['id_doctor']) ? intval($_GET['id_doctor']) : 0;

if ($id_factura <= 0 || $id_doctor <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de factura o médico no válido.']);
    exit;
}

try {
    $sql = "
    SELECT 
        od.descripcion AS nombre_servicio,
        od.cantidad,
        s.precio_venta AS precio_unitario,
        od.total_after_discount AS subtotal,
        COALESCE(hc.porcentaje_honorario, 0) AS porcentaje_honorario,
        COALESCE(hc.cuota_fija, 0) AS cuota_fija,
        CASE 
            WHEN COALESCE(hc.cuota_fija, 0) > 0 THEN hc.cuota_fija * od.cantidad
            ELSE od.total_after_discount * (COALESCE(hc.porcentaje_honorario, 0) / 100)
        END AS honorario_calculado
    FROM order_details od
    JOIN servicios_hospital s ON od.service_id = s.id
    LEFT JOIN honorarios_configuracion hc ON hc.id_doctor = :id_doctor AND hc.id_servicio = od.service_id
    WHERE od.order_id = :id_factura AND od.item_type = 'servicio'
    ";

    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt->bindParam(':id_factura', $id_factura, PDO::PARAM_INT);
    $stmt->execute();
    
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatear el campo honorario_calculado a dos decimales (LPS)
    foreach ($servicios as &$servicio) {
        $servicio['honorario_calculado'] = number_format((float)$servicio['honorario_calculado'], 2, '.', '');
    }

    echo json_encode($servicios);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 