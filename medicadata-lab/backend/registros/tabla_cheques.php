<?php
header('Content-Type: application/json');
require_once '../../backend/bd/Conexion.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';

        if (empty($search)) {
            // Obtener todos los registros si no hay búsqueda
            $sql = "SELECT * FROM emitir_cheques ORDER BY fecha DESC";
            $stmt = $connect->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Manejar la búsqueda
            $sql = "SELECT * FROM emitir_cheques 
                    WHERE cuenta LIKE :search 
                    OR balance LIKE :search 
                    OR impuestos LIKE :search 
                    OR proveedor_RTN LIKE :search 
                    OR cheque_no LIKE :search 
                    OR pagar LIKE :search 
                    OR fecha LIKE :search 
                    OR cantidad LIKE :search 
                    OR concepto LIKE :search 
                    OR asignar_monto LIKE :search 
                    OR monto LIKE :search 
                    OR proyecto LIKE :search 
                    OR imp_ventas LIKE :search 
                    OR total_asignado LIKE :search 
                    OR impuesto LIKE :search 
                    OR fuera_balance LIKE :search 
                    OR total_pagado LIKE :search
                    ORDER BY fecha DESC";
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