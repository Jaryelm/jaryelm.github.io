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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">



    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../almacen/menu.php';
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
include_once '../almacen/perfil.php';
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



        <!-- Opciones de Navegación -->
        <button class="button" onclick="cambiarColor(this, 'compra_unificada_user.php')">Compra e inventario</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_compras_user.php')">Compras Registradas</button>
        <button class="button" onclick="cambiarColor(this, 'mostrar_user.php')">Lista de Inventario</button>
        <button class="button" onclick="cambiarColor(this, 'reorden_user.php')">Punto de Reorden</button>
        <button class="button" onclick="cambiarColor(this, 'lista_solicitud_reorden.php')">Autorización Compras Almacen</button>
        <button class="button" onclick="cambiarColor(this, 'lista_requisiciones_user.php')">Requisiciones</button>

           
<form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validarFormularioCompleto()">
    <div class="containerss">
        <h1>Registrar Compras</h1>

        <br>

        <div class="alert-danger">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
            <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
        </div>
        <hr>
        <br>

        <!-- Campos Existentes -->
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
    <option value="AMBULANCIA 1">AMBULANCIA 1</option>
    <option value="AMBULANCIA 2">AMBULANCIA 2</option>
    <option value="QUIROFANO">QUIROFANO</option>
    <option value="SALA DE CUNA">SALA DE CUNA</option>
    <option value="SALA DE PARTOS">SALA DE PARTOS</option>
    <option value="CARRO ROJO EMERGENCIA">CARRO ROJO EMERGENCIA</option>
    <option value="SALA DE PROCEDIMIENTOS">SALA DE PROCEDIMIENTOS</option>
    <option value="ALMACEN ENFERMERIA HOSPITAL">ALMACEN ENFERMERIA HOSPITAL</option>
    <option value="TECNOLOGÍA IT">TECNOLOGÍA IT</option>
    <option value="CLINICA GINECOLOGÍA">CLINICA GINECOLOGÍA</option>
    <option value="USG MEDICASA">USG MEDICASA</option>
    <option value="CARDIOLOGÍA MEDICASA">CARDIOLOGÍA MEDICASA</option>
    <option value="UNIDAD DIGESTIVA">UNIDAD DIGESTIVA</option>
    <option value="EEG">EEG</option>
    <option value="CUBICULO GINECOLOGÍA">CUBICULO GINECOLOGÍA</option>
    <option value="ALMACEN ENFERMERIA EMERGENCIA">ALMACEN ENFERMERIA EMERGENCIA</option>
    <option value="PEDIATRIA">PEDIATRIA</option>
    <option value="RADIOLOGÍA">RADIOLOGÍA</option>
    <option value="MERCADEO">MERCADEO</option>
    <option value="RECEPCIÓN">RECEPCIÓN</option>
    <option value="FACTURACIÓN">FACTURACIÓN</option>
    <option value="TOMOGRAFÍA">TOMOGRAFÍA</option>
    <option value="CAJA">CAJA</option>
    <option value="FARMACIA EXTERNA">FARMACIA EXTERNA</option>
    <option value="MAMOGRAFÍA">MAMOGRAFÍA</option>
    <option value="ODONTOLOGÍA">ODONTOLOGÍA</option>
    <option value="VENTAS EXTERNAS">VENTAS EXTERNAS</option>

</select>

        <label for="prov_datos"><b>Seleccione Proveedor Comercial</b></label>
        <select name="prov_datos" id="prov_datos" class="select2">
            <option>Seleccione</option>
        </select>

        <br><br>

        <!-- Campo Factura -->
        <label for="dato_fac"><b>Factura No.</b></label><span class="badge-warning">*</span>
        <input type="text" id="dato_fac" name="dato_fac" required maxlength="19" minlength="19"
               placeholder="000-003-01-00031284-7854-2345" 
               title="OBLIGATORIO: Exactamente 19 caracteres incluyendo guiones"
               oninput="validarFacturaExacta(this)"
               onblur="validarFacturaExacta(this)">
        <div id="factura-validacion-container" style="margin-bottom: 15px;"></div>

<!-- Campo Fecha de Emisión -->
<label for="fecha_emision"><b>Fecha Factura</b></label><span class="badge-warning">*</span>
<input type="date" id="fecha_emision" name="fecha_emision" required onchange="calcularFechaVencimiento()">

