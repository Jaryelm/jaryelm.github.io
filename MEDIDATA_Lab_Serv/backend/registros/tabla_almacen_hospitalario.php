<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            $sql = "SELECT p.*, c.nomcat 
                    FROM almacen_hospitalario p
                    LEFT JOIN category c ON p.idcat = c.idcat
                    ORDER BY p.fere DESC" . medidata_tablas_mysql_limit_clause();
            $stmt = $connect->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Manejar la búsqueda
            $sql = "SELECT p.*, c.nomcat 
                    FROM almacen_hospitalario p
                    LEFT JOIN category c ON p.idcat = c.idcat
                    WHERE CONCAT_WS(' ', p.codpro, p.codbars, p.nompro, p.principio_activo, p.idcat, p.preprd, p.stock, p.state, p.fere, p.precio_venta, p.impuesto, p.margen_ganancia, p.fecha_vencimiento, p.via_administracion, p.concentracion, p.forma_farmaceutica, p.presentacion, p.sub_linea, p.linea) LIKE :search
                    ORDER BY p.fere DESC" . medidata_tablas_mysql_limit_clause();
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