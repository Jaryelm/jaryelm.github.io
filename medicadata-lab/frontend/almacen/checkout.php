<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />



    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#">
                <div class="form-group">
                    
                </div>
            </form>
            
           
            <span class="divider"></span>
            <?php
include_once '../admin/perfil.php';
// incuir el archivo menu principal
?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
    <?php
    // Obtener la hora actual
    $hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

    if ($hora_actual >= 6 && $hora_actual < 12) {
        $saludo = "Buenos Días";
    } elseif ($hora_actual >= 12 && $hora_actual < 18) {
        $saludo = "Buenas Tardes";
    } else {
        $saludo = "Buenas Noches";
    }
    ?>

    <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

    <button class="button" onclick="cambiarColor(this, '../almacen/new_sale.php')">Nueva Venta</button>
    <button class="button" onclick="cambiarColor(this, '../almacen/cart.php')">Procesar Venta</button>
    <button class="button" onclick="cambiarColor(this, '#')">Cotizaciones</button>
    <button class="button" onclick="cambiarColor(this, '#')">Estados de Cuenta</button>
    <button class="button" onclick="cambiarColor(this, '../almacen/venta.php')">Resumen de Ventas</button>
    <button class="button" onclick="cambiarColor(this, '../citas/mostrar.php')">Resumen de Citas</button>

    <style>
/* Contenedor general */
.input-block {
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    margin: 20px 0;
}

/* Contenedor para los detalles con scroll */
.summary-container {
    max-height: 300px; /* Limitar la altura para mostrar máximo 6 filas */
    overflow-y: auto;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 10px;
}

/* Estilo para las cajas de resumen */
.summary-box {
    flex: 1 1 calc(50% - 20px); /* Dos columnas en dispositivos grandes */
    background: #fff;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s, box-shadow 0.3s;
    max-width: 400px;
}

.summary-box:hover {
    transform: scale(1.03);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
}

.summary-box h3 {
    margin: 0;
    font-size: 1.5rem;
    color: #333;
}

.summary-box p {
    margin: 5px 0;
    color: #555;
    font-size: 1rem;
}

/* Totales */
.grand-total {
    margin-top: 20px;
    text-align: center;
    font-size: 1.5rem;
    color: #030303;
    font-weight: bold;
}

/* Responsivo */
@media (max-width: 768px) {
    .summary-box {
        flex: 1 1 100%; /* Una columna en dispositivos pequeños */
    }
}
</style>

<div class="input-block">
    <?php
    $user_id = $_SESSION['id'];
    $cart_grand_total = 0;
    $cart_original_total = 0;
    $product_items = 0;
    $service_items = 0;

    $select_cart_items = $connect->prepare("
        SELECT 
            cart.idv, 
            cart.name, 
            cart.price, 
            cart.quantity, 
            cart.discount, 
            cart.promotion_discount, 
            cart.other_discount, 
            cart.age_discount_30, 
            cart.age_discount_40, 
            cart.type,
            product.codpro AS prod_cod, 
            product.precio_venta AS prod_price,
            servicios_hospital.codigo_servicio AS serv_cod, 
            servicios_hospital.total AS serv_price,
            almacen_hospitalario.codpro AS hosp_cod,
            almacen_hospitalario.precio_venta AS hosp_price
        FROM cart
        INNER JOIN users ON cart.user_id = users.id
        LEFT JOIN product ON cart.idprcd = product.idprcd AND cart.type = 'producto'
        LEFT JOIN servicios_hospital ON cart.id_servicio = servicios_hospital.id AND cart.type = 'servicio'
        LEFT JOIN almacen_hospitalario ON cart.id_producto_hospitalario = almacen_hospitalario.idprcd AND cart.type = 'producto_hospitalario'
        WHERE cart.user_id = ?
    ");
    $select_cart_items->execute([$user_id]);

    $summary = [];
    if ($select_cart_items->rowCount() > 0) {
        $items_counted = ['product' => [], 'service' => [], 'producto_hospitalario' => []];
        while ($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)) {
            $is_product = $fetch_cart_items['type'] === 'producto';
            $is_hospitalario = $fetch_cart_items['type'] === 'producto_hospitalario';
            
            // Usar el precio del carrito directamente (ya tiene el precio correcto, incluso si es 0.00)
            $price = $fetch_cart_items['price'];
            
            // Determinar código según el tipo
            if ($is_product) {
                $code = $fetch_cart_items['prod_cod'];
            } elseif ($is_hospitalario) {
                $code = $fetch_cart_items['hosp_cod'];
            } else {
                $code = $fetch_cart_items['serv_cod'];
            }

            // Calcular subtotal
            $cart_total_price = $price * $fetch_cart_items['quantity'];

            // Calcular descuentos
            $general_discount_percent = $fetch_cart_items['discount'] ?? 0;
            $general_discount_amount = ($cart_total_price * $general_discount_percent) / 100;

            $promotion_discount_percent = $fetch_cart_items['promotion_discount'] ?? 0;
            $promotion_discount_amount = ($cart_total_price * $promotion_discount_percent) / 100;

            // Otros descuento ahora es monto fijo en Lempiras, no porcentaje
            $other_discount_amount = floatval($fetch_cart_items['other_discount'] ?? 0);

            $age_discount_30_amount = $fetch_cart_items['age_discount_30'] ? ($cart_total_price * 30) / 100 : 0;
            $age_discount_40_amount = $fetch_cart_items['age_discount_40'] ? ($cart_total_price * 40) / 100 : 0;

            // Verificar si es "PLACA DE RAYOS X" (no debe aparecer en la factura ni sumarse al total, pero sí contablemente)
            $es_placa = (stripos($fetch_cart_items['name'], 'PLACA') !== false && 
                        stripos($fetch_cart_items['name'], 'RAYOS') !== false);
            
            // Si es la placa, NO sumarla al total (pero seguirá en el carrito para contabilidad)
            if (!$es_placa) {
            // Calcular total después de descuentos
            $total_discount = $general_discount_amount + $promotion_discount_amount + $other_discount_amount + $age_discount_30_amount + $age_discount_40_amount;
            $total_after_discount = $cart_total_price - $total_discount;

                // Sumar al total general (solo si NO es placa)
            $cart_grand_total += $total_after_discount;
                $cart_original_total += $cart_total_price;
            }

            // Contar productos y servicios únicos (excluyendo la placa del conteo visual)
            if (!$es_placa) {
            if ($is_product && !in_array($fetch_cart_items['idv'], $items_counted['product'])) {
                $product_items++;
                $items_counted['product'][] = $fetch_cart_items['idv'];
            } elseif ($is_hospitalario && !in_array($fetch_cart_items['idv'], $items_counted['producto_hospitalario'])) {
                $product_items++;
                $items_counted['producto_hospitalario'][] = $fetch_cart_items['idv'];
            } elseif (!$is_product && !$is_hospitalario && !in_array($fetch_cart_items['idv'], $items_counted['service'])) {
                $service_items++;
                $items_counted['service'][] = $fetch_cart_items['idv'];
                }
            }

            // Añadir al resumen SOLO si NO es la placa (la placa no aparece en la factura pero sí contablemente)
            if (!$es_placa) {
            $summary[] = [
                'name' => $fetch_cart_items['name'],
                'code' => $code,
                'quantity' => $fetch_cart_items['quantity'],
                'price' => $price,
                'discount_details' => [
                    'Descuento General' => $general_discount_amount,
                    'Descuento Promoción' => $promotion_discount_amount,
                    'Descuento Otros' => $other_discount_amount,
                    'Descuento 3ra Edad' => $age_discount_30_amount,
                    'Descuento 4ta Edad' => $age_discount_40_amount,
                ],
                'total_after_discount' => $total_after_discount,
            ];
            }
        }
    } else {
        echo '<p class="alert alert-warning">Tu carrito está vacío.</p>';
    }
    ?>

    <!-- Mostrar Resumen -->
    <div class="summary-container">
        <?php foreach ($summary as $item): ?>
            <div class="summary-box">
                <h3><?= htmlspecialchars($item['name']); ?> (<?= htmlspecialchars($item['code']); ?>)</h3>
                <p>Cantidad: <?= htmlspecialchars($item['quantity']); ?></p>
                <p>Precio Unitario: LPS. <?= number_format($item['price'], 2); ?></p>
                <?php foreach ($item['discount_details'] as $type => $amount): ?>
                    <?php if ($amount > 0): ?>
                        <p><?= $type; ?>: -LPS. <?= number_format($amount, 2); ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
                <p><strong>Total: LPS. <?= number_format($item['total_after_discount'], 2); ?></strong></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Totales -->
    <div class="grand-total">
        <p>Precio Total (Sin Descuentos): LPS. <?= number_format($cart_original_total, 2); ?></p>
        <p>Precio Total (Con Descuentos): LPS. <?= number_format($cart_grand_total, 2); ?></p>
        <p><strong>Productos: <?= $product_items; ?> | Servicios: <?= $service_items; ?></strong></p>
    </div>
