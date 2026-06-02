<?php
// Configurar la zona horaria de Tegucigalpa, Honduras
date_default_timezone_set('America/Tegucigalpa');
$currentDateTime = date('Y-m-d H:i:s'); // Obtener la fecha y hora actual

// Incluir la conexión a la base de datos existente
require_once '../../backend/bd/Conexion.php';

try {
    // Obtener y convertir los datos del formulario a mayúsculas
    $sucursal = strtoupper(trim($_POST['sucursal']));
    $bodega = strtoupper(trim($_POST['bodega']));
    $prov_datos = strtoupper(trim($_POST['prov_datos']));
    $dato_fac = strtoupper(trim($_POST['dato_fac']));
    $fecha_emision = trim($_POST['fecha_emision']);
    $cred_cont = strtoupper(trim($_POST['cred_cont']));
    $dias_credito = trim($_POST['dias_credito'] ?? null);
    $porcentaje_prima = trim($_POST['porcentaje_prima'] ?? null);
    $cuotas_pendientes = trim($_POST['cuotas_pendientes'] ?? null);
    $otros_terminos = strtoupper(trim($_POST['otros_terminos'] ?? null));
    $isv_global = trim($_POST['isv_global']);
    $fech_vence = trim($_POST['fech_vence']);
    $sub_total = trim($_POST['sub_total']);
    $total = trim($_POST['total']);

    // Iniciar la transacción
    $connect->beginTransaction();

    // Insertar la compra en la tabla `medifarma_compras`
    $sql = "INSERT INTO medifarma_compras (
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
        ':dias_credito' => $dias_credito,
        ':porcentaje_prima' => $porcentaje_prima,
        ':cuotas_pendientes' => $cuotas_pendientes,
        ':otros_terminos' => $otros_terminos,
        ':fech_vence' => $fech_vence,
        ':isv_global' => $isv_global,
        ':sub_total' => $sub_total,
        ':total' => $total,
        ':fecha_registro' => $currentDateTime
    ]);

    // Obtener el ID de la compra recién insertada
    $id_compra = $connect->lastInsertId();

    // Insertar los ítems en la tabla `medifarma_detalle_compras`
    $cat_cuenta = array_map('strtoupper', $_POST['cat_cuenta']); // Convertir a mayúsculas
    $codigo_producto = array_map('strtoupper', $_POST['codigo_producto']);
    $cantidad = $_POST['cantidad'];
    $unidad = array_map('strtoupper', $_POST['unidad']);
    $descripcion = array_map('strtoupper', $_POST['descripcion']);
    $precio_unitario = $_POST['precio_unitario'];
    $isv = $_POST['isv'];
    $subtotal = $_POST['subtotal'];
    $total_item = $_POST['total_item'];
    $exento_item = $_POST['exento'];
    $gravado_item = $_POST['gravado'];
    $descuento_porcentaje = $_POST['descuento_porcentaje'];

    $sql_detalle = "INSERT INTO medifarma_detalle_compras (
                        id_compra, cat_cuenta, codigo_producto, cantidad, unidad, descripcion, 
                        precio_unitario, isv, subtotal, total_item, 
                        exento, gravado, descuento_porcentaje
                    ) VALUES (
                        :id_compra, :cat_cuenta, :codigo_producto, :cantidad, :unidad, :descripcion, 
                        :precio_unitario, :isv, :subtotal, :total_item, 
                        :exento, :gravado, :descuento_porcentaje
                    )";

    $stmt_detalle = $connect->prepare($sql_detalle);

    // Recorrer los arrays y agregar cada ítem
    for ($i = 0; $i < count($codigo_producto); $i++) {
        $puLine = round((float) str_replace(',', '.', trim((string) ($precio_unitario[$i] ?? '0'))), 4);
        $stmt_detalle->execute([
            ':id_compra' => $id_compra,
            ':cat_cuenta' => $cat_cuenta[$i],
            ':codigo_producto' => $codigo_producto[$i],
            ':cantidad' => $cantidad[$i],
            ':unidad' => $unidad[$i],
            ':descripcion' => $descripcion[$i],
            ':precio_unitario' => $puLine,
            ':isv' => $isv[$i],
            ':subtotal' => $subtotal[$i],
            ':total_item' => $total_item[$i],
            ':exento' => $exento_item[$i] ?? 0,
            ':gravado' => $gravado_item[$i] ?? 0,
            ':descuento_porcentaje' => $descuento_porcentaje[$i] ?? 0
        ]);
    }

    // Confirmar la transacción
    $connect->commit();

    echo "<script>
        Swal.fire({
            title: 'Compra Medifarma Guardada',
            text: 'La compra de Medifarma se ha registrado correctamente.',
            icon: 'success',
            button: 'OK',
        }).then(function() {
            window.location = 'mostrar_medifarma_compras.php';
        });
    </script>";

} catch (PDOException $e) {
    $connect->rollBack();
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: 'Error en la conexión: " . $e->getMessage() . "',
            icon: 'error',
            button: 'OK',
        });
    </script>";
}
?> 