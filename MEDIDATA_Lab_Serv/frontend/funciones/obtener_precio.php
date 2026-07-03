<?php
require '../../backend/bd/Conexion.php';

if (isset($_GET['descripcion'])) {
    $descripcion = $_GET['descripcion'];

    // Consulta para obtener precio_unitario, cantidad y unidad del producto
    $stmt = $connect->prepare('SELECT precio_unitario, cantidad, unidad FROM detalle_compras WHERE descripcion = :descripcion LIMIT 1');
    $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
    $stmt->execute();

    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        echo json_encode([
            'precio_unitario' => $producto['precio_unitario'],
            'cantidad' => $producto['cantidad'],
            'unidad' => $producto['unidad']
        ]);
    } else {
        echo json_encode(['precio_unitario' => null, 'cantidad' => null, 'unidad' => null]);
    }
} else {
    echo json_encode(['precio_unitario' => null, 'cantidad' => null, 'unidad' => null]);
}
