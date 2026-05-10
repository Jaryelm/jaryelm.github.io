<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');
session_start();
date_default_timezone_set('America/Tegucigalpa');

function limpiar_dni($dni) {
    // Quita guiones, espacios y todo lo que no sea número
    return preg_replace('/[^0-9]/', '', $dni);
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $patient_id = $input['patient_id'] ?? '';
    $dni_limpio = limpiar_dni($patient_id);
    
    if (!$dni_limpio) {
        throw new Exception('DNI del paciente no válido');
    }
    
    // Consulta SOLO por DNI limpio y estado pendiente
    $sql = "
        SELECT 
            o.idord,
            o.invoice_number,
            o.nomcl,
            o.dni_paciente,
            o.total_price,
            o.placed_on,
            o.remitente,
            o.tipc,
            o.invoice_status
        FROM orders o
        WHERE LOWER(TRIM(o.invoice_status)) = 'pendiente'
          AND REPLACE(REPLACE(o.dni_paciente, '-', ''), ' ', '') = ?
        ORDER BY o.placed_on DESC
        LIMIT 20
    ";
    $params = [$dni_limpio];
    
    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $facturas_procesadas = [];
    foreach ($facturas as $factura) {
        $facturas_procesadas[] = [
            'idord' => $factura['idord'],
            'invoice_number' => $factura['invoice_number'],
            'nomcl' => $factura['nomcl'],
            'dni_paciente' => $factura['dni_paciente'],
            'total_price' => $factura['total_price'],
            'placed_on' => $factura['placed_on'],
            'remitente' => $factura['remitente'],
            'tipc' => $factura['tipc'],
            'invoice_status' => $factura['invoice_status'],
            'fecha_formateada' => date('d/m/Y H:i', strtotime($factura['placed_on']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'facturas' => $facturas_procesadas,
        'total_encontradas' => count($facturas_procesadas),
        'criterios_busqueda' => [
            'dni_limpio' => $dni_limpio
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error obteniendo facturas disponibles: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'facturas' => []
    ]);
}
?> 