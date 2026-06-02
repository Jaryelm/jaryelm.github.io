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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <title>MEDIDATA</title>
</head>
<body>

<?php
include_once '../auxcontable/menu.php';
// incuir el archivo menu principal
?>

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
include_once '../auxcontable/perfil.php';
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

<h1 class="title"><?php echo $saludo . ', <strong>' . $_SESSION['name'] . '</strong>'; ?></h1>

<button class="button" onclick="cambiarColor(this, 'formulario_directorio_user.php')">Registrar Proveedores</button>
<button class="button" onclick="cambiarColor(this, 'tabla_directorio_user.php')">Directorio Médico</button>
<button class="button" onclick="cambiarColor(this, 'tabla_directorio_comercial_user.php')">Directorio Comercial</button>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <style>
/* Contenedor principal para los formularios */
.b_container {
    display: flex;
    flex-wrap: wrap;
    max-width: 1400px;
    margin: 0 auto;
    gap: 40px;
    padding: 40px;
    box-sizing: border-box;
}

/* Botones para seleccionar el formulario */
.form-selector {
    display: flex;
    justify-content: center;
    flex-wrap: wrap; /* Permitir que los botones se envuelvan en varias líneas si es necesario */
    margin-bottom: 20px;
    width: 100%; /* Asegura que el contenedor de botones ocupe todo el ancho disponible */
    box-sizing: border-box; /* Incluye el padding y el borde en el cálculo del ancho total */
}

.form-selector button {
    flex: 1 0 200px; /* Permite que los botones se expandan y tengan un ancho inicial de 200px */
    max-width: 250px; /* Establece un ancho máximo más amplio para los botones */
    margin: 5px; /* Ajusta el margen para que haya espacio entre los botones */
    padding: 12px 24px; /* Aumenta el padding para hacer los botones más grandes */
    font-size: 16px;
    cursor: pointer;
    background-color: #035c67;
    color: white;
    border: none;
    border-radius: 6px;
    transition: background-color 0.3s ease;
}

.form-selector button:hover {
    background-color: #06adbf;
}

/* Estilos para pantallas pequeñas */
@media (max-width: 768px) {
    .form-selector {
        flex-direction: column; /* Cambia la dirección de los botones a columna en pantallas pequeñas */
        align-items: center; /* Centra los botones verticalmente */
    }

    .form-selector button {
        max-width: 100%; /* Permite que los botones ocupen todo el ancho disponible en pantallas pequeñas */
    }
}

/* Sección del formulario */
.b_form-section {
    flex: 1;
    background-color: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    min-width: 450px;
    max-width: 100%;
    box-sizing: border-box;
    overflow: auto;
    display: none; /* Ocultar formularios por defecto */
}

.b_form-section.active {
    display: block; /* Mostrar el formulario activo */
}

/* Títulos dentro de la sección del formulario */
.b_form-section h2 {
    text-align: center;
    font-size: 28px;
    margin-bottom: 30px;
    color: #06adbf;
}

/* Párrafos dentro de la sección del formulario */
.b_form-section p {
    font-size: 16px;
    color: #333;
    margin-bottom: 20px;
    text-align: left;
}

/* Etiquetas dentro del formulario */
.b_form-section label {
    display: block;
    margin-bottom: 12px;
    font-weight: bold;
    font-size: 16px;
}

/* Asteriscos rojos para campos obligatorios */
.required {
    color: red;
    margin-left: 5px;
}

/* Campos de entrada y selección en el formulario */
.b_form-section select,
.b_form-section input,
.b_form-section textarea {
    width: 100%;
    padding: 15px;
    margin-bottom: 25px;
    border-radius: 6px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    font-size: 16px;
}

/* Estilo para casillas de verificación */
.b_form-section input[type="checkbox"] {
    width: auto;
    margin-right: 10px;
}

/* Etiquetas para casillas de verificación */
.b_form-section .b_checkbox-label {
    display: inline-flex; /* Alinear elementos hijos en línea */
    align-items: center; /* Alinear verticalmente el contenido */
    margin-right: 15px;
    font-size: 16px;
    line-height: 1; /* Asegura que el texto y el input tengan la misma altura de línea */
}

/* Ajuste adicional para centrar el input */
.b_checkbox-label input[type="radio"] {
    margin: 0 10px 0 0; /* Añadir margen derecho para separar el input del texto */
    vertical-align: middle; /* Asegura que el input esté alineado verticalmente con el texto */
}

/* Botón de envío en el formulario */
.b_form-section button {
    width: 100%;
    padding: 18px;
    background-color: #035c67;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 18px;
    margin-top: auto;
    transition: background-color 0.3s ease;
}

