<?php
require_once '../../backend/bd/Conexion.php';

// Consultar las compras de medifarma desde la base de datos
try {
    $sql = "SELECT id_compra, sucursal, bodega, prov_datos, dato_fac, fecha_emision, cred_cont, dias_credito, porcentaje_prima, cuotas_pendientes, fech_vence, isv_global, sub_total, total, fecha_registro FROM medifarma_compras ORDER BY fecha_registro DESC";
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Enviar los datos en formato JSON
    echo json_encode($compras);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?> 