</div>


<form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validacion()">
    <div class="containerss">
        <h1>Finalizar Venta</h1>

        <div class="alert-danger">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <strong>Importante!</strong> Es importante rellenar los campos con <span class="badge-warning">*</span>
        </div>
        <hr>
        <br>

        <!-- Campo Nombres y Apellidos Dinámico -->
        <label for="nomcl_dynamic"><b>Nombres y apellidos del cliente (Pacientes Registrados)</b></label>
        <select id="nomcl_dynamic" name="nomcl_dynamic" class="select2" style="width: 100%;" placeholder="Escribe o selecciona un paciente" onchange="handleDynamicSelection()">
            <option value="" selected disabled>Escribe o selecciona un paciente</option>
            <?php
            $query = $connect->prepare("SELECT CONCAT(nompa, ' ', apepa) AS full_name, numhs, cump FROM patients");
            $query->execute();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                echo '<option value="' . htmlspecialchars($row['full_name']) . '" data-dni="' . htmlspecialchars($row['numhs']) . '" data-cump="' . htmlspecialchars($row['cump']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
            }
            ?>
        </select>

<!-- Campo Edad del Paciente Dinámico (Oculto) -->
<input type="hidden" name="edad_dynamic" id="edad_dynamic" value="">

<!-- Campo DNI dinámico (Oculto) -->
<input type="hidden" name="dni_paciente" id="dni_paciente" value="">


        <br><br>

        <!-- Campo Manual -->
        <label for="nomcl_manual"><b>Nombres y apellidos del cliente (Pacientes Ambulatorios)</b></label>
        <input type="text" id="nomcl_manual" name="nomcl_manual" placeholder="Escribe manualmente el nombre del paciente" oninput="handleManualInput()">

        <!-- Campo DNI Manual -->
        <div id="dni_manual_container" style="display: none;">
            <label for="dni_manual"><b>DNI del Paciente (Manual)</b></label>
            <input type="text" name="dni_manual" id="dni_manual" placeholder="Ingrese el DNI manualmente">
        </div>

        <!-- Campo Edad del Paciente Manual -->
        <div id="edad_manual_container" style="display: none;">
            <label for="edad_manual"><b>Edad del paciente (Manual)</b></label>
            <input type="number" name="edad_manual" id="edad_manual" placeholder="Escribe la edad del paciente" min="0">
        </div>

        <!-- Campo Teléfono del Paciente Manual -->
        <div id="telefono_manual_container" style="display: none;">
            <label for="telefono_manual"><b>Teléfono del paciente (Manual)</b></label>
            <input type="tel" name="telefono_manual" id="telefono_manual" placeholder="Ingrese el teléfono del paciente (opcional)">
        </div>

        <br><br>

        <!-- Campo Remitente -->
        <label for="remitente"><b>Médico Atención</b> <small style="color: #666;">(Opcional - se puede asignar después en radiología)</small></label>
        <select id="remitente" name="remitente" class="select2" style="width: 100%;" placeholder="Escribe o selecciona un remitente">
            <option value="">Seleccionar después en radiología</option>
            <?php
            $query = $connect->prepare("SELECT CONCAT(nodoc, ' ', apdoc) AS full_name FROM doctor");
            $query->execute();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                echo '<option value="' . htmlspecialchars($row['full_name']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
            }
            ?>
        </select>

        <br><br>

        <!-- Campo Remite -->
        <label for="remite"><b>Remite</b> <small style="color: #666;">(Opcional)</small></label>
        <input type="text" name="remite" id="remite" placeholder="Nombre del médico o institución que remite (opcional)">

        <br><br>

        <!-- Campo Teléfono Remite -->
        <label for="telefono_remite"><b>Teléfono Remite</b> <small style="color: #666;">(Opcional)</small></label>
        <input type="tel" name="telefono_remite" id="telefono_remite" placeholder="Teléfono del remitente (opcional)">

        <br><br>

        <!-- Campo Tipo -->
        <label for="tipo"><b>Tipo</b></label><span class="badge-warning">*</span>
        <select name="tipo" id="tipo" required>
            <option value="" selected disabled>Seleccione el tipo</option>
            <option value="Consulta Externa">Consulta Externa</option>
            <option value="Externo Referido Privado">Externo Referido Privado</option>
            <option value="Externo Estatal">Externo Estatal</option>
            <option value="Emergencia">Emergencia</option>
            <option value="Hospitalizado">Hospitalizado</option>
        </select>

        <br><br>

        <!-- Campo Método de Pago -->
        <label for="cxtcre"><b>Método de Pago</b></label><span class="badge-warning">*</span>
        <select required name="cxtcre" id="cxtcre" onchange="mostrarCamposTarjeta()">
            <option value="" selected disabled>Seleccione</option>
            <option value="Efectivo">Efectivo</option>
            <option value="Tarjeta">Tarjeta</option>
            <option value="Pago Mixto">Pago Mixto</option>
            <option value="Transferencia">Transferencia</option>
            <option value="Credito Colaborador">Crédito Colaborador</option>
            <option value="Boton de Pago">Botón de Pago</option>
            <option value="Transferencia Internacional">Transferencia Internacional</option>
            <option value="Transferencia Local">Transferencia Local</option>
        </select>

        <!-- Campo oculto con el total para cálculos JavaScript -->
        <input type="hidden" id="total_a_pagar" value="<?= number_format($cart_grand_total, 2, '.', ''); ?>">

        <!-- Campos adicionales para Efectivo -->
        <div id="efectivo_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #28a745; border-radius: 8px; background-color: #f0fff0;">
            <h4 style="color: #28a745; margin-top: 0; margin-bottom: 15px;">Información de Pago en Efectivo</h4>
            
            <label for="monto_recibido"><b>Monto Recibido</b></label><span class="badge-warning">*</span>
            <input type="number" name="monto_recibido" id="monto_recibido" step="0.01" min="0" placeholder="0.00" style="margin-bottom: 15px; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" oninput="calcularCambioEfectivo()">
            
            <label for="cambio_devolver"><b>Cambio a Devolver</b></label>
            <input type="number" name="cambio_devolver" id="cambio_devolver" step="0.01" readonly style="margin-bottom: 10px; width: 100%; padding: 8px; border: 1px solid #28a745; border-radius: 4px; background-color: #e8f5e9; font-weight: bold; color: #155724;" value="0.00">
            <small style="color: #666;">Este campo se calcula automáticamente</small>
        </div>

        <!-- Campos adicionales para Tarjeta/Crédito -->
        <div id="tarjeta_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #06adbf; border-radius: 8px; background-color: #f0f9fa;">
            <h4 style="color: #06adbf; margin-top: 0; margin-bottom: 15px;">Información de Tarjeta</h4>
            
            <label for="tipo_tarjeta"><b>Tipo de Tarjeta</b></label><span class="badge-warning">*</span>
            <select name="tipo_tarjeta" id="tipo_tarjeta" style="margin-bottom: 15px;">
                <option value="">Seleccione el tipo de tarjeta</option>
                <option value="Visa">Visa</option>
                <option value="Mastercard">Mastercard</option>
                <option value="American Express">American Express</option>
                <option value="Débito">Débito</option>
                <option value="Crédito">Crédito</option>
                <option value="Prepago">Prepago</option>
            </select>

            <label for="banco_emisor"><b>Banco Emisor</b></label><span class="badge-warning">*</span>
            <select name="banco_emisor" id="banco_emisor" style="margin-bottom: 15px;">
                <option value="">Seleccione el banco emisor</option>
                <option value="BAC Credomatic">BAC Credomatic</option>
                <option value="Banco Atlántida">Banco Atlántida</option>
                <option value="Banco de Occidente">Banco de Occidente</option>
                <option value="Banco Popular">Banco Popular</option>
                <option value="BANPAIS">BANPAIS</option>
                <option value="BANCATLAN">BANCATLAN</option>
                <option value="Banco Ficohsa">Banco Ficohsa</option>
                <option value="BANHCAFE">BANHCAFE</option>
                <option value="Banco Promerica">Banco Promerica</option>
                <option value="Banco Davivienda">Banco Davivienda</option>
                <option value="Otro">Otro</option>
            </select>

            <label for="pos_cobrado"><b>POS Cobrado</b></label><span class="badge-warning">*</span>
            <select name="pos_cobrado" id="pos_cobrado" style="margin-bottom: 10px;">
                <option value="">Seleccione el POS</option>
                <option value="POS Principal">POS Principal</option>
                <option value="POS Secundario">POS Secundario</option>
                <option value="POS Móvil">POS Móvil</option>
                <option value="Terminal 1">Terminal 1</option>
                <option value="Terminal 2">Terminal 2</option>
                <option value="Terminal 3">Terminal 3</option>
                <option value="Datafast">Datafast</option>
                <option value="Credomatic">Credomatic</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <!-- Campos adicionales para Transferencia -->
        <div id="transferencia_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #28a745; border-radius: 8px; background-color: #f0fff0;">
            <h4 style="color: #28a745; margin-top: 0; margin-bottom: 15px;">Información de Transferencia</h4>
            
            <label for="banco_transferencia"><b>Banco</b></label><span class="badge-warning">*</span>
            <select name="banco_transferencia" id="banco_transferencia" style="margin-bottom: 15px;">
                <option value="">Seleccione el banco</option>
                <option value="BAC Credomatic">BAC Credomatic</option>
                <option value="Banco Atlántida">Banco Atlántida</option>
                <option value="Banco de Occidente">Banco de Occidente</option>
                <option value="Banco Popular">Banco Popular</option>
                <option value="BANPAIS">BANPAIS</option>
                <option value="BANCATLAN">BANCATLAN</option>
                <option value="Banco Ficohsa">Banco Ficohsa</option>
                <option value="BANHCAFE">BANHCAFE</option>
                <option value="Banco Promerica">Banco Promerica</option>
                <option value="Banco Davivienda">Banco Davivienda</option>
                <option value="Otro">Otro</option>
            </select>

            <label for="num_referencia"><b># de Referencia</b></label><span class="badge-warning">*</span>
            <input type="text" name="num_referencia" id="num_referencia" placeholder="Ingrese el número de referencia" style="margin-bottom: 10px;">
        </div>

        <!-- Campos adicionales para Pago Mixto -->
        <div id="pago_mixto_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #ff6b35; border-radius: 8px; background-color: #fff5f0;">
            <h4 style="color: #ff6b35; margin-top: 0; margin-bottom: 15px;">Información de Pago Mixto</h4>
            
            <label for="tipo_pago_mixto"><b>Tipo de Pago</b></label><span class="badge-warning">*</span>
            <select name="tipo_pago_mixto" id="tipo_pago_mixto" style="margin-bottom: 15px;">
                <option value="">Seleccione el tipo</option>
                <option value="Tarjeta / Efectivo">Tarjeta / Efectivo</option>
                <option value="Efectivo / Tarjeta">Efectivo / Tarjeta</option>
            </select>

            <label for="monto_tarjeta_mixto"><b>Monto en Tarjeta</b></label><span class="badge-warning">*</span>
            <input type="number" name="monto_tarjeta_mixto" id="monto_tarjeta_mixto" step="0.01" min="0" placeholder="0.00" style="margin-bottom: 15px; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" oninput="calcularCambioMixto()">
            
            <label for="monto_efectivo_mixto"><b>Monto en Efectivo</b></label><span class="badge-warning">*</span>
            <input type="number" name="monto_efectivo_mixto" id="monto_efectivo_mixto" step="0.01" min="0" placeholder="0.00" style="margin-bottom: 15px; width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" oninput="calcularCambioMixto()">
            
            <label for="cambio_devolver_mixto"><b>Cambio a Devolver</b></label>
            <input type="number" name="cambio_devolver_mixto" id="cambio_devolver_mixto" step="0.01" readonly style="margin-bottom: 10px; width: 100%; padding: 8px; border: 1px solid #ff6b35; border-radius: 4px; background-color: #ffe8e0; font-weight: bold; color: #cc3300;" value="0.00">
            <small style="color: #666;">Este campo se calcula automáticamente si el efectivo excede lo necesario</small>
        </div>

        <!-- Campos adicionales para Botón de Pago -->
        <div id="boton_pago_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #6f42c1; border-radius: 8px; background-color: #f8f5ff;">
            <h4 style="color: #6f42c1; margin-top: 0; margin-bottom: 15px;">Información de Botón de Pago</h4>
            
            <label for="banco_boton_pago"><b>Banco</b></label><span class="badge-warning">*</span>
            <select name="banco_boton_pago" id="banco_boton_pago" style="margin-bottom: 15px;">
                <option value="">Seleccione el banco</option>
                <option value="BAC Credomatic">BAC Credomatic</option>
                <option value="Banco Atlántida">Banco Atlántida</option>
                <option value="Banco de Occidente">Banco de Occidente</option>
                <option value="Banco Popular">Banco Popular</option>
                <option value="BANPAIS">BANPAIS</option>
                <option value="BANCATLAN">BANCATLAN</option>
                <option value="Banco Ficohsa">Banco Ficohsa</option>
                <option value="BANHCAFE">BANHCAFE</option>
                <option value="Banco Promerica">Banco Promerica</option>
                <option value="Banco Davivienda">Banco Davivienda</option>
                <option value="Otro">Otro</option>
            </select>

            <label for="num_referencia_boton_pago"><b># de Referencia</b></label><span class="badge-warning">*</span>
            <input type="text" name="num_referencia_boton_pago" id="num_referencia_boton_pago" placeholder="Ingrese el número de referencia" style="margin-bottom: 10px;">
        </div>

        <!-- Campos adicionales para Transferencia Local -->
        <div id="transferencia_local_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #17a2b8; border-radius: 8px; background-color: #f0f8ff;">
            <h4 style="color: #17a2b8; margin-top: 0; margin-bottom: 15px;">Información de Transferencia Local</h4>
            
            <label for="banco_transferencia_local"><b>Banco</b></label><span class="badge-warning">*</span>
            <select name="banco_transferencia_local" id="banco_transferencia_local" style="margin-bottom: 15px;">
                <option value="">Seleccione el banco</option>
                <option value="BAC Credomatic">BAC Credomatic</option>
                <option value="Banco Atlántida">Banco Atlántida</option>
                <option value="Banco de Occidente">Banco de Occidente</option>
                <option value="Banco Popular">Banco Popular</option>
                <option value="BANPAIS">BANPAIS</option>
                <option value="BANCATLAN">BANCATLAN</option>
                <option value="Banco Ficohsa">Banco Ficohsa</option>
                <option value="BANHCAFE">BANHCAFE</option>
                <option value="Banco Promerica">Banco Promerica</option>
                <option value="Banco Davivienda">Banco Davivienda</option>
                <option value="Otro">Otro</option>
            </select>

            <label for="num_referencia_transferencia_local"><b># de Referencia</b></label><span class="badge-warning">*</span>
            <input type="text" name="num_referencia_transferencia_local" id="num_referencia_transferencia_local" placeholder="Ingrese el número de referencia" style="margin-bottom: 10px;">
        </div>

        <!-- Campos adicionales para Transferencia Internacional -->
        <div id="transferencia_internacional_fields" style="display: none; margin-top: 20px; margin-bottom: 20px; padding: 20px; border: 2px solid #fd7e14; border-radius: 8px; background-color: #fff8f0;">
            <h4 style="color: #fd7e14; margin-top: 0; margin-bottom: 15px;">Información de Transferencia Internacional</h4>
            
            <label for="banco_transferencia_internacional"><b>Banco</b></label><span class="badge-warning">*</span>
            <select name="banco_transferencia_internacional" id="banco_transferencia_internacional" style="margin-bottom: 15px;">
                <option value="">Seleccione el banco</option>
                <option value="BAC Credomatic">BAC Credomatic</option>
                <option value="Banco Atlántida">Banco Atlántida</option>
                <option value="Banco de Occidente">Banco de Occidente</option>
                <option value="Banco Popular">Banco Popular</option>
                <option value="BANPAIS">BANPAIS</option>
                <option value="BANCATLAN">BANCATLAN</option>
                <option value="Banco Ficohsa">Banco Ficohsa</option>
                <option value="BANHCAFE">BANHCAFE</option>
                <option value="Banco Promerica">Banco Promerica</option>
                <option value="Banco Davivienda">Banco Davivienda</option>
                <option value="Otro">Otro</option>
            </select>

            <label for="num_referencia_transferencia_internacional"><b># de Referencia</b></label><span class="badge-warning">*</span>
            <input type="text" name="num_referencia_transferencia_internacional" id="num_referencia_transferencia_internacional" placeholder="Ingrese el número de referencia" style="margin-bottom: 10px;">
        </div>

        <br><br>

        <!-- Otros campos opcionales -->
        <label for="pagador"><b>Nombre del Pagador</b></label>
        <input type="text" name="pagador" id="pagador" placeholder="Nombre del Pagador">

        <label for="rtn_pagador"><b>RTN del Pagador</b></label>
        <input type="text" name="rtn_pagador" id="rtn_pagador" placeholder="RTN del Pagador">

        <label for="num_poliza"><b>Número de Póliza</b></label>
        <input type="text" name="num_poliza" id="num_poliza" placeholder="Número de Póliza">

        <label for="num_cuenta"><b>Número de Cuenta</b></label>
        <input type="text" name="num_cuenta" id="num_cuenta" placeholder="Número de Cuenta">

        <!-- Campo oculto para el usuario procesador -->
        <input type="hidden" name="pdrus" value="<?php echo $_SESSION['id']; ?>">

        <hr>

        <button type="submit" id="validate" name="order" class="registerbtn">Guardar</button>
        <button onclick="location.href='cart.php'" class="pabtn">Cancelar</button>
    </div>