.b_form-section button:hover {
    background-color: #06adbf;
}

/* Información importante al final del formulario */
.b_important-info {
    font-size: 14px;
    color: #333;
    margin-top: 30px;
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 6px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

/* Estilos para pantallas pequeñas */
@media (max-width: 768px) {
    .b_container {
        flex-direction: column;
    }
    .b_form-section {
        min-width: 100%;
        max-width: 100%;
        overflow: visible;
    }
}
    </style>
</head>
<body>
    <div class="b_container">
<!-- Botones para seleccionar el formulario -->
<div class="form-selector">
    <button data-form="form1" onclick="showForm('form1')" class="selected">Proveedores Médicos</button>
    <button data-form="form2" onclick="showForm('form2')">Proveedores Comerciales</button>
</div>

<!-- Primer formulario -->
<div id="form1" class="b_form-section active">
    <h2>Proveedores Médicos</h2>
    <p>Favor llenar el formulario proporcionando datos completos y correctos:</p>
    <form id="proveedor-form">
        <label for="nombre-proveedor">Nombre Completo Proveedor: <span class="required">*</span></label>
        <input type="text" id="nombre-proveedor" name="nombre-proveedor" required>
        
        <label for="especialidad">Especialidad: <span class="required">*</span></label>
        <input type="text" id="especialidad" name="especialidad" required>
        
        <label for="identidad">DNI: <span class="required">*</span></label>
        <input type="text" id="identidad" name="identidad" required>
        
        <label for="colegiado">Numero Colegiado: <span class="required">*</span></label>
        <input type="text" id="colegiado" name="colegiado" required>
        
        <label for="rtn">RTN: <span class="required">*</span></label>
        <input type="text" id="rtn" name="rtn" required>
        
        <label for="celular">WhatsApp: <span class="required">*</span></label>
        <input type="text" id="celular" name="celular" value="+504 " required>
        
        <label for="correo">Correo Electrónico: <span class="required">*</span></label>
        <input type="email" id="correo" name="correo" required>
        
        <label>Tiene Cuenta en BAC? <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="cuenta-bac" value="si" required> SI
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="cuenta-bac" value="no" required> NO
            </label>
        </div>
        
        <div id="cuenta-si" style="display: none;">
            <label for="cuenta-si">Coloque el numero de cuenta:</label>
            <input type="text" id="cuenta-si-input" name="cuenta-si">
        </div>
        
        <div id="cuenta-no" style="display: none;">
            <label for="cuenta-no">Indique el BANCO y el numero de su cuenta:</label>
            <input type="text" id="cuenta-no-input" name="cuenta-no">
        </div>
        
        <label>Indique el Tipo de Cuenta: <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="tipo-cuenta" value="ahorros" required> Ahorros
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="tipo-cuenta" value="cheques" required> Cheques
            </label>
        </div>
        
        <label>Tiene Constancia de Pagos? <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="constancia-pagos" value="si" required> SI
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="constancia-pagos" value="no" required> NO
            </label>
        </div>
        
        <div id="adjuntar-constancia" style="display: none;">
            <label for="archivo-constancia">Adjunte el documento (Word, PDF, Excel):</label>
            <input type="file" id="archivo-constancia" name="archivo-constancia" accept=".doc, .docx, .pdf, .xls, .xlsx">
        </div>
        
        <label>MEDICASA le ha solicitado esta constancia? <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="solicitud-constancia" value="si" required> SI
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="solicitud-constancia" value="no" required> NO
            </label>
        </div>
        
        <label>MEDICASA tiene YA su constancia de pagos a cuenta vigente? <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="constancia-vigente" value="si" required> SI
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="constancia-vigente" value="no" required> NO
            </label>
        </div>

        <label for="firma_digital_medico">Firma Digital:</label>
        <div id="signature-pad-medico" class="signature-pad">
            <canvas id="firma_pad_medico" class="firma"></canvas>
            <button type="button" class="btn-clear" onclick="clearSignatureMedico()">Limpiar</button>
        </div>
        <input type="hidden" id="firma_digital_medico" name="firma-digital">
        <button type="submit">Enviar</button>
    </form>
    
    <div class="b_important-info">
        <strong style="color: red;">IMPORTANTES:</strong>
        <ol>
            <li><strong style="color: red;">1.</strong> Por seguridad, para acortar tiempos y para evitar espera por gestiones manuales de pago, MEDICASA asegura su pago por transferencia bancaria.</li>
            <li><strong style="color: red;">2.</strong> Apóyese con su secretaria elaborando y dejando los recibos de honorarios médicos según pacientes atendidos.</li>
            <li><strong style="color: red;">3.</strong> Si tiene constancia de pagos a cuenta NO se le hace la retención del 12.5%.</li>
            <li><strong style="color: red;">4.</strong> Favor verificar que los datos que nos proporciono sean los correctos.</li>
            <li><strong style="color: red;">5.</strong> Una vez acreditado se le enviaría notificación a su Correo Electrónico.</li>
            <li><strong style="color: red;">6.</strong> A todo proveedor médico se le retendrá el 2% por gestión administrativa según el monto acumulado a cancelar (trabajo contable no correspondiente a nuestro personal por falta de constancia de pagos a cuenta y posteriormente por emisión de constancia de retención por honorarios dirigida a la SAR).</li>
        </ol>
    </div>
</div>
<!-- logica de campos para formulario 1 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicialmente ocultar ambos campos y títulos
    document.getElementById('cuenta-si').style.display = 'none';
    document.getElementById('cuenta-no').style.display = 'none';
    document.getElementById('adjuntar-constancia').style.display = 'none';

    // Manejar la lógica de mostrar/ocultar campos y títulos según las respuestas
    const cuentaBacInputs = document.querySelectorAll('input[name="cuenta-bac"]');
    cuentaBacInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'si') {
                document.getElementById('cuenta-si').style.display = 'block';
                document.getElementById('cuenta-no').style.display = 'none';
                document.getElementById('cuenta-si-input').disabled = false;
                document.getElementById('cuenta-no-input').disabled = true;
            } else {
                document.getElementById('cuenta-si').style.display = 'none';
                document.getElementById('cuenta-no').style.display = 'block';
                document.getElementById('cuenta-si-input').disabled = true;
                document.getElementById('cuenta-no-input').disabled = false;
            }
        });
    });

    const constanciaPagosInputs = document.querySelectorAll('input[name="constancia-pagos"]');
    constanciaPagosInputs.forEach(input => {
        input.addEventListener('change', function() {
            const adjuntarConstancia = document.getElementById('adjuntar-constancia');
            if (this.value === 'si') {
                adjuntarConstancia.style.display = 'block';
            } else {
                adjuntarConstancia.style.display = 'none';
            }
        });
    });
});
</script>
        
