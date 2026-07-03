<?php
require_once '../../backend/bd/Conexion.php';

// Consultar las compras desde la base de datos
try {
    $sql = "SELECT id_compra, sucursal, bodega, prov_datos, dato_fac, fecha_emision, cred_cont, dias_credito, porcentaje_prima, cuotas_pendientes, fech_vence, isv_global, sub_total, total, fecha_registro FROM compras ORDER BY fecha_registro DESC";
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $compras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Evitar null en JSON: en plantillas JS `${x}` muestra el texto "null"
    foreach ($compras as &$row) {
        $row['dias_credito'] = ($row['dias_credito'] !== null && $row['dias_credito'] !== '')
            ? (int) $row['dias_credito'] : 0;
        $row['cuotas_pendientes'] = ($row['cuotas_pendientes'] !== null && $row['cuotas_pendientes'] !== '')
            ? (int) $row['cuotas_pendientes'] : 0;
        $p = $row['porcentaje_prima'];
        $row['porcentaje_prima'] = ($p !== null && $p !== '')
            ? number_format((float) $p, 2, '.', '') : '0.00';
    }
    unset($row);

    echo json_encode($compras);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}