<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/tablas_json_list_limits.php';

try {
    if ($_SERVER["REQUEST_METHOD"] == "GET") {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        if (empty($search)) {
            $sql = "SELECT * FROM proveedor_data ORDER BY fecha_registro DESC" . medidata_tablas_mysql_limit_clause();
        } else {
            $sql = "SELECT * FROM proveedor_data 
                    WHERE nombre_proveedor LIKE :search 
                    OR especialidad LIKE :search 
                    OR identidad LIKE :search 
                    OR colegiado LIKE :search 
                    OR rtn LIKE :search 
                    OR celular LIKE :search 
                    OR correo LIKE :search
                    OR cuenta_bac LIKE :search
                    OR cuenta_si LIKE :search
                    OR cuenta_no LIKE :search
                    OR tipo_cuenta LIKE :search
                    OR constancia_pagos LIKE :search
                    OR solicitud_constancia LIKE :search
                    OR constancia_vigente LIKE :search
                    OR fecha_registro LIKE :search
                    ORDER BY fecha_registro DESC" . medidata_tablas_mysql_limit_clause();
        }

        $stmt = $connect->prepare($sql);
        
        if (!empty($search)) {
            $search_param = '%' . $search . '%';
            $stmt->bindParam(':search', $search_param);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Aquí es donde podrías procesar el archivo al insertar o actualizar
        foreach ($results as &$row) {
            // Suponiendo que el campo de archivo está disponible
            // $nuevo_nombre_archivo es el nombre del archivo subido
            $nuevo_nombre_archivo = $row['archivo_constancia']; // Cambia esto según tu lógica
            $row['archivo_constancia'] = '/uploads/' . basename($nuevo_nombre_archivo); // Guarda la ruta relativa
        }

        echo json_encode($results);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
