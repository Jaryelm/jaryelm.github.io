<?php
/** Flujo anterior (sin line_mode / sin product_id / sin tocar stock). Usado por compras_seg.php. */
if (!isset($_POST['add_medicine'])) {
    return;
}

date_default_timezone_set('America/Tegucigalpa');
$currentDateTime = date('Y-m-d H:i:s');
require_once __DIR__ . '/../bd/Conexion.php';

if (!function_exists('medidata_parse_precio_unitario_compra')) {
    function medidata_parse_precio_unitario_compra($raw): float
    {
        $s = str_replace(',', '.', trim((string) $raw));
        if ($s === '' || !is_numeric($s)) {
            return 0.0;
        }

        return round((float) $s, 4);
    }
}

if (!function_exists('medidata_parse_decimal_compra')) {
    function medidata_parse_decimal_compra($raw, int $decimals = 2): float
    {
        $s = str_replace(',', '.', trim((string) $raw));
        if ($s === '' || !is_numeric($s)) {
            return 0.0;
        }

        return round((float) $s, $decimals);
    }
}

try {
    $sucursal = strtoupper(trim($_POST['sucursal']));
    $bodega = strtoupper(trim($_POST['bodega']));
    $prov_datos = strtoupper(trim($_POST['prov_datos']));
    $dato_fac = strtoupper(trim($_POST['dato_fac']));
    $fecha_emision = trim($_POST['fecha_emision']);
    $cred_cont = strtoupper(trim($_POST['cred_cont']));
    $dias_credito_raw = trim((string) ($_POST['dias_credito'] ?? ''));
    $porcentaje_prima_raw = trim((string) ($_POST['porcentaje_prima'] ?? ''));
    $cuotas_pendientes_raw = trim((string) ($_POST['cuotas_pendientes'] ?? ''));

    $dias_credito_val = ($dias_credito_raw !== '' && is_numeric($dias_credito_raw)) ? (int) $dias_credito_raw : 0;
    $porcentaje_prima_val = ($porcentaje_prima_raw !== '' && is_numeric($porcentaje_prima_raw)) ? (float) $porcentaje_prima_raw : 0.0;
    $cuotas_pendientes_val = ($cuotas_pendientes_raw !== '' && is_numeric($cuotas_pendientes_raw)) ? (int) $cuotas_pendientes_raw : 0;

    $otros_terminos = null;
    if ($cred_cont === 'PRIMA') {
        $otros_terminos = 'Prima: ' . $porcentaje_prima_val . '%, Cuotas: ' . $cuotas_pendientes_val;
    } elseif ($cred_cont === 'CONSIGNACION') {
        $otros_terminos = 'CONSIGNACION';
    }

    $isv_global = trim($_POST['isv_global']);
    $fech_vence = trim($_POST['fech_vence']);
    $sub_total = trim($_POST['sub_total']);
    $total = trim($_POST['total']);

    $connect->beginTransaction();

    $chkFac = $connect->prepare('SELECT COUNT(*) FROM compras WHERE dato_fac = ?');
    $chkFac->execute([$dato_fac]);
    if ((int) $chkFac->fetchColumn() > 0) {
        throw new Exception('Ya existe una compra registrada con este número de factura. No se puede duplicar.');
    }

    $sql = "INSERT INTO compras (
                sucursal, bodega, prov_datos, dato_fac, fecha_emision, cred_cont, dias_credito,
                porcentaje_prima, cuotas_pendientes, otros_terminos, fech_vence, isv_global, sub_total, total, fecha_registro
            ) VALUES (
                :sucursal, :bodega, :prov_datos, :dato_fac, :fecha_emision, :cred_cont, :dias_credito,
                :porcentaje_prima, :cuotas_pendientes, :otros_terminos, :fech_vence, :isv_global, :sub_total, :total, :fecha_registro
            )";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':sucursal' => $sucursal,
        ':bodega' => $bodega,
        ':prov_datos' => $prov_datos,
        ':dato_fac' => $dato_fac,
        ':fecha_emision' => $fecha_emision,
        ':cred_cont' => $cred_cont,
        ':dias_credito' => $dias_credito_val,
        ':porcentaje_prima' => $porcentaje_prima_val,
        ':cuotas_pendientes' => $cuotas_pendientes_val,
        ':otros_terminos' => $otros_terminos,
        ':fech_vence' => $fech_vence,
        ':isv_global' => $isv_global,
        ':sub_total' => $sub_total,
        ':total' => $total,
        ':fecha_registro' => $currentDateTime,
    ]);

    $id_compra = $connect->lastInsertId();

    $cat_cuenta = array_map('strtoupper', $_POST['cat_cuenta']);
    $codigo_producto = array_map('strtoupper', $_POST['codigo_producto']);
    $cantidad = $_POST['cantidad'];
    $unidad = array_map('strtoupper', $_POST['unidad']);
    $descripcion = array_map('strtoupper', $_POST['descripcion']);
    $precio_unitario = $_POST['precio_unitario'];
    $isv = $_POST['isv'];
    $subtotal = $_POST['subtotal'];
    $total_item = $_POST['total_item'];
    $descuento_porcentaje = $_POST['descuento_porcentaje'];

    $hasProductId = $connect->query("SHOW COLUMNS FROM detalle_compras LIKE 'product_id'")->fetch(PDO::FETCH_ASSOC);

    if ($hasProductId) {
        $sql_detalle = "INSERT INTO detalle_compras (
                            id_compra, product_id, cat_cuenta, codigo_producto, cantidad, unidad, descripcion,
                            precio_unitario, isv, subtotal, total_item,
                            exento, gravado, descuento_porcentaje
                        ) VALUES (
                            :id_compra, NULL, :cat_cuenta, :codigo_producto, :cantidad, :unidad, :descripcion,
                            :precio_unitario, :isv, :subtotal, :total_item,
                            :exento, :gravado, :descuento_porcentaje
                        )";
    } else {
        $sql_detalle = "INSERT INTO detalle_compras (
                            id_compra, cat_cuenta, codigo_producto, cantidad, unidad, descripcion,
                            precio_unitario, isv, subtotal, total_item,
                            exento, gravado, descuento_porcentaje
                        ) VALUES (
                            :id_compra, :cat_cuenta, :codigo_producto, :cantidad, :unidad, :descripcion,
                            :precio_unitario, :isv, :subtotal, :total_item,
                            :exento, :gravado, :descuento_porcentaje
                        )";
    }

    $stmt_detalle = $connect->prepare($sql_detalle);

    for ($i = 0; $i < count($codigo_producto); $i++) {
        $isvVal = (float) ($isv[$i] ?? 0);
        $gravado = ($isvVal > 0.00001) ? 1 : 0;
        $exento = $gravado ? 0 : 1;
        $puLine = medidata_parse_precio_unitario_compra($precio_unitario[$i] ?? '0');
        $params = [
            ':id_compra' => $id_compra,
            ':cat_cuenta' => $cat_cuenta[$i],
            ':codigo_producto' => $codigo_producto[$i],
            ':cantidad' => $cantidad[$i],
            ':unidad' => $unidad[$i],
            ':descripcion' => $descripcion[$i],
            ':precio_unitario' => $puLine,
            ':isv' => medidata_parse_decimal_compra($isv[$i] ?? 0),
            ':subtotal' => medidata_parse_decimal_compra($subtotal[$i] ?? 0),
            ':total_item' => medidata_parse_decimal_compra($total_item[$i] ?? 0),
            ':exento' => $exento,
            ':gravado' => $gravado,
            ':descuento_porcentaje' => medidata_parse_decimal_compra($descuento_porcentaje[$i] ?? 0),
        ];
        $stmt_detalle->execute($params);
    }

    require_once __DIR__ . '/../php/partida_compra_proveedor.php';
    $usuarioPartidaLegacy = isset($_SESSION['name']) ? (string) $_SESSION['name'] : 'Sistema';
    medidata_generar_partida_desde_compra(
        $connect,
        (int) $id_compra,
        $usuarioPartidaLegacy,
        $sucursal !== '' ? $sucursal : 'Hospital Medicasa'
    );

    $connect->commit();

    echo "<script>
        Swal.fire({
            title: 'Compra Guardada',
            text: 'La compra se ha registrado correctamente.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(function() {
            window.location = 'mostrar_compras.php';
        });
    </script>";
} catch (PDOException $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    echo "<script>Swal.fire({title:'Error',text:'" . addslashes($e->getMessage()) . "',icon:'error',confirmButtonText:'OK'});</script>";
} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    echo "<script>Swal.fire({title:'Error',text:'" . addslashes($e->getMessage()) . "',icon:'error',confirmButtonText:'OK'});</script>";
}
