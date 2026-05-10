<?php
/**
 * Registro de compras + actualización de product.stock y preprd (promedio ponderado).
 * Requiere columna detalle_compras.product_id (ver database backup/alter_detalle_compras_product_id.sql).
 */
if (!isset($_POST['add_medicine'])) {
    return;
}

require_once __DIR__ . '/../bd/Conexion.php';

// compras_seg.php y formularios sin line_mode → solo detalle (sin tocar stock)
if (!isset($_POST['line_mode'])) {
    require __DIR__ . '/reg_compras_legacy.php';
    return;
}

date_default_timezone_set('America/Tegucigalpa');
$currentDateTime = date('Y-m-d H:i:s');

/**
 * Calcula precio de venta como en add_medicine.php
 */
if (!function_exists('medidata_calc_precio_venta')) {
    function medidata_calc_precio_venta(float $preprd, float $margenPct, string $impuesto): float
    {
        $precioVenta = $preprd + ($preprd * ($margenPct / 100));
        if ($impuesto === 'G') {
            $precioVenta += $precioVenta * 0.15;
        }
        return round($precioVenta, 2);
    }
}

/**
 * Precio unitario en líneas de compra (hasta 4 decimales; ver
 * database backup/alter_detalle_precio_unitario_precision.sql).
 */
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

/**
 * Normaliza cantidad de stock para columnas INT UNSIGNED (product.stock,
 * almacen_hospitalario.stock). Tras migración alter_product_stock_int_unsigned.sql
 * ya no aplica el tope artificial de 999.
 */
if (!function_exists('medidata_normalize_product_stock')) {
    function medidata_normalize_product_stock(int $n): int
    {
        if ($n < 0) {
            throw new Exception('El stock no puede ser negativo.');
        }
        if ($n > 4294967295) {
            throw new Exception('Cantidad de stock fuera del rango permitido (máx. 4.294.967.295).');
        }
        return $n;
    }
}

