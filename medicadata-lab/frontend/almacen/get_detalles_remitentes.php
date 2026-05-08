<?php
require '../../backend/bd/Conexion.php';

// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

header('Content-Type: application/json');

if (!isset($_GET['id_factura'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de factura requerido']);
    exit;
}

$id_factura = intval($_GET['id_factura']);

try {
    // Obtener información básica de la factura
    $sql_factura = "SELECT idord, invoice_number, remitente, total_price FROM orders WHERE idord = ?";
    $stmt_factura = $connect->prepare($sql_factura);
    $stmt_factura->execute([$id_factura]);
    $factura_data = $stmt_factura->fetch(PDO::FETCH_ASSOC);
    
    // Buscar el doctor por nombre en el remitente
    $doctor_data = null;
    $idodc = null;
    if (!empty($factura_data['remitente'])) {
        $stmt_doctor = $connect->prepare("SELECT idodc, nodoc, apdoc FROM doctor WHERE CONCAT(nodoc, ' ', apdoc) = ?");
        $stmt_doctor->execute([$factura_data['remitente']]);
        $doctor_data = $stmt_doctor->fetch(PDO::FETCH_ASSOC);
        if ($doctor_data) {
            $idodc = $doctor_data['idodc'];
        }
    }
    
    // Obtener datos de honorario si hay doctor
    $honorario_data = null;
    if ($idodc) {
        $stmt_honorario = $connect->prepare("SELECT * FROM honorarios_medicos WHERE id_factura = ? AND id_doctor = ?");
        $stmt_honorario->execute([$id_factura, $idodc]);
        $honorario_data = $stmt_honorario->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$factura_data) {
        echo json_encode(['error' => 'Factura no encontrada']);
        exit;
    }
    
    // Calcular honorario por servicios usando cuota fija o porcentaje
    $honorario_servicios = 0;
    if ($idodc) {
        // Obtener la configuración de honorario para el médico y cada servicio
        $stmt_servicios = $connect->prepare("
            SELECT od.service_id, od.cantidad, od.total_after_discount, 
                   hc.porcentaje_honorario, hc.cuota_fija
            FROM order_details od
            LEFT JOIN honorarios_configuracion hc ON hc.id_doctor = ? AND hc.id_servicio = od.service_id
            WHERE od.order_id = ? AND od.item_type = 'servicio'
        ");
        $stmt_servicios->execute([$idodc, $id_factura]);
        
        while ($row = $stmt_servicios->fetch(PDO::FETCH_ASSOC)) {
            $cuota_fija = floatval($row['cuota_fija']) ?: 0;
            $porcentaje = floatval($row['porcentaje_honorario']) ?: 0;
            $cantidad = floatval($row['cantidad']) ?: 0;
            $total_serv = floatval($row['total_after_discount']) ?: 0;
            
            // Aplicar cuota fija o porcentaje
            if ($cuota_fija > 0) {
                $honorario = $cuota_fija * $cantidad;
            } else {
                $honorario = $total_serv * ($porcentaje / 100);
            }
            $honorario_servicios += $honorario;
        }
    }
    
    // Calcular descuentos (si aplica)
    $total_descuentos = 0;
    if ($honorario_data) {
        $total_descuentos = ($honorario_data['desc_temporada'] ?? 0) + 
                           ($honorario_data['desc_promo'] ?? 0) + 
                           ($honorario_data['desc_empleado'] ?? 0) + 
                           ($honorario_data['desc_preferencial'] ?? 0);
    }
    
    // Obtener total de comisiones de remitentes
    $stmt_comisiones = $connect->prepare("SELECT COALESCE(SUM(monto_comision), 0) FROM remitentes_honorarios WHERE id_factura = ?");
    $stmt_comisiones->execute([$id_factura]);
    $total_comisiones = $stmt_comisiones->fetchColumn() ?: 0;
    // El honorario del médico es FIJO, no se le resta nada
    $total_final = $honorario_servicios - $total_descuentos; // Solo descuentos manuales si aplica
    
    // Calcular el total hospital final: total factura - honorario médico - comisiones remitentes
    $total_factura = $factura_data['total_price'] ?: 0;
    $total_hospital_final = $total_factura - $honorario_servicios - $total_comisiones;
    
    // Obtener remitentes detallados
    $sql_remitentes = "
    SELECT 
        rh.id,
        rh.id_doctor_remitente,
        rh.factura,
        rh.monto_comision,
        rh.fecha_registro,
        CONCAT(d.nodoc, ' ', d.apdoc) as nombre_medico,
        s.nombre_servicio
    FROM remitentes_honorarios rh
    JOIN doctor d ON rh.id_doctor_remitente = d.idodc
    JOIN servicios_hospital s ON rh.id_servicio = s.id
    WHERE rh.id_factura = ?
    ORDER BY rh.fecha_registro DESC";
    
    $stmt_remitentes = $connect->prepare($sql_remitentes);
    $stmt_remitentes->execute([$id_factura]);
    $remitentes = $stmt_remitentes->fetchAll(PDO::FETCH_ASSOC);
    
    // Preparar respuesta
    $response = [
        'honorario_servicios' => $honorario_servicios,
        'total_descuentos' => $total_descuentos,
        'total_comisiones' => $total_comisiones,
        // Total Honorario Final: honorario médico principal - descuentos (las comisiones remitentes son independientes)
        'total_honorario_final' => $total_final,
        'total_hospital_final' => $total_hospital_final,
        'remitentes' => $remitentes
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener detalles: ' . $e->getMessage()]);
}
?> 