</form>


<script>
// Inicializar Select2 con comportamiento personalizado
$(document).ready(function () {
    $('.select2').select2({
        placeholder: "Escribe o selecciona un paciente",
        allowClear: true,
        dropdownAutoWidth: true,
        width: 'resolve',
    });

    // Forzar cierre de la lista desplegable al seleccionar una opción
    $('.select2').on('select2:select', function () {
        $(this).select2('close');
    });

    // Asegurar que el foco no reabra la lista
    $('.select2').on('select2:close', function () {
        $(this).blur();
    });
});

// Manejo de selección dinámica
function handleDynamicSelection() {
    const selectedOption = document.getElementById("nomcl_dynamic")?.selectedOptions[0];
    if (!selectedOption) {
        console.log("No se seleccionó ninguna opción");
        return;
    }

    // Asignar el DNI desde el atributo data-dni
    const dni = selectedOption.getAttribute("data-dni");
    if (dni) {
        document.getElementById("dni_paciente").value = dni;
    }

    // Asignar la fecha de nacimiento para calcular la edad (OPCIONAL)
    const fechaNacimiento = selectedOption.getAttribute("data-cump");
    console.log("DEBUG - Fecha de nacimiento capturada:", fechaNacimiento);
    console.log("DEBUG - Tipo de fecha:", typeof fechaNacimiento);
    
    if (fechaNacimiento && fechaNacimiento !== '0000-00-00' && fechaNacimiento !== '') {
        console.log("DEBUG - Procesando fecha válida:", fechaNacimiento);
        const birthDate = new Date(fechaNacimiento);
        console.log("DEBUG - Objeto Date creado:", birthDate);
        console.log("DEBUG - Timestamp válido:", !isNaN(birthDate.getTime()));
        
        // Verificar que la fecha sea válida
        if (isNaN(birthDate.getTime())) {
            console.warn("Advertencia: Fecha de nacimiento inválida:", fechaNacimiento);
            document.getElementById("edad_dynamic").value = "";
        } else {
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            
            // Ajustar edad si aún no ha cumplido años
            if (today.getMonth() < birthDate.getMonth() || 
                (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
                age--;
            }

            console.log("DEBUG - Cálculo de edad:", {
                añoNacimiento: birthDate.getFullYear(),
                añoActual: today.getFullYear(),
                edadCalculada: age,
                mesNacimiento: birthDate.getMonth(),
                mesActual: today.getMonth(),
                diaNacimiento: birthDate.getDate(),
                diaActual: today.getDate()
            });

            if (age >= 0 && age <= 120) {
                document.getElementById("edad_dynamic").value = age;
                console.log("✅ Edad calculada correctamente:", age);
            } else {
                console.warn("Advertencia: Edad calculada fuera de rango válido (0-120):", age);
                document.getElementById("edad_dynamic").value = "";
            }
        }
    } else {
        console.log("Info: No hay fecha de nacimiento para calcular la edad (campo opcional)");
        console.log("DEBUG - Valor de fecha:", fechaNacimiento);
        document.getElementById("edad_dynamic").value = "";
    }

    // Ocultar campos manuales si se selecciona un paciente dinámico
    document.getElementById("dni_manual_container").style.display = "none";
    document.getElementById("edad_manual_container").style.display = "none";
    document.getElementById("telefono_manual_container").style.display = "none";
    document.getElementById("dni_manual").value = "";
    document.getElementById("edad_manual").value = "";
    document.getElementById("telefono_manual").value = "";

    // Mostrar los valores actuales de los campos ocultos
    console.log("Valor actual de dni_paciente:", document.getElementById("dni_paciente").value);
    console.log("Valor actual de edad_dynamic:", document.getElementById("edad_dynamic").value);
}

// Manejo de entrada manual
function handleManualInput() {
    const manualInput = document.getElementById("nomcl_manual").value.trim();
    if (manualInput !== "") {
        document.getElementById("dni_manual_container").style.display = "block";
        document.getElementById("edad_manual_container").style.display = "block";
        document.getElementById("telefono_manual_container").style.display = "block";

        document.getElementById("dni_paciente").value = "";
        document.getElementById("edad_dynamic").value = "";

        console.log("Modo manual activado: limpiando campos dinámicos");
    } else {
        document.getElementById("dni_manual_container").style.display = "none";
        document.getElementById("edad_manual_container").style.display = "none";
        document.getElementById("telefono_manual_container").style.display = "none";
        document.getElementById("dni_manual").value = "";
        document.getElementById("edad_manual").value = "";
        document.getElementById("telefono_manual").value = "";

        console.log("Modo manual desactivado: ocultando campos manuales");
    }
}

// FUNCIÓN PARA MOSTRAR/OCULTAR CAMPOS SEGÚN MÉTODO DE PAGO
function mostrarCamposTarjeta() {
    var selectMetodo = document.getElementById("cxtcre");
    var divCamposEfectivo = document.getElementById("efectivo_fields");
    var divCamposTarjeta = document.getElementById("tarjeta_fields");
    var divCamposTransferencia = document.getElementById("transferencia_fields");
    var divCamposPagoMixto = document.getElementById("pago_mixto_fields");
    var divCamposBotonPago = document.getElementById("boton_pago_fields");
    var divCamposTransferenciaLocal = document.getElementById("transferencia_local_fields");
    var divCamposTransferenciaInternacional = document.getElementById("transferencia_internacional_fields");
    
    // Verificar que los elementos existen
    if (!selectMetodo) {
        return;
    }
    
    var valorSeleccionado = selectMetodo.value;
    
    // Ocultar todos los campos primero
    if (divCamposEfectivo) divCamposEfectivo.style.display = "none";
    if (divCamposTarjeta) divCamposTarjeta.style.display = "none";
    if (divCamposTransferencia) divCamposTransferencia.style.display = "none";
    if (divCamposPagoMixto) divCamposPagoMixto.style.display = "none";
    if (divCamposBotonPago) divCamposBotonPago.style.display = "none";
    if (divCamposTransferenciaLocal) divCamposTransferenciaLocal.style.display = "none";
    if (divCamposTransferenciaInternacional) divCamposTransferenciaInternacional.style.display = "none";
    
    // Mostrar campos según el método seleccionado
    if (valorSeleccionado === "Efectivo") {
        if (divCamposEfectivo) divCamposEfectivo.style.display = "block";
    } else if (valorSeleccionado === "Tarjeta") {
        if (divCamposTarjeta) divCamposTarjeta.style.display = "block";
    } else if (valorSeleccionado === "Transferencia") {
        if (divCamposTransferencia) divCamposTransferencia.style.display = "block";
    } else if (valorSeleccionado === "Pago Mixto") {
        if (divCamposPagoMixto) divCamposPagoMixto.style.display = "block";
    } else if (valorSeleccionado === "Boton de Pago") {
        if (divCamposBotonPago) divCamposBotonPago.style.display = "block";
    } else if (valorSeleccionado === "Transferencia Local") {
        if (divCamposTransferenciaLocal) divCamposTransferenciaLocal.style.display = "block";
    } else if (valorSeleccionado === "Transferencia Internacional") {
        if (divCamposTransferenciaInternacional) divCamposTransferenciaInternacional.style.display = "block";
    } else {
        // Limpiar todos los campos para cualquier otra opción
        limpiarCamposAdicionales();
    }
    
    // Llamar a la función de mapeo después de mostrar/ocultar campos
    mapearCamposPago();
}

// Función para limpiar todos los campos adicionales
function limpiarCamposAdicionales() {
    // Limpiar campos de tarjeta
    var tipoTarjeta = document.getElementById("tipo_tarjeta");
    var bancoEmisor = document.getElementById("banco_emisor");
    var posCobrado = document.getElementById("pos_cobrado");
    
    if (tipoTarjeta) tipoTarjeta.value = "";
    if (bancoEmisor) bancoEmisor.value = "";
    if (posCobrado) posCobrado.value = "";
    
    // Limpiar campos de transferencia
    var bancoTransferencia = document.getElementById("banco_transferencia");
    var numReferencia = document.getElementById("num_referencia");
    
    if (bancoTransferencia) bancoTransferencia.value = "";
    if (numReferencia) numReferencia.value = "";

    // Limpiar campos de efectivo
    var montoRecibido = document.getElementById("monto_recibido");
    var cambioDevolver = document.getElementById("cambio_devolver");
    
    if (montoRecibido) montoRecibido.value = "";
    if (cambioDevolver) cambioDevolver.value = "0.00";

    // Limpiar campos de pago mixto
    var tipoPagoMixto = document.getElementById("tipo_pago_mixto");
    var montoTarjetaMixto = document.getElementById("monto_tarjeta_mixto");
    var montoEfectivoMixto = document.getElementById("monto_efectivo_mixto");
    var cambioDevolverMixto = document.getElementById("cambio_devolver_mixto");
    
    if (tipoPagoMixto) tipoPagoMixto.value = "";
    if (montoTarjetaMixto) montoTarjetaMixto.value = "";
    if (montoEfectivoMixto) montoEfectivoMixto.value = "";
    if (cambioDevolverMixto) cambioDevolverMixto.value = "0.00";
    
    // Limpiar campos de botón de pago
    var bancoBotonPago = document.getElementById("banco_boton_pago");
    var numReferenciaBotonPago = document.getElementById("num_referencia_boton_pago");
    
    if (bancoBotonPago) bancoBotonPago.value = "";
    if (numReferenciaBotonPago) numReferenciaBotonPago.value = "";
    
    // Limpiar campos de transferencia local
    var bancoTransferenciaLocal = document.getElementById("banco_transferencia_local");
    var numReferenciaTransferenciaLocal = document.getElementById("num_referencia_transferencia_local");
    
    if (bancoTransferenciaLocal) bancoTransferenciaLocal.value = "";
    if (numReferenciaTransferenciaLocal) numReferenciaTransferenciaLocal.value = "";
    
    // Limpiar campos de transferencia internacional
    var bancoTransferenciaInternacional = document.getElementById("banco_transferencia_internacional");
    var numReferenciaTransferenciaInternacional = document.getElementById("num_referencia_transferencia_internacional");
    
    if (bancoTransferenciaInternacional) bancoTransferenciaInternacional.value = "";
    if (numReferenciaTransferenciaInternacional) numReferenciaTransferenciaInternacional.value = "";
}

// Función para calcular el cambio en pago con efectivo
function calcularCambioEfectivo() {
    var totalAPagar = parseFloat(document.getElementById("total_a_pagar").value) || 0;
    var montoRecibido = parseFloat(document.getElementById("monto_recibido").value) || 0;
    var cambioDevolver = document.getElementById("cambio_devolver");
    
    if (cambioDevolver) {
        var cambio = montoRecibido - totalAPagar;
        if (cambio < 0) {
            cambioDevolver.value = "0.00";
            cambioDevolver.style.color = "#dc3545";
            cambioDevolver.style.borderColor = "#dc3545";
        } else {
            cambioDevolver.value = cambio.toFixed(2);
            cambioDevolver.style.color = "#155724";
            cambioDevolver.style.borderColor = "#28a745";
        }
    }
}

// Función para calcular el cambio en pago mixto
function calcularCambioMixto() {
    var totalAPagar = parseFloat(document.getElementById("total_a_pagar").value) || 0;
    var montoTarjeta = parseFloat(document.getElementById("monto_tarjeta_mixto").value) || 0;
    var montoEfectivo = parseFloat(document.getElementById("monto_efectivo_mixto").value) || 0;
    var cambioDevolverMixto = document.getElementById("cambio_devolver_mixto");
    
    if (cambioDevolverMixto) {
        // Calcular cuánto falta después de la tarjeta
        var faltante = totalAPagar - montoTarjeta;
        
        // Si el efectivo es mayor al faltante, hay cambio
        var cambio = montoEfectivo - faltante;
        
        if (cambio < 0) {
            cambioDevolverMixto.value = "0.00";
            cambioDevolverMixto.style.color = "#666";
            cambioDevolverMixto.style.borderColor = "#ff6b35";
        } else {
            cambioDevolverMixto.value = cambio.toFixed(2);
            cambioDevolverMixto.style.color = "#cc3300";
            cambioDevolverMixto.style.borderColor = "#ff6b35";
        }
    }
}

// Validación del formulario antes del envío
function validacion() {
    const nomclManual = document.getElementById("nomcl_manual").value.trim();
    const dniManual = document.getElementById("dni_manual").value.trim();
    const edadManual = document.getElementById("edad_manual").value.trim();
    const nomclDynamic = document.getElementById("nomcl_dynamic").value;
    const dniDynamic = document.getElementById("dni_paciente").value.trim();
    const edadDynamic = document.getElementById("edad_dynamic").value.trim();

    console.log("Validando formulario:", {
        nomclManual, dniManual, edadManual,
        nomclDynamic, dniDynamic, edadDynamic
    });

    // LOS CAMPOS DEL PACIENTE NO SON OBLIGATORIOS - Solo informativos
    console.log("Datos del paciente (opcionales):", {
        pacienteRegistrado: nomclDynamic ? "Sí" : "No",
        pacienteAmbulatorio: nomclManual ? "Sí" : "No"
    });

    // SOLO VALIDAR CAMPOS OBLIGATORIOS: TIPO y MÉTODO DE PAGO
    const tipo = document.getElementById("tipo").value;
    const metodoPago = document.getElementById("cxtcre").value;

    if (!tipo) {
        alert("Debe seleccionar el tipo de atención.");
        document.getElementById("tipo").focus();
        return false;
    }

    if (!metodoPago) {
        alert("Debe seleccionar el método de pago.");
        document.getElementById("cxtcre").focus();
        return false;
    }

    // Validar campos adicionales según método de pago
    if (metodoPago === "Tarjeta") {
        const tipoTarjeta = document.getElementById("tipo_tarjeta").value;
        const bancoEmisor = document.getElementById("banco_emisor").value;
        const posCobrado = document.getElementById("pos_cobrado").value;
        
        if (!tipoTarjeta || !bancoEmisor || !posCobrado) {
            alert("Para pagos con tarjeta, debe completar todos los campos de tarjeta.");
            return false;
        }
    }

    if (metodoPago === "Transferencia") {
        const bancoTransferencia = document.getElementById("banco_transferencia").value;
        const numReferencia = document.getElementById("num_referencia").value;
        
        if (!bancoTransferencia || !numReferencia) {
            alert("Para transferencias, debe completar todos los campos de transferencia.");
            return false;
        }
    }

    if (metodoPago === "Efectivo") {
        const montoRecibido = parseFloat(document.getElementById("monto_recibido").value) || 0;
        const totalAPagar = parseFloat(document.getElementById("total_a_pagar").value) || 0;
        
        if (!montoRecibido || montoRecibido <= 0) {
            alert("Para pagos en efectivo, debe ingresar el monto recibido del cliente.");
            document.getElementById("monto_recibido").focus();
            return false;
        }
        
        if (montoRecibido < totalAPagar) {
            alert("El monto recibido no puede ser menor al total a pagar. Total: L. " + totalAPagar.toFixed(2));
            document.getElementById("monto_recibido").focus();
            return false;
        }
    }

    if (metodoPago === "Pago Mixto") {
        const tipoPagoMixto = document.getElementById("tipo_pago_mixto").value;
        const montoTarjeta = parseFloat(document.getElementById("monto_tarjeta_mixto").value) || 0;
        const montoEfectivo = parseFloat(document.getElementById("monto_efectivo_mixto").value) || 0;
        const totalAPagar = parseFloat(document.getElementById("total_a_pagar").value) || 0;
        
        if (!tipoPagoMixto) {
            alert("Para pago mixto, debe seleccionar el tipo de pago.");
            return false;
        }
        
        if (!montoTarjeta || montoTarjeta <= 0) {
            alert("Para pago mixto, debe ingresar el monto pagado con tarjeta.");
            document.getElementById("monto_tarjeta_mixto").focus();
            return false;
        }
        
        if (!montoEfectivo || montoEfectivo <= 0) {
            alert("Para pago mixto, debe ingresar el monto pagado en efectivo.");
            document.getElementById("monto_efectivo_mixto").focus();
            return false;
        }
        
        // Validar que la suma de tarjeta + efectivo sea mayor o igual al total (puede haber cambio)
        const sumaPagos = montoTarjeta + montoEfectivo;
        
        // Calcular el cambio
        const faltante = totalAPagar - montoTarjeta;
        const cambio = montoEfectivo - faltante;
        
        // La suma debe cubrir al menos el total (puede haber cambio si el efectivo excede lo necesario)
        if (sumaPagos < totalAPagar) {
            alert("La suma del monto en tarjeta (L. " + montoTarjeta.toFixed(2) + ") y el monto en efectivo (L. " + montoEfectivo.toFixed(2) + ") debe ser mayor o igual al total a pagar (L. " + totalAPagar.toFixed(2) + ").");
            return false;
        }
        
        // Verificar que el monto en tarjeta no exceda el total
        if (montoTarjeta > totalAPagar) {
            alert("El monto en tarjeta (L. " + montoTarjeta.toFixed(2) + ") no puede ser mayor al total a pagar (L. " + totalAPagar.toFixed(2) + ").");
            return false;
        }
    }

    if (metodoPago === "Boton de Pago") {
        const bancoBotonPago = document.getElementById("banco_boton_pago").value;
        const numReferenciaBotonPago = document.getElementById("num_referencia_boton_pago").value;

        if (!bancoBotonPago || !numReferenciaBotonPago) {
            alert("Para pagos con Botón de Pago, debe completar el banco y el número de referencia.");
            return false;
        }
    }

    if (metodoPago === "Transferencia Local") {
        const bancoTransferenciaLocal = document.getElementById("banco_transferencia_local").value;
        const numReferenciaTransferenciaLocal = document.getElementById("num_referencia_transferencia_local").value;

        if (!bancoTransferenciaLocal || !numReferenciaTransferenciaLocal) {
            alert("Para pagos con Transferencia Local, debe completar el banco y el número de referencia.");
            return false;
        }
    }

    if (metodoPago === "Transferencia Internacional") {
        const bancoTransferenciaInternacional = document.getElementById("banco_transferencia_internacional").value;
        const numReferenciaTransferenciaInternacional = document.getElementById("num_referencia_transferencia_internacional").value;

        if (!bancoTransferenciaInternacional || !numReferenciaTransferenciaInternacional) {
            alert("Para pagos con Transferencia Internacional, debe completar el banco y el número de referencia.");
            return false;
        }
    }

    console.log("Validación completada exitosamente - Solo TIPO y MÉTODO DE PAGO son obligatorios");
    return true;
}

// Función para mapear campos de banco y referencia según el método de pago
function mapearCamposPago() {
    const metodoPago = document.getElementById("cxtcre").value;
    
    // Ocultar todos los campos de mapeo primero
    document.getElementById("banco_transferencia").style.display = "none";
    document.getElementById("num_referencia").style.display = "none";
    
    // Mapear campos según el método seleccionado
    if (metodoPago === "Transferencia") {
        // Usar los campos originales de transferencia
        document.getElementById("banco_transferencia").style.display = "block";
        document.getElementById("num_referencia").style.display = "block";
    } else if (metodoPago === "Boton de Pago") {
        // Mapear a campos de botón de pago
        const bancoBotonPago = document.getElementById("banco_boton_pago").value;
        const numRefBotonPago = document.getElementById("num_referencia_boton_pago").value;
        
        // Copiar valores a los campos de transferencia para la base de datos
        document.getElementById("banco_transferencia").value = bancoBotonPago;
        document.getElementById("num_referencia").value = numRefBotonPago;
    } else if (metodoPago === "Transferencia Local") {
        // Mapear a campos de transferencia local
        const bancoLocal = document.getElementById("banco_transferencia_local").value;
        const numRefLocal = document.getElementById("num_referencia_transferencia_local").value;
        
        // Copiar valores a los campos de transferencia para la base de datos
        document.getElementById("banco_transferencia").value = bancoLocal;
        document.getElementById("num_referencia").value = numRefLocal;
    } else if (metodoPago === "Transferencia Internacional") {
        // Mapear a campos de transferencia internacional
        const bancoInternacional = document.getElementById("banco_transferencia_internacional").value;
        const numRefInternacional = document.getElementById("num_referencia_transferencia_internacional").value;
        
        // Copiar valores a los campos de transferencia para la base de datos
        document.getElementById("banco_transferencia").value = bancoInternacional;
        document.getElementById("num_referencia").value = numRefInternacional;
    }
}

// Llamar a la función de mapeo cuando cambie el método de pago
document.addEventListener('DOMContentLoaded', function() {
    const selectMetodo = document.getElementById("cxtcre");
    if (selectMetodo) {
        selectMetodo.addEventListener('change', function() {
            mostrarCamposTarjeta();
            // Pequeño delay para asegurar que los campos se muestren antes del mapeo
            setTimeout(mapearCamposPago, 100);
        });
    }
});
</script>

</main>

        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
   
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <?php
    include_once '../../backend/php/add_check.php'?>

    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
    </script>

</body>
</html>