<!-- Campo Términos de Pago -->
<label for="cred_cont"><b>Términos de Pago</b></label><span class="badge-warning">*</span>
<div>
    <label for="cred_credito">
        <input type="radio" id="cred_credito" name="cred_cont" value="Credito" required onclick="toggleCampos('credito')"> Crédito
    </label>
    <label for="cred_contado">
        <input type="radio" id="cred_contado" name="cred_cont" value="Contado" required onclick="toggleCampos('contado')"> Contado
    </label>
    <label for="cred_prima">
        <input type="radio" id="cred_prima" name="cred_cont" value="Prima" required onclick="toggleCampos('prima')"> Otros Terminos
    </label>
    <label for="cred_consignacion">
        <input type="radio" id="cred_consignacion" name="cred_cont" value="Consignacion" required onclick="toggleCampos('consignacion')"> Consignación
    </label>
</div>

<!-- Campo Días de Crédito (Oculto por defecto) -->
<div id="dias_credito_field" style="display: none; margin-top: 10px;">
    <label for="dias_credito"><b>Días de Crédito</b></label>
    <input type="number" id="dias_credito" name="dias_credito" min="1" placeholder="Ingrese el número de días" oninput="calcularFechaVencimiento()">
</div>

<!-- Campos para Otros Terminos (Ocultos por defecto) -->
<div id="prima_fields" style="display: none; margin-top: 10px;">
    <label for="porcentaje_prima"><b>% de Prima</b></label>
    <input type="number" id="porcentaje_prima" name="porcentaje_prima" min="0" max="100" placeholder="Ingrese el porcentaje de prima">
    
    <label for="cuotas_pendientes"><b>Cuotas Pendientes</b></label>
    <input type="number" id="cuotas_pendientes" name="cuotas_pendientes" min="1" placeholder="Ingrese el número de cuotas" oninput="calcularFechaVencimiento()">
</div>

<br>

<!-- Campo Fecha de Vencimiento (Calculado automáticamente) -->
<label for="fech_vence"><b>Fecha Vencimiento</b></label><span class="badge-warning">*</span>
<input type="date" id="fech_vence" name="fech_vence" required readonly placeholder="Fecha de Vencimiento Calculada">

        <hr>

<!-- Tabla Editable de Productos -->
<table id="items_table" border="1" width="100%" style="border-collapse: collapse; margin-top: 20px;">
    <thead>
        <tr>
            <th>Cuenta</th>
            <th>Código</th>
            <th>Cantidad</th>
            <th>Unidad</th>
            <th>Descripción</th>
            <th>Precio Unitario</th>
            <th>Exento</th>
            <th>Gravado</th>
            <th>ISV</th>
            <th>Subtotal</th>
            <th>Descuento %</th>
            <th>Total por Item</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <select name="cat_cuenta[]" class="select2" style="width: 200px;">
                    <option value="">Seleccione</option>
                    <!-- Aquí se cargan las opciones de cuentas -->
                </select>
            </td>
            <td><input type="text" name="codigo_producto[]" required placeholder="Código"></td>
            <td><input type="number" name="cantidad[]" min="1" required placeholder="Cantidad" oninput="calcularTotales(this)"></td>
            <td><input type="text" name="unidad[]" required placeholder="Unidad"></td>
            <td><input type="text" name="descripcion[]" required placeholder="Descripción"></td>
            <td><input type="number" name="precio_unitario[]" min="0" step="0.0001" required placeholder="Precio Unitario" oninput="calcularTotales(this)"></td>
            <td><input type="checkbox" name="exento[]" onclick="toggleExentoGravado(this)" value="exento"></td>
            <td><input type="checkbox" name="gravado[]" onclick="toggleExentoGravado(this)" value="gravado"></td>
            <td><input type="number" name="isv[]" min="0" step="0.01" required readonly placeholder="ISV"></td>
            <td><input type="number" name="subtotal[]" min="0" step="0.01" required readonly placeholder="Subtotal"></td>
            <td><input type="number" name="descuento_porcentaje[]" min="0" max="100" step="0.01" placeholder="% Descuento" oninput="calcularTotales(this)"></td>
            <td><input type="number" name="total_item[]" min="0" step="0.01" required readonly placeholder="Total por Item"></td>
            <td><button type="button" class="item-table-button" onclick="removeItem(this)">Eliminar</button></td>
        </tr>
    </tbody>
</table>

<!-- Botón para agregar más filas -->
<button type="button" class="item-table-button" onclick="addItemRow()">Agregar Producto</button>

