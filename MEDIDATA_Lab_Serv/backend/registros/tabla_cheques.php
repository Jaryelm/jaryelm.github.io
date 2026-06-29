<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            $sql = "SELECT * FROM emitir_cheques ORDER BY fecha DESC" . medidata_tablas_mysql_limit_clause();
            $stmt = $connect->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Manejar la búsqueda
            $sql = "SELECT * FROM emitir_cheques 
                    WHERE CONCAT_WS(' ', cuenta, balance, impuestos, proveedor_RTN, cheque_no, pagar, fecha, cantidad, concepto, asignar_monto, monto, proyecto, imp_ventas, total_asignado, impuesto, fuera_balance, total_pagado) LIKE :search
                    ORDER BY fecha DESC" . medidata_tablas_mysql_limit_clause();
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