<?php
declare(strict_types=1);

/**
 * Consultas ligeras para modales "Ver detalles" del Diario General.
 * Evita JOINs pesados con CASE en ON; los montos ya están en order_details / detalle_compras.
 */

/**
 * Líneas de venta (checkout) para una orden.
 *
 * @return list<array<string, mixed>>
 */
function medidata_diario_fetch_order_details_checkout(PDO $connect, int $orderId): array
{
    if ($orderId < 1) {
        return [];
    }

    $stmt = $connect->prepare(
        'SELECT
            od.codpro AS codigo,
            od.descripcion AS nombre,
            od.cantidad,
            od.discount_percentage,
            od.age_discount_30,
            od.age_discount_40,
            od.promotion_discount,
            od.other_discount,
            od.total_discount,
            od.total_after_discount,
            ROUND(od.total_after_discount + od.total_discount, 2) AS total_original,
            COALESCE(p.impuesto, ah.impuesto, s.impuesto) AS impuesto
        FROM order_details od
        LEFT JOIN product p ON p.idprcd = od.product_id
        LEFT JOIN almacen_hospitalario ah ON ah.idprcd = od.hospitalario_id
        LEFT JOIN servicios_hospital s ON s.id = od.service_id
        WHERE od.order_id = :order_id
        ORDER BY od.id ASC'
    );
    $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Líneas de compra a proveedor.
 *
 * @return list<array<string, mixed>>
 */
function medidata_diario_fetch_detalle_compras(PDO $connect, int $idCompra): array
{
    if ($idCompra < 1) {
        return [];
    }

    $stmt = $connect->prepare(
        'SELECT
            id_detalle,
            id_compra,
            cat_cuenta,
            codigo_producto,
            descripcion,
            cantidad,
            unidad,
            precio_unitario,
            isv,
            subtotal,
            descuento_porcentaje,
            total_item,
            exento,
            gravado
        FROM detalle_compras
        WHERE id_compra = :id_compra
        ORDER BY id_detalle ASC'
    );
    $stmt->bindValue(':id_compra', $idCompra, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
