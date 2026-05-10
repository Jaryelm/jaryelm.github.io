<?php
require_once('../../backend/bd/Conexion.php');

$order_id = $_GET['order_id'];

// Consulta corregida para incluir `total_discount` y `total_after_discount` correctamente
$details_query = $connect->prepare("
    SELECT 
        od.item_type, 
        od.cantidad, 
        od.age_discount_30,
        od.age_discount_40,
        od.promotion_discount,
        od.other_discount,
        od.total_discount,
        od.total_after_discount,
        od.discount_percentage,
        (
            CASE 
                WHEN od.item_type = 'producto' AND od.product_id IS NOT NULL THEN od.cantidad * p.precio_venta
                WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN od.cantidad * ah.precio_venta
                WHEN od.item_type = 'servicio' THEN od.cantidad * s.total
                ELSE 0 
            END
        ) AS total_original,
        od.codpro AS codigo,
        od.descripcion AS nombre,
        CASE
            WHEN od.item_type = 'producto' AND od.product_id IS NOT NULL THEN p.impuesto
            WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.impuesto
            WHEN od.item_type = 'servicio' THEN s.impuesto
            ELSE NULL 
        END AS impuesto,
        CASE
            WHEN od.item_type = 'producto' AND od.product_id IS NOT NULL THEN p.precio_venta
            WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN ah.precio_venta
            WHEN od.item_type = 'servicio' THEN s.total
            ELSE NULL
        END AS precio_unitario
    FROM order_details od
    LEFT JOIN product p ON od.product_id = p.idprcd AND od.item_type = 'producto'
    LEFT JOIN almacen_hospitalario ah ON od.hospitalario_id = ah.idprcd AND od.item_type = 'producto'
    LEFT JOIN servicios_hospital s ON od.service_id = s.id AND od.item_type = 'servicio'
    WHERE od.order_id = ?
");
$details_query->execute([$order_id]);

$details = [];
while ($row = $details_query->fetch(PDO::FETCH_ASSOC)) {
    // Incluir todos los items, incluyendo la placa de rayos X
    // Ahora la placa puede ser vista y devuelta como cualquier otro producto/servicio
    $details[] = $row;
}

// Devolver los detalles como JSON
echo json_encode($details);
?>
