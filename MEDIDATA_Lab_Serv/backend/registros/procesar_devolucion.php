<?php
require_once('../../backend/bd/Conexion.php');

// Recibir datos
$data = json_decode(file_get_contents('php://input'), true);

try {
    $connect->beginTransaction();

    // 1. Verificar la cantidad disponible y obtener el tipo de item
    $stmt = $connect->prepare("
        SELECT cantidad, item_type, hospitalario_id, product_id 
        FROM order_details 
        WHERE order_id = :orderId AND codpro = :productId
    ");
    
    $stmt->execute([
        ':orderId' => $data['orderId'],
        ':productId' => $data['productId']
    ]);
    
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$detalle) {
        throw new Exception("No se encontró el producto en la orden.");
    }

    $currentQuantity = $detalle['cantidad'];
    $itemType = $detalle['item_type'];
    $hospitalarioId = $detalle['hospitalario_id'];
    $productId = $detalle['product_id'];
    
    if ($currentQuantity < $data['cantidad']) {
        throw new Exception("La cantidad a devolver excede la cantidad disponible");
    }

    // 2. Registrar la devolución antes de eliminar el registro
    $stmt = $connect->prepare("
        INSERT INTO returns (
            order_id, 
            product_id, 
            quantity_returned, 
            return_reason, 
            return_date,
            processed_by,
            status
        ) VALUES (
            :orderId,
            :productId,
            :cantidad,
            :motivo,
            NOW(),
            :processed_by,
            'Procesado'
        )
    ");

    $stmt->execute([
        ':orderId' => $data['orderId'],
        ':productId' => $data['productId'],
        ':cantidad' => $data['cantidad'],
        ':motivo' => $data['motivo'],
        ':processed_by' => isset($_SESSION['name']) ? $_SESSION['name'] : 'Sistema'
    ]);

    // 3. Eliminar el registro de order_details
    $stmt = $connect->prepare("
        DELETE FROM order_details 
        WHERE order_id = :orderId 
        AND codpro = :productId
        AND cantidad = :cantidad
    ");
    
    $stmt->execute([
        ':orderId' => $data['orderId'],
        ':productId' => $data['productId'],
        ':cantidad' => $data['cantidad']
    ]);

    // 4. Actualizar el inventario según el tipo de producto
    if ($itemType === 'producto') {
        $stmt = $connect->prepare("
            UPDATE product 
            SET stock = stock + :cantidad
            WHERE idprcd = :productId
        ");
        $stmt->execute([
            ':cantidad' => $data['cantidad'],
            ':productId' => $productId
        ]);
    } elseif ($itemType === 'producto_hospitalario') {
        $stmt = $connect->prepare("
            UPDATE almacen_hospitalario 
            SET stock = stock + :cantidad
            WHERE idprcd = :hospitalarioId
        ");
        $stmt->execute([
            ':cantidad' => $data['cantidad'],
            ':hospitalarioId' => $hospitalarioId
        ]);
    }

    // 5. Actualizar el total en la tabla orders
    $stmt = $connect->prepare("
        UPDATE orders o
        SET 
            total_price = COALESCE(
                (
                    SELECT SUM(total_after_discount)
                    FROM order_details
                    WHERE order_id = :orderId
                ), 0
            ),
            price_without_discount = COALESCE(
                (
                    SELECT SUM(total_price)
                    FROM order_details
                    WHERE order_id = :orderId
                ), 0
            )
        WHERE o.idord = :orderId
    ");

    $stmt->execute([
        ':orderId' => $data['orderId']
    ]);

    $connect->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $connect->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}