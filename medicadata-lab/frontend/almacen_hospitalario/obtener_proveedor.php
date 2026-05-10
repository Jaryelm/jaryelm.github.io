<?php
require_once('../../backend/bd/Conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener el nombre del producto enviado por AJAX
        $producto_nombre = trim($_POST['producto_nombre']);

        // Consulta SQL para obtener el proveedor
        $stmt = $connect->prepare("
            SELECT c.prov_datos
            FROM compras c
            JOIN detalle_compras dc ON c.id_compra = dc.id_compra
            WHERE dc.descripcion = :producto_nombre
        ");
        $stmt->bindParam(':producto_nombre', $producto_nombre);
        $stmt->execute();
        $proveedor_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($proveedor_data) {
            echo $proveedor_data['prov_datos']; // Devolver el nombre del proveedor
        } else {
            echo 'Proveedor no encontrado'; // Mensaje si no se encuentra el proveedor
        }

    } catch (Exception $e) {
        echo 'Error al obtener el proveedor: ' . $e->getMessage();
    }
}
?>