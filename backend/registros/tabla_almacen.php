<?php
header('Content-Type: application/json');
require_once '../../backend/bd/Conexion.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            // Obtener todos los registros si no hay búsqueda
            $sql = "SELECT p.*, c.nomcat 
                    FROM product p
                    LEFT JOIN category c ON p.idcat = c.idcat
                    ORDER BY p.fere DESC";
            $stmt = $connect->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Manejar la búsqueda
            $sql = "SELECT p.*, c.nomcat 
                    FROM product p
                    LEFT JOIN category c ON p.idcat = c.idcat
                    WHERE p.codpro LIKE :search 
                    OR p.codbars LIKE :search
                    OR p.nompro LIKE :search
                    OR p.principio_activo LIKE :search
                    OR p.idcat LIKE :search 
                    OR p.preprd LIKE :search 
                    OR p.stock LIKE :search 
                    OR p.state LIKE :search 
                    OR p.fere LIKE :search 
                    OR p.precio_venta LIKE :search 
                    OR p.impuesto LIKE :search 
                    OR p.margen_ganancia LIKE :search 
                    OR p.fecha_vencimiento LIKE :search 
                    OR p.via_administracion LIKE :search 
                    OR p.concentracion LIKE :search 
                    OR p.forma_farmaceutica LIKE :search
                    OR p.presentacion LIKE :search
                    OR p.sub_linea LIKE :search
                    OR p.linea LIKE :search 
                    ORDER BY p.fere DESC";
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
