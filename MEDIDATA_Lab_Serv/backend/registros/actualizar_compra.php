<?php
require_once '../../backend/bd/Conexion.php';

function registrarHistorial($connect, $id_compra, $campo, $valor_anterior, $valor_nuevo) {
    $sql = "INSERT INTO compra_historial (id_compra, campo, valor_anterior, valor_nuevo, fecha_modificacion) 
            VALUES (:id_compra, :campo, :valor_anterior, :valor_nuevo, NOW())";
    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':id_compra' => $id_compra,
        ':campo' => $campo,
        ':valor_anterior' => $valor_anterior,
        ':valor_nuevo' => $valor_nuevo
    ]);
}

try {
    $id_compra = $_POST['id_compra'];
    $nuevo_proveedor = $_POST['nuevo_proveedor'];
    $nuevo_total = $_POST['nuevo_total'];

    // Verificar los datos enviados para detalle_compras
    $detalles_nuevos = $_POST['detalles'] ?? [];

    $connect->beginTransaction();

    // 1. Obtener los valores actuales de la compra para comparación
    $stmt = $connect->prepare("SELECT prov_datos, total FROM compras WHERE id_compra = :id_compra");
    $stmt->execute([':id_compra' => $id_compra]);
    $compra_actual = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compra_actual) {
        throw new Exception("Compra no encontrada.");
    }

    // 2. Actualizar y registrar cambios en `compra_historial` solo si hay modificaciones
    if ($compra_actual['prov_datos'] !== $nuevo_proveedor) {
        registrarHistorial($connect, $id_compra, 'Proveedor', $compra_actual['prov_datos'], $nuevo_proveedor);
    }
    if ($compra_actual['total'] != $nuevo_total) {
        registrarHistorial($connect, $id_compra, 'Total', $compra_actual['total'], $nuevo_total);
    }

    // 3. Actualizar en `compras`
    $stmt = $connect->prepare("UPDATE compras SET prov_datos = :nuevo_proveedor, total = :nuevo_total WHERE id_compra = :id_compra");
    $stmt->execute([
        ':nuevo_proveedor' => $nuevo_proveedor,
        ':nuevo_total' => $nuevo_total,
        ':id_compra' => $id_compra
    ]);

    // 4. Actualizar `detalle_compras` y registrar los cambios en `compra_historial`
    foreach ($detalles_nuevos as $detalle) {
        $stmt_detalle = $connect->prepare("SELECT * FROM detalle_compras WHERE id = :id AND id_compra = :id_compra");
        $stmt_detalle->execute([':id' => $detalle['id'], ':id_compra' => $id_compra]);
        $detalle_actual = $stmt_detalle->fetch(PDO::FETCH_ASSOC);

        if (!$detalle_actual) continue;

        // Verificar cambios y registrar historial solo si el valor ha cambiado
        foreach ($detalle as $campo => $valor_nuevo) {
            if ($campo !== 'id' && isset($detalle_actual[$campo]) && $detalle_actual[$campo] != $valor_nuevo) {
                registrarHistorial($connect, $id_compra, ucfirst($campo), $detalle_actual[$campo], $valor_nuevo);
            }
        }

        // Actualizar en `detalle_compras`
        $stmt_update_detalle = $connect->prepare("UPDATE detalle_compras SET 
            codigo_producto = :codigo_producto, cantidad = :cantidad, unidad = :unidad, descripcion = :descripcion, 
            precio_unitario = :precio_unitario, isv = :isv, subtotal = :subtotal, total_item = :total_item, 
            exento = :exento, gravado = :gravado, descuento_porcentaje = :descuento_porcentaje 
            WHERE id = :id AND id_compra = :id_compra");

        $stmt_update_detalle->execute([
            ':codigo_producto' => $detalle['codigo_producto'],
            ':cantidad' => $detalle['cantidad'],
            ':unidad' => $detalle['unidad'],
            ':descripcion' => $detalle['descripcion'],
            ':precio_unitario' => $detalle['precio_unitario'],
            ':isv' => $detalle['isv'],
            ':subtotal' => $detalle['subtotal'],
            ':total_item' => $detalle['total_item'],
            ':exento' => $detalle['exento'] ?? 0,
            ':gravado' => $detalle['gravado'] ?? 0,
            ':descuento_porcentaje' => $detalle['descuento_porcentaje'] ?? 0,
            ':id' => $detalle['id'],
            ':id_compra' => $id_compra
        ]);
    }

    $connect->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
