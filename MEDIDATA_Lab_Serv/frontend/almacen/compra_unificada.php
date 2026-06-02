<?php
include_once '../../backend/registros/session_check.php';

$productOpts = '<option value="">— Seleccione producto —</option>';
try {
    $st = $connect->query("SELECT idprcd, nompro FROM product WHERE state = '1' ORDER BY nompro ASC");
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $id = (int) $r['idprcd'];
        $nom = htmlspecialchars($r['nompro'], ENT_QUOTES, 'UTF-8');
        $productOpts .= '<option value="' . $id . '">' . $nom . '</option>';
    }
} catch (Throwable $e) {
    $productOpts .= '<option value="">Error al cargar productos</option>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
    <style>
        .compra-items-table-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: visible;
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
            border: 1px solid #d8d8d8;
            border-radius: 4px;
            background: #f9f9f9;
        }

        .compra-items-table-scroll:focus-within {
            outline: none;
        }

        .compra-items-table-hint {
            font-size: 12px;
            color: #555;
            margin: 6px 0 0;
        }

        /* Tabla de líneas: mismos criterios que compras_seg.php (cabecera marca, filas alternas, inputs compactos) */
        #items_table {
            width: 100%;
            min-width: 1560px;
            border-collapse: collapse;
            margin-top: 0;
            background-color: #f9f9f9;
            table-layout: fixed;
        }

        #items_table thead th {
            background-color: #06adbf;
            color: #fff;
            padding: 10px 6px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #035c67;
            border-bottom: 2px solid #035c67;
            vertical-align: middle;
        }

        #items_table tbody td {
            padding: 6px;
            border: 1px solid #e0e0e0;
            vertical-align: middle;
        }

        /* Pareja línea principal + fila maestro: mismo color de bloque */
        #items_table tbody tr:nth-child(4n + 1),
        #items_table tbody tr:nth-child(4n + 2) {
            background-color: #fff;
        }

        #items_table tbody tr:nth-child(4n + 3),
        #items_table tbody tr:nth-child(4n + 4) {
            background-color: #e6f7f8;
        }

        #items_table tbody tr.line-maestro td {
            border-color: #d0d0d0;
        }

        #items_table input[type="text"],
        #items_table input[type="number"],
        #items_table input[type="date"] {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            padding: 5px 6px;
            margin: 0;
            border: 1px solid #dcdcdc;
            border-radius: 3px;
            background: #fff;
        }

        #items_table input[type="file"] {
            width: auto;
            max-width: 100%;
            box-sizing: border-box;
            padding: 4px 6px;
            margin: 0;
            border: 1px solid #dcdcdc;
            border-radius: 3px;
            background: #fff;
        }

        #items_table input[readonly] {
            background: #f0f0f0;
        }

        #items_table tbody td select {
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
            margin: 0;
            padding: 5px 6px;
            border: 1px solid #dcdcdc;
            border-radius: 3px;
            background: #fff;
        }

        #items_table .select2-container {
            margin: 0 !important;
            max-width: 100%;
        }

        #items_table .select2-container .select2-selection--single {
            min-height: 32px;
            border: 1px solid #dcdcdc !important;
            border-radius: 3px;
            background: #fff !important;
        }

        #items_table .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
            padding-left: 6px;
        }

        #items_table .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 30px;
        }

        #items_table button {
            background-color: #035c67;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #items_table button:hover {
            background-color: #06adbf;
        }

        .item-table-button {
            background-color: #035c67;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .item-table-button:hover {
            background-color: #06adbf;
        }

        /* Anchos de columna (Producto y Descripción más amplios) */
        #items_table col.col-modo { width: 7%; }
        #items_table col.col-producto { width: 16%; }
        #items_table col.col-stock { width: 4.5%; }
        #items_table col.col-stock-fin { width: 4.5%; }
        #items_table col.col-cuenta { width: 11%; }
        #items_table col.col-codigo { width: 5%; }
        #items_table col.col-cant { width: 4%; }
        #items_table col.col-unidad { width: 3.5%; }
        #items_table col.col-desc { width: 12%; }
        #items_table col.col-punit { width: 6%; }
        #items_table col.col-tax { width: 7%; }
        #items_table col.col-isv { width: 5%; }
        #items_table col.col-sub { width: 5%; }
        #items_table col.col-descpct { width: 4%; }
        #items_table col.col-total { width: 5%; }
        #items_table col.col-acc { width: 96px; }

        #items_table thead th:last-child,
        #items_table tbody tr.line-main td:last-child {
            width: 96px;
            min-width: 96px;
            max-width: 96px;
            text-align: center;
            white-space: nowrap;
        }

        #items_table tbody tr.line-main td:last-child .item-table-button {
            white-space: nowrap;
        }

        #items_table .maestro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid #b8dce0;
            border-radius: 4px;
        }

        #items_table .maestro-grid-title {
            grid-column: 1 / -1;
            color: #035c67;
        }

        #items_table .maestro-grid label input,
        #items_table .maestro-grid label select {
            margin-top: 4px;
        }
    </style>