<!-- Segundo formulario -->
<div id="form2" class="b_form-section">
    <h2>Proveedores Comerciales</h2>
    <p>Favor llenar el formulario proporcionando datos completos y correctos:</p>
    <form id="comercial-form">
        <label for="empresa">Nombre Comercial de la Empresa: <span class="required">*</span></label>
        <input type="text" id="empresa" name="empresa" required>
        
        <label for="direccion">Dirección: <span class="required">*</span></label>
        <input type="text" id="direccion" name="direccion" required>
        
        <label for="rtn_comercial">RTN: <span class="required">*</span></label>
        <input type="text" id="rtn_comercial" name="rtn_comercial" required>
        
        <label for="tel_fijo">Teléfono Fijo: <span class="required">*</span></label>
        <input type="text" id="tel_fijo" name="tel_fijo" required>
        
        <label for="correo_comercial">Correo Electrónico: <span class="required">*</span></label>
        <input type="email" id="correo_comercial" name="correo_comercial" required>
        
        <label for="cel_whatsapp">WhatsApp: <span class="required">*</span></label>
        <input type="text" id="cel_whatsapp" name="cel_whatsapp" value="+504 " required>
        
        <label for="nombre_legal">Nombre Representante Legal: <span class="required">*</span></label>
        <input type="text" id="nombre_legal" name="nombre_legal" required>
        
        <label for="dni_comercial">DNI: <span class="required">*</span></label>
        <input type="text" id="dni_comercial" name="dni_comercial" required>
        
        <label for="cel_comercial">Celular: <span class="required">*</span></label>
        <input type="text" id="cel_comercial" name="cel_comercial" value="+504 " required>
        
        <label>Tiene Cuenta en BAC? <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="cuenta_bac_comercial" value="si" required> SI
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="cuenta_bac_comercial" value="no" required> NO
            </label>
        </div>
        <div id="cuenta-bac-si" style="display: none;">
            <label for="cuenta-bac-si">Coloque el número de cuenta:</label>
            <input type="text" id="cuenta-bac-si-input" name="cuenta_bac_si">
        </div>
        <div id="cuenta-bac-no" style="display: none;">
            <label for="cuenta-bac-no">Indique el BANCO y el número de su cuenta:</label>
            <input type="text" id="cuenta-bac-no-input" name="cuenta_bac_no">
        </div>
        
        <label>Indique el Tipo de Cuenta: <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="tipo_cuenta_comercial" value="ahorro" required> Ahorro
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="tipo_cuenta_comercial" value="cheques" required> Cheques
            </label>
        </div>
        
        <label>Tiene Constancia de Pagos a Cuenta Vigente? <span class="required">*</span></label>
        <div>
            <label class="b_checkbox-label">
                <input type="radio" name="constancia_archivo_comercial" value="si" required> SI
            </label>
            <label class="b_checkbox-label">
                <input type="radio" name="constancia_archivo_comercial" value="no" required> NO
            </label>
        </div>
        <div id="adjuntar-constancia-comercial" style="display: none;">
            <label for="archivo-constancia-comercial">Adjunte el documento (Word, PDF, Excel):</label>
            <input type="file" id="archivo-constancia-comercial" name="archivo-constancia-comercial" accept=".doc, .docx, .pdf, .xls, .xlsx">
        </div>
        
        <label>Referencia Comercial 1: </label>
        <input type="text" name="1_refbac_comercial" placeholder="Nombre">
        <input type="text" name="1_refbac_comercial_tel" placeholder="Teléfono">
        
        <label>Referencia Comercial 2: </label>
        <input type="text" name="2_refbac_comercial" placeholder="Nombre">
        <input type="text" name="2_refbac_comercial_tel" placeholder="Teléfono">
        
        <label>Referencia Bancaria: </label>
        <input type="text" name="1_refbac_contacto" placeholder="Nombre">
        <input type="text" name="1_refbac_contacto_tel" placeholder="Teléfono">
        
        <label for="nom_contacto">Nombre del Contacto: <span class="required">*</span></label>
        <input type="text" id="nom_contacto" name="nom_contacto" required>

        <label for="firma_digital_comercial">Firma Digital:</label>
            <div id="signature-pad" class="signature-pad">
                <canvas id="firma_pad" class="firma"></canvas>
                <button type="button" class="btn-clear" onclick="clearSignature()">Limpiar</button>
            </div>
        <input type="hidden" id="firma_digital_comercial" name="firma_digital_comercial">
        <!-- Libreria para Firma DIgital -->
        <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

        <button type="submit">Enviar</button>

        <div class="b_important-info">
        <strong style="color: red;">IMPORTANTES:</strong>
        <ol>
            <li><strong style="color: red;">1.</strong> Por seguridad, para acortar tiempos y para evitar espera por gestiones manuales de pago, MEDICASA asegura su pago por transferencia bancaria.</li>
            <li><strong style="color: red;">3.</strong> Si tiene constancia de pagos a cuenta NO se le hace la retención del 12.5%.</li>
            <li><strong style="color: red;">4.</strong> Favor verificar que los datos que nos proporciono sean los correctos.</li>
            <li><strong style="color: red;">5.</strong> Una vez acreditado se le enviaría notificación a su Correo Electrónico.</li>
        </ol>
    </div>
    </form>