<!-- Totales Generales -->
<br><br>
<label for="isv_global"><b>ISV</b></label><span class="badge-warning">*</span>
<input type="number" id="isv_global" name="isv_global" min="0" step="0.01" readonly placeholder="Total ISV">

<label for="sub_total"><b>Subtotal</b></label><span class="badge-warning">*</span>
<input type="number" id="sub_total" name="sub_total" min="0" step="0.01" readonly placeholder="Subtotal">

<label for="total"><b>Total</b></label><span class="badge-warning">*</span>
<input type="number" id="total" name="total" min="0" step="0.01" readonly placeholder="Total General">

<script>

    function toggleExentoGravado(checkbox) {
        const row = checkbox.closest("tr");
        const exentoCheckbox = row.querySelector('input[name="exento[]"]');
        const gravadoCheckbox = row.querySelector('input[name="gravado[]"]');

        if (checkbox.value === "exento" && checkbox.checked) {
            gravadoCheckbox.checked = false;
        } else if (checkbox.value === "gravado" && checkbox.checked) {
            exentoCheckbox.checked = false;
        }
        
        calcularTotales(checkbox);
    }

    function calcularTotales(element) {
        const row = element.closest("tr");
        const cantidad = parseFloat(row.querySelector('input[name="cantidad[]"]').value) || 0;
        const precioUnitario = parseFloat(row.querySelector('input[name="precio_unitario[]"]').value) || 0;
        const descuentoPorcentaje = parseFloat(row.querySelector('input[name="descuento_porcentaje[]"]').value) || 0;
        const isvField = row.querySelector('input[name="isv[]"]');
        const subtotalField = row.querySelector('input[name="subtotal[]"]');
        const totalItemField = row.querySelector('input[name="total_item[]"]');
        const gravado = row.querySelector('input[name="gravado[]"]').checked ? "Si" : "No";

        let isv = 0;
        let subtotal = cantidad * precioUnitario;

        if (gravado === "Si") {
            isv = subtotal * 0.15; // Aplicar ISV del 15%
        }

        isvField.value = isv.toFixed(2);
        subtotalField.value = subtotal.toFixed(2);

        const descuentoAplicado = subtotal * (descuentoPorcentaje / 100);
        const totalItem = subtotal + isv - descuentoAplicado;
        totalItemField.value = totalItem.toFixed(2);

        calcularTotalesGenerales();
    }

    function calcularTotalesGenerales() {
        const rows = document.querySelectorAll('#items_table tbody tr');
        let totalISV = 0, totalSubtotal = 0, totalGeneral = 0;

        rows.forEach(row => {
            const subtotal = parseFloat(row.querySelector('input[name="subtotal[]"]').value) || 0;
            const isv = parseFloat(row.querySelector('input[name="isv[]"]').value) || 0;
            const totalItem = parseFloat(row.querySelector('input[name="total_item[]"]').value) || 0;

            totalISV += isv;
            totalSubtotal += subtotal;
            totalGeneral += totalItem;
        });

        document.getElementById('isv_global').value = totalISV.toFixed(2);
        document.getElementById('sub_total').value = totalSubtotal.toFixed(2);
        document.getElementById('total').value = totalGeneral.toFixed(2);
    }

    function removeItem(button) {
        const row = button.parentElement.parentElement;
        row.remove();
        calcularTotalesGenerales();
    }

    function validarFacturaExacta(input) {
        // Limitar a 19 caracteres máximo
        if (input.value.length > 19) {
            input.value = input.value.substring(0, 19);
        }
        
        // Usar el contenedor específico para los mensajes de validación
        const container = document.getElementById('factura-validacion-container');
        let contador = container.querySelector('.contador-caracteres');
        let mensaje = container.querySelector('.mensaje-validacion');
        
        if (!contador) {
            contador = document.createElement('small');
            contador.className = 'contador-caracteres';
            contador.style.display = 'block';
            contador.style.fontSize = '12px';
            contador.style.marginTop = '3px';
            container.appendChild(contador);
        }
        
        if (!mensaje) {
            mensaje = document.createElement('small');
            mensaje.className = 'mensaje-validacion';
            mensaje.style.display = 'block';
            mensaje.style.fontSize = '12px';
            mensaje.style.marginTop = '2px';
            mensaje.style.fontWeight = 'bold';
            container.appendChild(mensaje);
        }
        
        const longitud = input.value.length;
        contador.textContent = `${longitud}/19 caracteres`;
        
        // Validación estricta: exactamente 19 caracteres
        if (longitud === 19) {
            // Válido
            input.style.borderColor = '#28a745';
            input.style.backgroundColor = '#f8fff9';
            contador.style.color = '#28a745';
            mensaje.textContent = 'Formato correcto';
            mensaje.style.color = '#28a745';
            input.setCustomValidity('');
        } else if (longitud === 0) {
            // Vacío
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#fff5f5';
            contador.style.color = '#dc3545';
            mensaje.textContent = 'Campo obligatorio - Debe tener exactamente 19 caracteres';
            mensaje.style.color = '#dc3545';
            input.setCustomValidity('Debe ingresar exactamente 19 caracteres incluyendo guiones');
        } else {
            // Longitud incorrecta
            input.style.borderColor = '#dc3545';
            input.style.backgroundColor = '#fff5f5';
            contador.style.color = '#dc3545';
            const faltantes = 19 - longitud;
            if (longitud < 19) {
                mensaje.textContent = `Faltan ${faltantes} caracteres para completar los 19 requeridos`;
            }
            mensaje.style.color = '#dc3545';
            input.setCustomValidity('Debe tener exactamente 19 caracteres incluyendo guiones');
        }
    }

    function validarFormularioCompleto() {
        const facturaInput = document.getElementById('dato_fac');
        const longitud = facturaInput.value.length;
        
        if (longitud !== 19) {
            // Forzar validación visual
            validarFacturaExacta(facturaInput);
            
            // Mostrar alerta específica
            if (longitud === 0) {
                alert('ERROR: El campo "Factura No." es obligatorio.\n\nDebe ingresar exactamente 19 caracteres incluyendo guiones.\n\nEjemplo: 000-003-01-00031284-7854-2345');
            } else {
                const faltantes = 19 - longitud;
                alert(`ERROR: El campo "Factura No." debe tener exactamente 19 caracteres.\n\nActualmente tiene: ${longitud} caracteres\nFaltan: ${faltantes} caracteres\n\nEjemplo correcto: 000-003-01-00031284-7854-2345`);
            }
            
            // Enfocar el campo problemático
            facturaInput.focus();
            facturaInput.select();
            
            return false; // Prevenir envío del formulario
        }
        
        return true; // Permitir envío si todo está correcto
    }

    $(document).ready(function () {
        $('.select2').select2(); // Inicializar select2 en el inicio
    });
