<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
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
    <button class="button" onclick="cambiarColor(this, '../almacen/cart.php')">Pagar</button>
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
            servicios_hospital.total AS serv_price 
        FROM cart
        INNER JOIN users ON cart.user_id = users.id
        LEFT JOIN product ON cart.idprcd = product.idprcd AND cart.type = 'producto'
        LEFT JOIN servicios_hospital ON cart.id_servicio = servicios_hospital.id AND cart.type = 'servicio'
        WHERE cart.user_id = ?
    ");
    $select_cart_items->execute([$user_id]);

    $summary = [];
    if ($select_cart_items->rowCount() > 0) {
        $items_counted = ['product' => [], 'service' => []];
        while ($fetch_cart_items = $select_cart_items->fetch(PDO::FETCH_ASSOC)) {
            $is_product = $fetch_cart_items['type'] === 'producto';
            // Usar el precio del carrito directamente (ya tiene el precio correcto, incluso si es 0.00)
            $price = $fetch_cart_items['price'];
            $code = $is_product ? $fetch_cart_items['prod_cod'] : $fetch_cart_items['serv_cod'];

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
                } elseif (!$is_product && !in_array($fetch_cart_items['idv'], $items_counted['service'])) {
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

<!-- Campo Edad del Paciente Dinámico (Visible para depuración) -->
<input type="hidden" name="edad_dynamic" id="edad_dynamic" value="" readonly>

<!-- Campo DNI dinámico (Visible para depuración) -->
<input type="hidden" name="dni_paciente" id="dni_paciente" value="" readonly>


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
        <label for="remitente"><b>Remitente (Médicos)</b></label>
        <select id="remitente" name="remitente" class="select2" style="width: 100%;" placeholder="Escribe o selecciona un remitente" required>
            <option value="" selected disabled>Escribe o selecciona un remitente</option>
            <?php
            $query = $connect->prepare("SELECT CONCAT(nodoc, ' ', apdoc) AS full_name FROM doctor");
            $query->execute();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                echo '<option value="' . htmlspecialchars($row['full_name']) . '">' . htmlspecialchars($row['full_name']) . '</option>';
            }
            ?>
        </select>

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
        <label for="cxtcre"><b>Método de Pago</b></label>
        <select required name="cxtcre" id="cxtcre">
            <option value="" selected disabled>Seleccione</option>
            <option value="Efectivo">Efectivo</option>
            <option value="Tarjeta">Tarjeta</option>
            <option value="Pago Mixto">Pago Mixto</option>
            <option value="Transferencia">Transferencia</option>
            <option value="Boton de Pago">Botón de Pago</option>
            <option value="Transferencia Internacional">Transferencia Internacional</option>
            <option value="Transferencia Local">Transferencia Local</option>
        </select>

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
        console.log("DNI asignado correctamente:", dni);
    }

    // Asignar la fecha de nacimiento para calcular la edad
    const fechaNacimiento = selectedOption.getAttribute("data-cump");
    if (fechaNacimiento) {
        const birthDate = new Date(fechaNacimiento);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        if (today.getMonth() < birthDate.getMonth() || 
            (today.getMonth() === birthDate.getMonth() && today.getDate() < birthDate.getDate())) {
            age--;
        }

        if (age > 0) {
            document.getElementById("edad_dynamic").value = age;
            console.log("Edad calculada correctamente:", age);
        } else {
            console.error("Error: Edad calculada inválida:", age);
        }
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

// Validación del formulario antes del envío
function validacion() {
    const nomclManual = document.getElementById("nomcl_manual").value.trim();
    const dniManual = document.getElementById("dni_manual").value.trim();
    const edadManual = document.getElementById("edad_manual").value.trim();
    const nomclDynamic = document.getElementById("nomcl_dynamic").value;
    const dniDynamic = document.getElementById("dni_paciente").value.trim();
    const edadDynamic = document.getElementById("edad_dynamic").value.trim();

    // Validar método dinámico
    if (nomclDynamic && dniDynamic && edadDynamic) {
        console.log("Paciente registrado validado correctamente:", { nomclDynamic, dniDynamic, edadDynamic });
        return true;
    }

    // Validar método manual
    if (nomclManual && dniManual && edadManual) {
        console.log("Paciente ambulatorio validado correctamente:", { nomclManual, dniManual, edadManual });
        return true;
    }

    // Error si ninguno es válido
    alert("Debe completar los datos del paciente correctamente.");
    console.error("Error: Datos del paciente incompletos.");
    return false;
}
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

    <script src="/backend/vendor/sweetalert/sweetalert.min.js"></script>
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