</div>
<!-- logica de campos para formulario 2 -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Inicialmente ocultar los campos específicos del segundo formulario
    document.getElementById('cuenta-bac-si').style.display = 'none';
    document.getElementById('cuenta-bac-no').style.display = 'none';
    document.getElementById('adjuntar-constancia-comercial').style.display = 'none';

    // Lógica para mostrar/ocultar campos según las respuestas
    const cuentaBacInputsComercial = document.querySelectorAll('input[name="cuenta_bac_comercial"]');
    cuentaBacInputsComercial.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'si') {
                document.getElementById('cuenta-bac-si').style.display = 'block';
                document.getElementById('cuenta-bac-no').style.display = 'none';
            } else {
                document.getElementById('cuenta-bac-si').style.display = 'none';
                document.getElementById('cuenta-bac-no').style.display = 'block';
            }
        });
    });

    const constanciaPagosInputsComercial = document.querySelectorAll('input[name="constancia_archivo_comercial"]');
    constanciaPagosInputsComercial.forEach(input => {
        input.addEventListener('change', function() {
            const adjuntarConstanciaComercial = document.getElementById('adjuntar-constancia-comercial');
            if (this.value === 'si') {
                adjuntarConstanciaComercial.style.display = 'block';
            } else {
                adjuntarConstanciaComercial.style.display = 'none';
            }
        });
    });
});
    </script>
    <!-- Script para el funcionamiento de la firma digital -->
    <script src="../../backend/registros/script/firma_digital.js"></script>
    <!-- Script para el registro del formulario 1 -->
    <script src="../../backend/registros/script/reg_directorio.js"></script>

    <!-- Script para cambiar de formulario -->
    <script src="../../backend/registros/script/cambiar_form_prov_comerc.js"></script>
</body>
</html>

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

</body>
</html>