<?php
header('Content-Type: application/json');
require_once '../../backend/bd/Conexion.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            // Obtener todos los registros si no hay búsqueda
            $sql = "SELECT * FROM registros_articulos ORDER BY fecha_registro DESC";
            $stmt = $connect->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Manejar la búsqueda
            $sql = "SELECT * FROM registros_articulos 
                    WHERE linea LIKE :search 
                    OR sub_linea LIKE :search 
                    OR sucursal_bodega LIKE :search 
                    OR envase LIKE :search 
                    OR farmaceutica LIKE :search 
                    OR concentracion LIKE :search 
                    OR via_administracion LIKE :search 
                    OR codigo_articulo LIKE :search 
                    OR nombre LIKE :search 
                    OR descripcion LIKE :search 
                    OR precio_max_venta LIKE :search 
                    OR existencia_min LIKE :search 
                    OR existencia_max LIKE :search 
                    OR comision LIKE :search 
                    OR fecha_registro LIKE :search
                    OR fecha_vence LIKE :search
                    OR existencia_actual LIKE :search
                    OR costo LIKE :search 
                    OR margen_ganancia LIKE :search 
                    OR precio_venta LIKE :search 
                    OR impuestos LIKE :search 
                    OR lote LIKE :search
                    ORDER BY fecha_registro DESC";
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