</head>
<body>

<?php include_once '../admin/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        if ($hora_actual >= 6 && $hora_actual < 12) {
            $saludo = 'Buenos Días';
        } elseif ($hora_actual >= 12 && $hora_actual < 18) {
            $saludo = 'Buenas Tardes';
        } else {
            $saludo = 'Buenas Noches';
        }
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'compra_unificada.php')">Compra / Inventario (unificado)</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'categoria_new.php')">Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'categoria.php')">Lista de Categorias</button>
        <button class="button" onclick="cambiarColor(this, 'nuevo_servicio.php')">Registrar Servicio</button>
        <button class="button" onclick="cambiarColor(this, 'lista_servicios.php')">Lista de Servicios</button>
        <button class="button" onclick="cambiarColor(this, 'reorden.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden_admin.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones.php')">Requisiciones</button>

        <form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validarFormularioCompleto()">
            <div class="containerss">
                <h1>Registrar compra con factura (actualiza inventario)</h1>
                <p style="color:#555;">Un solo proceso: factura de proveedor, líneas de detalle y si aplica, alta de producto nuevo.</p>

                <div class="alert-danger">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <strong>Importante</strong> Campos con <span class="badge-warning">*</span> obligatorios.
                </div>
                <hr><br>

                <label for="sucursal"><b>Sucursal</b></label>
                <select name="sucursal" id="sucursal" class="select2">
                    <option value="">Seleccione</option>
                    <option value="SUCURSAL 1">SUCURSAL 1</option>
                </select>

                <label for="bodega"><b>Ubicación</b></label>
                <select name="bodega" id="bodega" class="select2">
                    <option value="">Seleccionar...</option>
                    <option value="BODEGA GENERAL">BODEGA GENERAL</option>
                    <option value="ALMACEN HOSPITALARIO">ALMACEN HOSPITALARIO</option>
                    <option value="QUIROFANO">QUIROFANO</option>
                    <option value="CAJA">CAJA</option>
                    <option value="FARMACIA EXTERNA">FARMACIA EXTERNA</option>
                </select>

                <label for="prov_datos"><b>Proveedor comercial</b></label>
                <select name="prov_datos" id="prov_datos" class="select2">
                    <option>Seleccione</option>
                </select>

                <br><br>
                <label for="dato_fac"><b>Factura No.</b></label><span class="badge-warning">*</span>
                <input type="text" id="dato_fac" name="dato_fac" required maxlength="19" minlength="19"
                       placeholder="000-003-01-00031284-7854-2345"
                       oninput="validarFacturaExacta(this)" onblur="validarFacturaExacta(this)">
                <div id="factura-validacion-container" style="margin-bottom:15px;"></div>

                <label for="fecha_emision"><b>Fecha factura</b></label><span class="badge-warning">*</span>
                <input type="date" id="fecha_emision" name="fecha_emision" required onchange="calcularFechaVencimiento()">

                <label><b>Términos de pago</b></label><span class="badge-warning">*</span>
                <div>
                    <label><input type="radio" id="cred_credito" name="cred_cont" value="Credito" required onclick="toggleCampos('credito')"> Crédito</label>
                    <label><input type="radio" id="cred_contado" name="cred_cont" value="Contado" required onclick="toggleCampos('contado')"> Contado</label>
                    <label><input type="radio" id="cred_prima" name="cred_cont" value="Prima" required onclick="toggleCampos('prima')"> Otros términos</label>
                    <label><input type="radio" id="cred_consignacion" name="cred_cont" value="Consignacion" required onclick="toggleCampos('consignacion')"> Consignación</label>
                </div>

                <div id="dias_credito_field" style="display:none;margin-top:10px;">
                    <label for="dias_credito"><b>Días de crédito</b></label>
                    <input type="number" id="dias_credito" name="dias_credito" min="1" placeholder="Días" oninput="calcularFechaVencimiento()">
                </div>
                <div id="prima_fields" style="display:none;margin-top:10px;">
                    <label for="porcentaje_prima"><b>% Prima</b></label>
                    <input type="number" id="porcentaje_prima" name="porcentaje_prima" min="0" max="100" placeholder="%">
                    <label for="cuotas_pendientes"><b>Cuotas pendientes</b></label>
                    <input type="number" id="cuotas_pendientes" name="cuotas_pendientes" min="1" placeholder="Cuotas" oninput="calcularFechaVencimiento()">
                </div>
                <br>
                <label for="fech_vence"><b>Fecha vencimiento</b></label><span class="badge-warning">*</span>
                <input type="date" id="fech_vence" name="fech_vence" required readonly>

                <hr>
                <div class="compra-items-table-scroll" role="region" aria-label="Líneas de detalle de compra">
                <table id="items_table" border="1" width="100%" style="border-collapse:collapse;">
                    <colgroup>
                        <col class="col-modo">
                        <col class="col-producto">
                        <col class="col-stock">
                        <col class="col-stock-fin">
                        <col class="col-cuenta">
                        <col class="col-codigo">
                        <col class="col-cant">
                        <col class="col-unidad">
                        <col class="col-desc">
                        <col class="col-punit">
                        <col class="col-tax">
                        <col class="col-isv">
                        <col class="col-sub">
                        <col class="col-descpct">
                        <col class="col-total">
                        <col class="col-acc">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Modo</th>
                            <th>Producto</th>
                            <th>Stock act.</th>
                            <th>Stock final</th>
                            <th>Cuenta</th>
                            <th>Código</th>
                            <th>Cant.</th>
                            <th>Uni</th>
                            <th>Descripción</th>
                            <th>P. unit.</th>
                            <th>Imp. línea</th>
                            <th>ISV</th>
                            <th>Subt.</th>
                            <th>Desc. %</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="items_tbody">
                        <tr class="line-main">
                            <td>
                                <select name="line_mode[0]" class="line-mode select2" onchange="toggleLineMode(this)">
                                    <option value="existing">Existente</option>
                                    <option value="new">Producto nuevo</option>
                                </select>
                            </td>
                            <td>
                                <select name="product_id_prcd[0]" class="select2 product-id-select"><?php echo $productOpts; ?></select>
                            </td>
                            <td><input type="text" class="stock-actual" name="stock_display_act[0]" readonly tabindex="-1"></td>
                            <td><input type="text" class="stock-resultado" name="stock_display_res[0]" readonly tabindex="-1"></td>
                            <td>
                                <select name="cat_cuenta[0]" class="select2 cat_cuenta_sel"><option value="">Seleccione cuenta</option></select>
                            </td>
                            <td><input type="text" name="codigo_producto[0]" required placeholder="Código"></td>
                            <td><input type="number" name="cantidad[0]" min="1" required value="1" oninput="calcularTotales(this)"></td>
                            <td><input type="text" name="unidad[0]" required value="1"></td>
                            <td><input type="text" name="descripcion[0]" required placeholder="Descripción"></td>
                            <td><input type="number" name="precio_unitario[0]" min="0" step="0.0001" required placeholder="0.0000" oninput="calcularTotales(this)"></td>
                            <td>
                                <select name="line_tax[0]" class="select2" onchange="calcularTotales(this)">
                                    <option value="E">Exento</option>
                                    <option value="G">Gravado 15%</option>
                                </select>
                            </td>
                            <td><input type="number" name="isv[0]" min="0" step="0.01" required readonly value="0.00"></td>
                            <td><input type="number" name="subtotal[0]" min="0" step="0.01" required readonly value="0.00"></td>
                            <td><input type="number" name="descuento_porcentaje[0]" min="0" max="100" step="0.01" value="0" oninput="calcularTotales(this)"></td>
                            <td><input type="number" name="total_item[0]" min="0" step="0.01" required readonly value="0.00"></td>
                            <td><button type="button" class="item-table-button" onclick="removeItemPair(this)">Eliminar</button></td>
                        </tr>
                        <tr class="line-maestro" style="display:none;">
                            <td colspan="16"><?php include __DIR__ . '/includes/compra_maestro_linea.inc.php'; ?></td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <p class="compra-items-table-hint">Si no columna "Acciones", desplace horizontalmente la tabla (barra inferior).</p>

                <button type="button" class="item-table-button" onclick="addItemRow()">Agregar línea</button>

                <br><br>
                <label for="isv_global"><b>ISV</b></label><span class="badge-warning">*</span>
                <input type="number" id="isv_global" name="isv_global" min="0" step="0.01" readonly>
                <label for="sub_total"><b>Subtotal</b></label><span class="badge-warning">*</span>
                <input type="number" id="sub_total" name="sub_total" min="0" step="0.01" readonly>
                <label for="total"><b>Total</b></label><span class="badge-warning">*</span>
                <input type="number" id="total" name="total" min="0" step="0.01" readonly>

                <script>
                window.__PRODUCT_OPTIONS_HTML__ = <?php echo json_encode($productOpts); ?>;

                function toggleExentoGravado() { /* legacy no-op */ }

                function calcularTotales(element) {
                    const row = element.closest('tr.line-main');
                    if (!row) return;
                    const cantidad = parseFloat(row.querySelector('input[name^="cantidad"]').value) || 0;
                    const precioUnitario = parseFloat(row.querySelector('input[name^="precio_unitario"]').value) || 0;
                    const descuentoPorcentaje = parseFloat(row.querySelector('input[name^="descuento_porcentaje"]').value) || 0;
                    const taxSel = row.querySelector('select[name^="line_tax"]');
                    const gravado = taxSel && taxSel.value === 'G';
                    const isvField = row.querySelector('input[name^="isv"]');
                    const subtotalField = row.querySelector('input[name^="subtotal"]');
                    const totalItemField = row.querySelector('input[name^="total_item"]');
                    let isv = 0;
                    let subtotal = cantidad * precioUnitario;
                    if (gravado) isv = subtotal * 0.15;
                    isvField.value = isv.toFixed(2);
                    subtotalField.value = subtotal.toFixed(2);
                    const descuentoAplicado = subtotal * (descuentoPorcentaje / 100);
                    totalItemField.value = (subtotal + isv - descuentoAplicado).toFixed(2);
                    calcularTotalesGenerales();
                }

                function calcularTotalesGenerales() {
                    const rows = document.querySelectorAll('#items_tbody tr.line-main');
                    let totalISV = 0, totalSubtotal = 0, totalGeneral = 0;
                    rows.forEach(row => {
                        const subtotal = parseFloat(row.querySelector('input[name^="subtotal"]')?.value) || 0;
                        const isv = parseFloat(row.querySelector('input[name^="isv"]')?.value) || 0;
                        const totalItem = parseFloat(row.querySelector('input[name^="total_item"]')?.value) || 0;
                        totalISV += isv;
                        totalSubtotal += subtotal;
                        totalGeneral += totalItem;
                    });
                    document.getElementById('isv_global').value = totalISV.toFixed(2);
                    document.getElementById('sub_total').value = totalSubtotal.toFixed(2);
                    document.getElementById('total').value = totalGeneral.toFixed(2);
                }

                function validarFacturaExacta(input) {
                    if (input.value.length > 19) input.value = input.value.substring(0, 19);
                    const container = document.getElementById('factura-validacion-container');
                    let contador = container.querySelector('.contador-caracteres');
                    let mensaje = container.querySelector('.mensaje-validacion');
                    if (!contador) {
                        contador = document.createElement('small');
                        contador.className = 'contador-caracteres';
                        contador.style.display = 'block';
                        container.appendChild(contador);
                    }
                    if (!mensaje) {
                        mensaje = document.createElement('small');
                        mensaje.className = 'mensaje-validacion';
                        mensaje.style.display = 'block';
                        container.appendChild(mensaje);
                    }
                    const longitud = input.value.length;
                    contador.textContent = longitud + '/19 caracteres';
                    if (longitud === 19) {
                        input.style.borderColor = '#28a745';
                        mensaje.textContent = 'Formato correcto';
                        mensaje.style.color = '#28a745';
                        input.setCustomValidity('');
                    } else {
                        input.style.borderColor = '#dc3545';
                        mensaje.textContent = longitud === 0 ? 'Obligatorio: 19 caracteres' : 'Faltan ' + (19 - longitud) + ' caracteres';
                        mensaje.style.color = '#dc3545';
                        input.setCustomValidity('19 caracteres');
                    }
                }

                function validarFormularioCompleto() {
                    const facturaInput = document.getElementById('dato_fac');
                    if (facturaInput.value.length !== 19) {
                        validarFacturaExacta(facturaInput);
                        facturaInput.focus();
                        return false;
                    }
                    const mains = document.querySelectorAll('tr.line-main');
                    for (let i = 0; i < mains.length; i++) {
                        const m = mains[i];
                        const mode = m.querySelector('.line-mode').value;
                        if (mode === 'existing') {
                            const pid = m.querySelector('.product-id-select').value;
                            if (!pid) {
                                alert('Línea ' + (i + 1) + ': seleccione un producto existente o cambie a «Producto nuevo».');
                                return false;
                            }
                        }
                    }
                    return true;
                }
                </script>

                <hr>
                <button type="submit" name="add_medicine" class="registerbtn">Guardar compra e inventario</button>
            </div>
        </form>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/cat_proveedores.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/js/fech_vence.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script src="../../backend/js/compra_unificada.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script>
$(function () {
    var $dp = $('#content').length ? $('#content') : $(document.body);
    $('.select2').select2({ width: '100%', dropdownParent: $dp });
    calcularTotales(document.querySelector('tr.line-main input[name^="cantidad"]'));
});
</script>
<?php $MEDIDATA_REG_COMPRAS_RELOAD_AFTER_SAVE = true; ?>
<?php include_once '../../backend/registros/reg_compras.php'; ?>
</body>
</html>
