<?php
date_default_timezone_set('America/Tegucigalpa');
$currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

require_once('../../backend/bd/Conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $productos = $_POST['productos'];
        $justificacion = trim($_POST['justificacion']);
        $usuario_solicitud = $_SESSION['name']; // Usuario que realiza la solicitud

        foreach ($productos as $producto) {
            $producto_id = trim($producto['id']);
            $stock_actual = trim($producto['stock_actual']);
            $cantidad_solicitada = trim($producto['cantidad_solicitada']);
            $proveedor = trim($producto['proveedor']);

            // Obtener el nombre del producto y el precio desde la tabla `product`
            $stmt_producto = $connect->prepare("SELECT nompro, preprd FROM product WHERE idprcd = :producto_id");
            $stmt_producto->bindParam(':producto_id', $producto_id);
            $stmt_producto->execute();
            $producto_data = $stmt_producto->fetch(PDO::FETCH_ASSOC);

            if (!$producto_data) {
                throw new Exception("Producto no encontrado: ID = $producto_id");
            }
            $producto_nombre = $producto_data['nompro'];
            $precio_producto = $producto_data['preprd'];

            // Insertar la solicitud de reorden en la base de datos
            $stmt_insert = $connect->prepare("
                INSERT INTO reorden_solicitudes (
                    producto_id, producto_nombre, stock_actual, cantidad_minima, cantidad_solicitada, 
                    justificacion, estado, fecha_solicitud, usuario_solicitud, precio_producto, proveedor
                ) VALUES (
                    :producto_id, :producto_nombre, :stock_actual, 5, :cantidad_solicitada, 
                    :justificacion, 'pendiente', :fecha_solicitud, :usuario_solicitud, :precio_producto, :proveedor
                )
            ");

            $stmt_insert->bindParam(':producto_id', $producto_id);
            $stmt_insert->bindParam(':producto_nombre', $producto_nombre);
            $stmt_insert->bindParam(':stock_actual', $stock_actual);
            $stmt_insert->bindParam(':cantidad_solicitada', $cantidad_solicitada);
            $stmt_insert->bindParam(':justificacion', $justificacion);
            $stmt_insert->bindParam(':fecha_solicitud', $currentDateTime);
            $stmt_insert->bindParam(':usuario_solicitud', $usuario_solicitud);
            $stmt_insert->bindParam(':precio_producto', $precio_producto);
            $stmt_insert->bindParam(':proveedor', $proveedor);

            $stmt_insert->execute();
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>