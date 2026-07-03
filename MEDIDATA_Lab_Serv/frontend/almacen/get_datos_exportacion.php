<?php
require '../../backend/bd/Conexion.php';

$facturas = $_POST['facturas'] ?? [];
$resultado = [];

// Almacena los logs en un array temporal
$debug = [];
function debug_log($msg) {
    global $debug;
    $debug[] = date('Y-m-d H:i:s') . ' ' . $msg;
}

debug_log('--- NUEVA EXPORTACION ---');

function sumar_columna($arr, $key) {
    $suma = 0;
    foreach ($arr as $d) {
        $valor = isset($d[$key]) ? str_replace([',', 'LPS.', 'LPS', ' '], '', $d[$key]) : 0;
        if (is_numeric($valor)) $suma += (float)$valor;
    }
    return $suma > 0 ? number_format($suma, 2, '.', '') : '-';
}

foreach ($facturas as $factura) {
    debug_log("Factura: $factura");
    // Buscar el ID de la factura (idord) a partir del número de factura
    $stmt = $connect->prepare("SELECT idord FROM orders WHERE invoice_number = ?");
    $stmt->execute([$factura]);
    $idord = $stmt->fetchColumn();
    debug_log("idord: $idord");
    if (!$idord) continue;

    // Totales de factura
    $stmt = $connect->prepare("SELECT price_without_discount, total_price FROM orders WHERE idord = ?");
    $stmt->execute([$idord]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Totales: " . json_encode($row));
    $resultado[$factura]['totales'] = [
        'factura' => $factura,
        'total_sin_descuento' => $row['price_without_discount'] ?? '-',
        'total_con_descuento' => $row['total_price'] ?? '-'
    ];

    // Detalles de productos/servicios (igual que en obtener_detalles_checkout.php)
    $stmt = $connect->prepare("
        SELECT 
            od.codpro AS codigo,
            od.descripcion AS nombre,
            od.cantidad,
            od.discount_percentage,
            od.age_discount_30,
            od.age_discount_40,
            od.promotion_discount,
            od.other_discount,
            od.total_discount,
            od.total_after_discount,
            (
                CASE 
                    WHEN od.item_type = 'producto' AND od.product_id IS NOT NULL THEN od.cantidad * p.precio_venta
                    WHEN od.item_type = 'producto' AND od.hospitalario_id IS NOT NULL THEN od.cantidad * ah.precio_venta
                    WHEN od.item_type = 'servicio' THEN od.cantidad * s.total
                    ELSE 0 
                END
            ) AS total_original
        FROM order_details od
        LEFT JOIN product p ON od.product_id = p.idprcd AND od.item_type = 'producto'
        LEFT JOIN almacen_hospitalario ah ON od.hospitalario_id = ah.idprcd AND od.item_type = 'producto'
        LEFT JOIN servicios_hospital s ON od.service_id = s.id AND od.item_type = 'servicio'
        WHERE od.order_id = ?
    ");
    $stmt->execute([$idord]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Detalles: " . json_encode($detalles));
    $concat = function($arr, $key) {
        if (empty($arr)) return '-';
        if (is_callable($key)) {
            return implode("\n", array_map($key, $arr));
        }
        return implode("\n", array_map(function($d) use ($key) {
            return isset($d[$key]) && $d[$key] !== '' ? $d[$key] : '-';
        }, $arr));
    };
    $resultado[$factura]['detalles'] = [
        'codigo' => $concat($detalles, 'codigo'),
        'nombre' => $concat($detalles, 'nombre'),
        'cantidad' => sumar_columna($detalles, 'cantidad'),
        'total' => sumar_columna($detalles, 'total_original'),
        'descuento_general' => sumar_columna($detalles, 'discount_percentage'),
        'descuento_3ra' => sumar_columna($detalles, 'age_discount_30'),
        'descuento_4ta' => sumar_columna($detalles, 'age_discount_40'),
        'promocion' => sumar_columna($detalles, 'promotion_discount'),
        'otros_descuentos' => sumar_columna($detalles, 'other_discount'),
        'descuentos_aplicados' => sumar_columna($detalles, 'total_discount'),
        'total_pagar' => sumar_columna($detalles, 'total_after_discount')
    ];

    // Honorarios por servicio (igual que en get_servicios_honorario.php)
    $stmt = $connect->prepare("SELECT d.idodc FROM orders o LEFT JOIN doctor d ON CONCAT(d.nodoc, ' ', d.apdoc) = o.remitente WHERE o.idord = ?");
    $stmt->execute([$idord]);
    $id_doctor = $stmt->fetchColumn();
    debug_log("id_doctor: $id_doctor");
    
    // === NUEVO: Descuentos manuales de honorarios ===
    $descuentos_honorarios = [
        'desc_temporada' => '-',
        'desc_promo' => '-',
        'desc_empleado' => '-',
        'desc_preferencial' => '-',
        'notas_ajuste' => '-',
    ];
    if ($id_doctor) {
        $stmt = $connect->prepare("SELECT desc_temporada, desc_promo, desc_empleado, desc_preferencial, notas_ajuste FROM honorarios_medicos WHERE id_factura = ? AND id_doctor = ?");
        $stmt->execute([$idord, $id_doctor]);
        $row_desc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row_desc) {
            $desc_temporada = is_numeric($row_desc['desc_temporada']) ? (float)$row_desc['desc_temporada'] : 0;
            $desc_promo = is_numeric($row_desc['desc_promo']) ? (float)$row_desc['desc_promo'] : 0;
            $desc_empleado = is_numeric($row_desc['desc_empleado']) ? (float)$row_desc['desc_empleado'] : 0;
            $desc_preferencial = is_numeric($row_desc['desc_preferencial']) ? (float)$row_desc['desc_preferencial'] : 0;
            $notas_ajuste = $row_desc['notas_ajuste'] !== null ? $row_desc['notas_ajuste'] : '-';
            $descuentos_honorarios = [
                'desc_temporada' => $desc_temporada,
                'desc_promo' => $desc_promo,
                'desc_empleado' => $desc_empleado,
                'desc_preferencial' => $desc_preferencial,
                'notas_ajuste' => $notas_ajuste,
            ];
            $resultado[$factura]['total_descuentos_honorarios'] = number_format($desc_temporada + $desc_promo + $desc_empleado + $desc_preferencial, 2, '.', '');
        } else {
            $descuentos_honorarios = [
                'desc_temporada' => '-',
                'desc_promo' => '-',
                'desc_empleado' => '-',
                'desc_preferencial' => '-',
                'notas_ajuste' => '-',
            ];
            $resultado[$factura]['total_descuentos_honorarios'] = '-';
        }
    }
    $resultado[$factura]['descuentos_honorarios'] = $descuentos_honorarios;

    if ($id_doctor) {
        $sql_servicios = "
            SELECT 
                od.descripcion AS nombre_servicio,
                od.cantidad,
                s.precio_venta AS precio_unitario,
                (s.precio_venta * od.cantidad) AS subtotal,
                COALESCE(hc.porcentaje_honorario, 0) AS porcentaje_honorario,
                COALESCE(hc.cuota_fija, 0) AS cuota_fija,
                CASE 
                    WHEN COALESCE(hc.cuota_fija, 0) > 0 THEN hc.cuota_fija * od.cantidad
                    ELSE s.precio_venta * od.cantidad * (COALESCE(hc.porcentaje_honorario, 0) / 100)
                END AS honorario_calculado
            FROM order_details od
            JOIN servicios_hospital s ON od.service_id = s.id
            LEFT JOIN honorarios_configuracion hc ON hc.id_doctor = :id_doctor AND hc.id_servicio = s.id
            WHERE od.order_id = :id_factura_outer AND od.item_type = 'servicio'
        ";
        $stmt = $connect->prepare($sql_servicios);
        $stmt->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
        $stmt->bindParam(':id_factura_outer', $idord, PDO::PARAM_INT);
        $stmt->execute();
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        debug_log("Servicios: " . json_encode($servicios));
        $resultado[$factura]['servicios'] = [
            'servicio' => $concat($servicios, 'nombre_servicio'),
            'cantidad' => sumar_columna($servicios, 'cantidad'),
            'precio_unitario' => sumar_columna($servicios, 'precio_unitario'),
            'porcentaje_honorario' => sumar_columna($servicios, 'porcentaje_honorario'),
            'monto_honorario' => sumar_columna($servicios, 'honorario_calculado')
        ];
    } else {
        $resultado[$factura]['servicios'] = [
            'servicio' => '-',
            'cantidad' => '-',
            'precio_unitario' => '-',
            'porcentaje_honorario' => '-',
            'monto_honorario' => '-'
        ];
    }

    // Remitentes (igual que en get_detalles_remitentes.php)
    $stmt = $connect->prepare("
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
        ORDER BY rh.fecha_registro DESC
    ");
    $stmt->execute([$idord]);
    $remitentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    debug_log("Remitentes: " . json_encode($remitentes));
    $resultado[$factura]['remitentes'] = [
        'medico' => $concat($remitentes, function($r) { return ($r['nombre_medico'] ?? '-'); }),
        'servicio' => $concat($remitentes, 'nombre_servicio'),
        'comision' => sumar_columna($remitentes, 'monto_comision'),
        'fecha' => $concat($remitentes, 'fecha_registro')
    ];

    // === Calcular Total Honorario Final y Total Hospital Final ===
    // Calcular honorario total sobre el total_after_discount de servicios
    $honorario_total = 0;
    if ($id_doctor) {
        $sql_serv = "SELECT od.service_id, od.total_after_discount FROM order_details od WHERE od.order_id = ? AND od.item_type = 'servicio'";
        $stmt_serv = $connect->prepare($sql_serv);
        $stmt_serv->execute([$idord]);
        $servicios = $stmt_serv->fetchAll(PDO::FETCH_ASSOC);
        foreach ($servicios as $serv) {
            $stmt_config = $connect->prepare("SELECT porcentaje_honorario, cuota_fija FROM honorarios_configuracion WHERE id_doctor = ? AND id_servicio = ?");
            $stmt_config->execute([$id_doctor, $serv['service_id']]);
            $config = $stmt_config->fetch(PDO::FETCH_ASSOC);
            
            $cuota_fija = $config ? floatval($config['cuota_fija']) : 0;
            $porcentaje = $config ? floatval($config['porcentaje_honorario']) : 0;
            
            // Obtener cantidad del servicio
            $stmt_cantidad = $connect->prepare("SELECT cantidad FROM order_details WHERE order_id = ? AND service_id = ? AND item_type = 'servicio'");
            $stmt_cantidad->execute([$idord, $serv['service_id']]);
            $cantidad = floatval($stmt_cantidad->fetchColumn()) ?: 1;
            
            // Aplicar cuota fija o porcentaje
            if ($cuota_fija > 0) {
                $honorario_total += $cuota_fija * $cantidad;
            } else {
                $honorario_total += ($serv['total_after_discount'] ?? 0) * ($porcentaje / 100);
            }
        }
    }
    $total_descuentos = isset($desc_temporada) ? $desc_temporada : 0;
    $total_descuentos += isset($desc_promo) ? $desc_promo : 0;
    $total_descuentos += isset($desc_empleado) ? $desc_empleado : 0;
    $total_descuentos += isset($desc_preferencial) ? $desc_preferencial : 0;
    $total_comisiones = $resultado[$factura]['remitentes']['comision'] ?? 0;
    $total_comisiones = is_numeric($total_comisiones) ? (float)$total_comisiones : 0;
    // El honorario del médico es FIJO, no se le resta la comisión de remitentes
    $total_final = floatval($honorario_total) - floatval($total_descuentos);
    $total_price_num = isset($row['total_price']) ? floatval($row['total_price']) : 0;
    // El total hospital final es: total factura - honorario médico - comisiones remitentes
    $total_hospital_final = $total_price_num - $honorario_total - $total_comisiones;
    $resultado[$factura]['total_honorario_final'] = number_format($total_final, 2, '.', '');
    $resultado[$factura]['total_hospital_final'] = number_format($total_hospital_final, 2, '.', '');
}

// Al final, incluye los logs en la respuesta JSON SOLO si hay un parámetro especial de depuración
if (isset($_GET['debug']) || isset($_POST['debug'])) {
    $resultado['__debug'] = $debug;
}

header('Content-Type: application/json');
echo json_encode($resultado); 