</script>

<style>
    #items_table input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
</style>


        <hr>
        <button type="submit" name="add_medicine" class="registerbtn">Guardar</button>
    </div>
</form>

        <!-- estilos para la tabla dinamica de filas -->
<style>
    /* Estilo para la tabla */
    #items_table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background-color: #f9f9f9;
    }

    /* Estilo para las celdas de encabezado */
    #items_table thead th {
        background-color: #06adbf;
        color: white;
        padding: 10px;
        text-align: left;
        font-weight: bold;
        border-bottom: 2px solid #035c67;
    }

    /* Estilo para las celdas del cuerpo de la tabla */
    #items_table tbody td {
        padding: 8px;
        border-bottom: 1px solid #e0e0e0;
    }

    /* Alternar color para las filas */
    #items_table tbody tr:nth-child(even) {
        background-color: #e6f7f8; /* un tono más claro de #06adbf */
    }

    #items_table tbody tr:nth-child(odd) {
        background-color: #ffffff;
    }

    /* Estilo para los inputs dentro de la tabla */
    #items_table input[type="text"],
    #items_table input[type="number"] {
        width: 95%;
        padding: 5px;
        border: 1px solid #dcdcdc;
        border-radius: 3px;
    }

    /* Botones para agregar y eliminar productos */
    #items_table button {
        background-color: #035c67;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    #items_table button:hover {
        background-color: #06adbf;
    }

/* Estilo para los botones específicos de la tabla */
.item-table-button {
    background-color: #035c67;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.item-table-button:hover {
    background-color: #06adbf;
}
</style>


        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/cat_nompro.js"></script>
    <script src="../../backend/js/cat_proveedores.js"></script>
    <script src="../../backend/js/cat_cuentas.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/fech_vence.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- Include jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
    </script>

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
 <?php include_once '../../backend/registros/reg_compras.php' ?>
</body>
</html>


