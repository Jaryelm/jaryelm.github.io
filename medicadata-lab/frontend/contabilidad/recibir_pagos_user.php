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
include_once '../contabilidad/menu.php';
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
include_once '../contabilidad/perfil.php';
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
        .form-container label {
            display: block;
            margin-bottom: 5px; /* Espaciado uniforme entre label e input */
            font-weight: bold;
        }
        .form-container input, .form-container select, .form-container textarea {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #fff; /* Fondo blanco */
            box-sizing: border-box; /* Garantiza que padding no afecte la alineación */
            margin-bottom: 0; /* Asegura que no haya espacio adicional debajo de los campos */
        }
        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container input[type="date"],
        .form-container textarea,
        .form-container select {
            height: 40px; /* Fija una altura uniforme */
        }
        .form-container textarea {
            height: auto; /* Permite que textarea se ajuste al contenido */
        }
        .form-container button {
            grid-column: span 4; /* Botón abarca toda la fila */
            padding: 14px;
            background-color: #035c67;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 18px;
        }
        .form-container button:hover {
            background-color: #06adbf;
        }
        .form-item {
            display: flex;
            flex-direction: column;
            gap: 5px; /* Asegura una separación uniforme entre el label y el input */
        }
        /* Estilos Responsivos */
        @media (max-width: 768px) {
            .form-container {
                grid-template-columns: 1fr; /* En pantallas pequeñas, se muestra una columna */
            }
            .form-container h2, .form-container button {
                grid-column: span 1; /* Abarca toda la fila en pantallas pequeñas */
            }
        }
    </style>
</head>
<body>

<div class="form-container">
    <h2>Recibir Pagos</h2>
    
    <div class="form-item">
        <label for="d_cuenta">Deposito a Cuenta:</label>
        <input type="text" id="d_cuenta" name="d_cuenta">
    </div>
    
    <div class="form-item">
        <label for="Balance">Balance:</label>
        <input type="text" id="Balance" name="Balance">
    </div>
    
    <div class="form-item">
        <label for="GF_Depositados">Grupo Fondos No Depositados:</label>
        <input type="text" id="GF_Depositados" name="GF_Depositados">
    </div>

    <div class="form-item">
        <label for="fecha">Fecha:</label>
        <input type="date" id="fecha" name="fecha" required>
    </div>
    
    <div class="form-item">
        <label for="cantidad">ID:</label>
        <input type="text" id="cantidad" name="cantidad" step="0.01" required>
    </div>

    <div class="form-item">
        <label for="Pago">Pago:</label>
        <input type="text" id="Pago" name="Pago">
    </div>

    <div class="form-item">
        <label for="Monto_Recibido">Monto Recibido:</label>
        <input type="text" id="Monto_Recibido" name="Monto_Recibido" step="0.01">
    </div>
    
    <div class="form-item">
        <label for="Metodo_Pago">Metodo de Pago:</label>
        <input type="text" id="Metodo_Pago" name="Metodo_Pago" step="0.01">
    </div>
    
    <div class="form-item">
        <label for="Detalles">Detalles:</label>
        <input type="text" id="Detalles" name="Detalles">
    </div>
    
    <div class="form-item">
        <label for="Concepto">Concepto:</label>
        <textarea id="text" name="Concepto" rows="1"></textarea>
    </div>
    
    <div class="form-item">
        <label for="asignar_monto">Asignar Monto:</label>
        <input type="text" id="asignar_monto" name="asignar_monto" step="0.01">
    </div>
    
    <div class="form-item">
        <label for="Monto">Monto:</label>
        <input type="text" id="Monto" name="Monto" step="0.01">
    </div>

    <div class="form-item">
        <label for="Proyecto">Proyecto:</label>
        <input type="text" id="Proyecto" name="Proyecto" step="0.01">
    </div>
    
    <div class="form-item">
        <label for="Impuesto">Impuesto:</label>
        <input type="text" id="Impuesto" name="Impuesto" step="0.01">
    </div>
    
    <button type="submit">Recibir Pagos</button>
</div>

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



    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

</body>
</html>