try {
    $colChk = $connect->query("SHOW COLUMNS FROM detalle_compras LIKE 'product_id'")->fetch(PDO::FETCH_ASSOC);
    if (!$colChk) {
        throw new Exception('Falta la columna product_id en detalle_compras. Ejecute: database backup/alter_detalle_compras_product_id.sql');
    }

    $sucursal = strtoupper(trim($_POST['sucursal'] ?? ''));
    $bodega = strtoupper(trim($_POST['bodega'] ?? ''));
    $prov_datos = strtoupper(trim($_POST['prov_datos'] ?? ''));
    $dato_fac = strtoupper(trim($_POST['dato_fac'] ?? ''));
    $fecha_emision = trim($_POST['fecha_emision'] ?? '');
    $cred_cont = strtoupper(trim($_POST['cred_cont'] ?? ''));
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

    $isv_global = trim($_POST['isv_global'] ?? '0');
    $fech_vence = trim($_POST['fech_vence'] ?? '');
    $sub_total = trim($_POST['sub_total'] ?? '0');
    $total = trim($_POST['total'] ?? '0');

    $codigo_producto = $_POST['codigo_producto'] ?? [];
    if (!is_array($codigo_producto) || count($codigo_producto) === 0) {
        throw new Exception('Debe agregar al menos una línea de detalle.');
    }

    $line_mode = $_POST['line_mode'] ?? [];
    $product_id_prcd = $_POST['product_id_prcd'] ?? [];

    $connect->beginTransaction();

    // Factura duplicada
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

    $id_compra = (int) $connect->lastInsertId();

    $cat_cuenta = array_map(function ($v) {
        return strtoupper(trim((string) $v));
    }, $_POST['cat_cuenta'] ?? []);
    $codigo_producto = array_map(function ($v) {
        return strtoupper(trim((string) $v));
    }, $_POST['codigo_producto'] ?? []);
    $cantidad = $_POST['cantidad'] ?? [];
    $unidad = array_map(function ($v) {
        return strtoupper(trim((string) $v));
    }, $_POST['unidad'] ?? []);
    $descripcion = array_map(function ($v) {
        return strtoupper(trim((string) $v));
    }, $_POST['descripcion'] ?? []);
    $precio_unitario = $_POST['precio_unitario'] ?? [];
    $isv = $_POST['isv'] ?? [];
    $subtotal = $_POST['subtotal'] ?? [];
    $total_item = $_POST['total_item'] ?? [];
    $line_tax = $_POST['line_tax'] ?? [];
    $descuento_porcentaje = $_POST['descuento_porcentaje'] ?? [];

    $sql_detalle = "INSERT INTO detalle_compras (
                        id_compra, product_id, cat_cuenta, codigo_producto, cantidad, unidad, descripcion,
                        precio_unitario, isv, subtotal, total_item,
                        exento, gravado, descuento_porcentaje
                    ) VALUES (
                        :id_compra, :product_id, :cat_cuenta, :codigo_producto, :cantidad, :unidad, :descripcion,
                        :precio_unitario, :isv, :subtotal, :total_item,
                        :exento, :gravado, :descuento_porcentaje
                    )";

    $stmt_detalle = $connect->prepare($sql_detalle);

    $selProduct = $connect->prepare('SELECT stock, preprd, margen_ganancia, impuesto FROM product WHERE idprcd = ? LIMIT 1');
    $updProduct = $connect->prepare('UPDATE product SET stock = ?, preprd = ?, precio_venta = ? WHERE idprcd = ? LIMIT 1');

    $insProduct = $connect->prepare("INSERT INTO product(
            codpro, codbars, nompro, principio_activo, idcat, preprd, stock, stock_minimo, state, fecha_vencimiento, via_administracion,
            concentracion, forma_farmaceutica, presentacion, sub_linea, linea, margen_ganancia,
            impuesto, precio_venta, adj_foto, fere
        ) VALUES (
            :codpro, :codbars, :nompro, :principio_activo, :idcat, :preprd, :stock, :stock_minimo, '1', :fecha_vencimiento, :via_administracion,
            :concentracion, :forma_farmaceutica, :presentacion, :sub_linea, :linea, :margen_ganancia,
            :impuesto, :precio_venta, :adj_foto, :fere
        )");

    $n = count($codigo_producto);
    $uploadDir = dirname(__DIR__, 2) . '/uploads/adj_foto/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    for ($i = 0; $i < $n; $i++) {
        $mode = strtolower(trim((string) ($line_mode[$i] ?? 'existing')));
        $qty = (int) ($cantidad[$i] ?? 0);
        if ($qty < 1) {
            throw new Exception('La cantidad debe ser mayor a cero en la línea ' . ($i + 1) . '.');
        }
        $pu = medidata_parse_precio_unitario_compra($precio_unitario[$i] ?? '0');
        if ($pu < 0) {
            throw new Exception('Precio unitario inválido en la línea ' . ($i + 1) . '.');
        }

        $lt = strtoupper(trim((string) ($line_tax[$i] ?? 'E')));
        if (!in_array($lt, ['E', 'G'], true)) {
            $lt = 'E';
        }
        $exento = ($lt === 'E') ? 1 : 0;
        $gravado = ($lt === 'G') ? 1 : 0;

        $pid = null;

        if ($mode === 'new') {
            $nompro = strtoupper(trim((string) ($_POST['np_nompro'][$i] ?? '')));
            if ($nompro === '') {
                throw new Exception('Línea ' . ($i + 1) . ': indique el nombre del producto nuevo.');
            }
            $linea = strtoupper(trim((string) ($_POST['np_linea'][$i] ?? '')));
            $sub_linea = strtoupper(trim((string) ($_POST['np_sub_linea'][$i] ?? '')));
            $codpro = strtoupper(trim((string) ($_POST['np_codpro'][$i] ?? '')));
            $codbars = strtoupper(trim((string) ($_POST['np_codbars'][$i] ?? '')));
            $principio = strtoupper(trim((string) ($_POST['np_principio_activo'][$i] ?? '')));
            $idcat = trim((string) ($_POST['np_medicate'][$i] ?? ''));
            $idcat = ($idcat === '' || $idcat === '0') ? null : $idcat;
            $presentacion = strtoupper(trim((string) ($_POST['np_presentacion'][$i] ?? '')));
            $forma_farmaceutica = strtoupper(trim((string) ($_POST['np_forma_farmaceutica'][$i] ?? '')));
            $concentracion = strtoupper(trim((string) ($_POST['np_concentracion'][$i] ?? '')));
            $via = strtoupper(trim((string) ($_POST['np_via_administracion'][$i] ?? '')));
            $margen = floatval($_POST['np_margen_ganancia'][$i] ?? 0);
            $imp = strtoupper(trim((string) ($_POST['np_impuesto'][$i] ?? '')));
            if (!in_array($imp, ['G', 'E'], true)) {
                throw new Exception('Línea ' . ($i + 1) . ': seleccione impuesto Gravado o Exento para el producto nuevo.');
            }
            $stock_min = (int) ($_POST['np_stock_minimo'][$i] ?? 5);
            $fv = trim((string) ($_POST['np_fecha_vencimiento'][$i] ?? ''));
            if ($fv === '') {
                throw new Exception('Línea ' . ($i + 1) . ': fecha de vencimiento obligatoria para producto nuevo.');
            }

            $adj_foto = null;
            $fl = $_FILES['foto_linea'] ?? null;
            if (is_array($fl) && !empty($fl['name'][$i]) && (int) ($fl['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $tmp = $fl['tmp_name'][$i];
                $origName = (string) $fl['name'][$i];

                // Sanear nombre del archivo: quitar espacios, tildes, ñ y cualquier
                // carácter que el sanitizador de tabla_almacen.js (regex
                // /[^a-zA-Z0-9._\-\/]/g) eliminaría al construir la URL. Si no se
                // sanea aquí, la URL solicitada NO coincide con el nombre real en
                // disco y la imagen sale rota (alt="Foto" visible en la lista).
                $ext = strtolower((string) pathinfo($origName, PATHINFO_EXTENSION));
                $ext = preg_replace('/[^a-z0-9]/', '', $ext);
                if ($ext === '') {
                    $ext = 'jpg';
                }
                $base = (string) pathinfo($origName, PATHINFO_FILENAME);
                // Reemplazar tildes/ñ por su equivalente ASCII antes del filtro estricto
                $base = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base) ?: $base;
                $base = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $base);
                $base = trim($base, '_.');
                if ($base === '') {
                    $base = 'foto';
                }
                // Sufijo único para evitar colisiones (dos productos con misma foto.jpg)
                try {
                    $unique = bin2hex(random_bytes(3));
                } catch (Throwable $eRnd) {
                    $unique = substr(md5(uniqid('', true)), 0, 6);
                }
                $fn = $base . '_' . date('YmdHis') . '_' . $unique . '.' . $ext;

                $dest = $uploadDir . $fn;
                if (move_uploaded_file($tmp, $dest)) {
                    $adj_foto = 'uploads/adj_foto/' . $fn;
                }
            }

            $preprd_new = $pu;
            $stock_new = medidata_normalize_product_stock($qty);
            $precioVenta = medidata_calc_precio_venta($preprd_new, $margen, $imp);

            $insProduct->execute([
                ':codpro' => $codpro ?: '110400102',
                ':codbars' => $codbars,
                ':nompro' => $nompro,
                ':principio_activo' => $principio,
                ':idcat' => $idcat,
                ':preprd' => $preprd_new,
                ':stock' => $stock_new,
                ':stock_minimo' => $stock_min,
                ':fecha_vencimiento' => $fv,
                ':via_administracion' => $via,
                ':concentracion' => $concentracion,
                ':forma_farmaceutica' => $forma_farmaceutica,
                ':presentacion' => $presentacion,
                ':sub_linea' => $sub_linea,
                ':linea' => $linea,
                ':margen_ganancia' => (string) $margen,
                ':impuesto' => $imp,
                ':precio_venta' => $precioVenta,
                ':adj_foto' => $adj_foto,
                ':fere' => $currentDateTime,
            ]);
            $pid = (int) $connect->lastInsertId();
            // Descripción en factura = nombre maestro si no rellenaron
            if (($descripcion[$i] ?? '') === '') {
                $descripcion[$i] = $nompro;
            }
        } else {
            $pid = (int) ($product_id_prcd[$i] ?? 0);
            if ($pid < 1) {
                throw new Exception('Línea ' . ($i + 1) . ': seleccione un producto existente o cambie a "Producto nuevo".');
            }
            $selProduct->execute([$pid]);
            $prow = $selProduct->fetch(PDO::FETCH_ASSOC);
            if (!$prow) {
                throw new Exception('Línea ' . ($i + 1) . ': producto no encontrado (id ' . $pid . ').');
            }

            $s_cur = (int) $prow['stock'];
            $old_preprd = (float) $prow['preprd'];
            $margen = floatval($prow['margen_ganancia'] ?? 0);
            $imp = strtoupper(trim((string) ($prow['impuesto'] ?? 'E')));
            if (!in_array($imp, ['G', 'E'], true)) {
                $imp = 'E';
            }

            $new_qty = $s_cur + $qty;
            $new_qty = medidata_normalize_product_stock($new_qty);

            if ($s_cur <= 0) {
                $new_preprd = $pu;
            } else {
                $new_preprd = (($s_cur * $old_preprd) + ($qty * $pu)) / $new_qty;
            }
            $new_preprd = round($new_preprd, 4);
            $precioVenta = medidata_calc_precio_venta($new_preprd, $margen, $imp);

            $updProduct->execute([
                $new_qty,
                $new_preprd,
                $precioVenta,
                $pid,
            ]);
        }

        $stmt_detalle->execute([
            ':id_compra' => $id_compra,
            ':product_id' => $pid,
            ':cat_cuenta' => $cat_cuenta[$i] ?? '',
            ':codigo_producto' => $codigo_producto[$i] ?? '',
            ':cantidad' => $qty,
            ':unidad' => $unidad[$i] ?? '',
            ':descripcion' => $descripcion[$i] ?? '',
            ':precio_unitario' => $pu,
            ':isv' => $isv[$i] ?? 0,
            ':subtotal' => $subtotal[$i] ?? 0,
            ':total_item' => $total_item[$i] ?? 0,
            ':exento' => $exento ? 1 : 0,
            ':gravado' => $gravado ? 1 : 0,
            ':descuento_porcentaje' => $descuento_porcentaje[$i] ?? 0,
        ]);
    }

    require_once __DIR__ . '/../php/partida_compra_proveedor.php';
    $usuarioPartida = isset($_SESSION['name']) ? (string) $_SESSION['name'] : 'Sistema';
    medidata_generar_partida_desde_compra(
        $connect,
        $id_compra,
        $usuarioPartida,
        $sucursal !== '' ? $sucursal : 'Hospital Medicasa'
    );

    $connect->commit();

    $medNavigateAfterSave = !empty($MEDIDATA_REG_COMPRAS_RELOAD_AFTER_SAVE)
        ? 'window.location.replace(window.location.pathname + "?guardado=1");'
        : "window.location = 'mostrar_compras.php';";

    echo '<script>
        swal({
            title: "Compra guardada",
            text: "La compra se registró y el inventario se actualizó correctamente.",
            icon: "success",
            button: "OK",
        }).then(function() {
            ' . $medNavigateAfterSave . '
        });
    </script>';
} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    $msg = addslashes($e->getMessage());
    echo "<script>
        swal({
            title: 'Error',
            text: '" . $msg . "',
            icon: 'error',
            button: 'OK',
        });
    </script>";
}
