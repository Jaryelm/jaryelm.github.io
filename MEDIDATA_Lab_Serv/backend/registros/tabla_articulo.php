<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            $sql = "SELECT * FROM registros_articulos ORDER BY fecha_registro DESC" . medidata_tablas_mysql_limit_clause();
            $stmt = $connect->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Manejar la búsqueda
            $sql = "SELECT * FROM registros_articulos 
                    WHERE CONCAT_WS(' ', linea, sub_linea, sucursal_bodega, envase, farmaceutica, concentracion, via_administracion, codigo_articulo, nombre, descripcion, precio_maximo_venta, existencia_minima, existencia_maxima, comision, fecha_registro, fecha_vence, costo, margen_ganancia, precio_venta, impuestos, lote) LIKE :search
                    ORDER BY fecha_registro DESC" . medidata_tablas_mysql_limit_clause();
            $stmt = $connect->prepare($sql);
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($results);
    }
} catch (PDOException $e) {
    echo json_encode(['message' => 'Error: ' . $e->getMessage()]);
}
