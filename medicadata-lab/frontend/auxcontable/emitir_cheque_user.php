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

    <script src="/backend/vendor/apexcharts/apexcharts.min.js"></script>

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

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>


            <button class="button" onclick="cambiarColor(this, 'emitir_cheque_user.php')">Emitir Cheque</button>
            <button class="button" onclick="cambiarColor(this, 'tabla_cheque_user.php')">Cheques Registrados</button>
            <button class="button" onclick="cambiarColor(this, 'conciliacion_bancaria_user.php')">Conciliación Bancaria</button>
            <button class="button" onclick="cambiarColor(this, 'recibir_pagos_user.php')">Recibir Pagos</button>
            <button class="button" onclick="cambiarColor(this, '#')">Deposito Bancario</button>

<!-- Formularios -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emitir Cheques</title>
    <!-- Include Notificaciones -->
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <style>

        .form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 1500px;
            margin: 40px auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-container h2 {
            margin: 0;
            padding: 0;
            text-align: center;
            font-size: 28px;
            color: #06adbf;
            font-weight: bold;
        }

        .form-item {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-item label {
            font-weight: bold;
            color: #333;
        }

        .form-item input, .form-item select, .form-item textarea {
            width: 100%;
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #ddd;
            background-color: #fafafa;
            box-sizing: border-box;
        }

        .form-item input[type="number"], .form-item input[type="text"], .form-item select, .form-item textarea {
            font-size: 16px;
        }

        .form-item textarea {
            resize: vertical;
        }

        .form-container button {
            padding: 14px;
            background-color: #035c67;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .form-container button:hover {
            background-color: #06adbf;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 20px;
            }
        }
    </style>

</head>
<body>

<div class="form-container">
    <h2>Emitir Cheques</h2>
    <div class="form-item">
        <label for="cuenta">Cuenta: <span class="required">*</span></label>
        <select id="cuenta" name="cuenta" class="select2" required>
            <option value="">Seleccione una cuenta</option>
            <!-- Las opciones de las cuentas se generarán desde PHP -->
        </select>
    </div>
    <div class="form-item">
        <label for="asignar_monto">Cuenta Asignar Monto: <span class="required">*</span></label>
        <select id="asignar_monto" name="asignar_monto" class="select2" required>
            <option value="">Seleccione una cuenta</option>
            <!-- Las opciones de las cuentas se generarán desde PHP -->
        </select>
    </div>
    <div class="form-item">
        <label for="balance">Balance: <span class="required">*</span></label>
        <input type="number" id="balance" name="balance" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="impuestos">Incluir impuestos:</label>
        <input type="number" id="impuestos" name="impuestos" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="proveedor_RTN">Proveedor RTN: <span class="required">*</span></label>
        <input type="text" id="proveedor_RTN" name="proveedor_RTN" required>
    </div>
    <div class="form-item">
        <label for="cheque">Cheque No: <span class="required">*</span></label>
        <input type="text" id="cheque" name="cheque" required>
    </div>
    <div class="form-item">
        <label for="pagar">Pagar a: <span class="required">*</span></label>
        <input type="text" id="pagar" name="pagar" required>
    </div>
    <div class="form-item">
        <label for="fecha">Fecha: <span class="required">*</span></label>
        <input type="date" id="fecha" name="fecha" required readonly>
    </div>
    <div class="form-item">
        <label for="cantidad">Cantidad: <span class="required">*</span></label>
        <input type="number" id="cantidad" name="cantidad" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="concepto">Concepto: <span class="required">*</span></label>
        <textarea id="concepto" name="concepto" rows="3" required></textarea>
    </div>
    <div class="form-item">
        <label for="monto">Monto: <span class="required">*</span></label>
        <input type="number" id="monto" name="monto" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="proyecto">Proyecto: <span class="required">*</span></label>
        <input type="text" id="proyecto" name="proyecto" required>
    </div>
    <div class="form-item">
        <label for="imp_ventas">Imp/Ventas:</label>
        <input type="number" id="imp_ventas" name="imp_ventas" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="total_asignado">Total Asignado: <span class="required">*</span></label>
        <input type="number" id="total_asignado" name="total_asignado" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="impuesto">Impuesto: <span class="required">*</span></label>
        <input type="number" id="impuesto" name="impuesto" step="0.01" required>
    </div>
    <div class="form-item">
        <label for="fuera_balance">Fuera del Balance:</label>
        <input type="number" id="fuera_balance" name="fuera_balance" step="0.01" readonly>
    </div>
    <div class="form-item">
        <label for="total_pagado">Total Pagado: <span class="required">*</span></label>
        <input type="number" id="total_pagado" name="total_pagado" step="0.01" required>
    </div>
    <button id="emitir-cheque-btn">Emitir Cheque</button>
</div>

<!-- obtener fuera de balance -->
<script src="../../backend/registros/script/fuera_balance.js"></script>
<!-- obtener fecha automatica -->
<script src="../../backend/registros/script/fecha_auto.js"></script>
<!-- obtener y enviar al backend para registrar en el php -->
<script src="../../backend/registros/script/emitir_cheque.js"></script>
<!-- obtener catalogo en la lista desplegable -->
<script src="../../backend/registros/script/lista_catalogo.js"></script>
<!-- obtener notificaciones 
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>-->

<!-- Include jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Include Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
</script>

</body>
</html>

        </main>
        <!-- MAIN -->
    </section>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html>