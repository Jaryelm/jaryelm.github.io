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





    <title>MEDIDATA</title>
</head>
<body>

<?php
include_once '../admin/menu.php';
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

<h1 class="title"><?php echo $saludo . ', <strong>' . $_SESSION['name'] . '</strong>'; ?></h1>


            <button class="button" onclick="cambiarColor(this, 'emitir_cheque.php')">Emitir Cheque</button>
            <button class="button" onclick="cambiarColor(this, 'tabla_cheque.php')">Cheques Registrados</button>
            <button class="button" onclick="cambiarColor(this, 'conciliacion_bancaria.php')">Conciliación Bancaria</button>
            <button class="button" onclick="cambiarColor(this, 'recibir_pagos.php')">Recibir Pagos</button>
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
    <h2>Conciliación Bancaria</h2>
    
    <div class="form-item">
        <label for="cuenta">Cuenta:</label>
        <input type="text" id="cuenta" name="cuenta">
    </div>
    
    <div class="form-item">
        <label for="balance">Saldo Fecha Corte:</label>
        <input type="date" id="balance" name="balance">
    </div>

    <div class="form-item">
        <label for="impuestos">Saldo Conciliado:</label>
        <input type="text" id="impuestos" name="impuestos">
    </div>
    
    <div class="form-item">
        <label for="impuestos">Fuera de balance:</label>
        <input type="text" id="impuestos" name="impuestos">
    </div>
    
    <div class="form-item">
        <label for="tarjeta">U. Fecha Con:</label>
        <input type="date" id="tarjeta" name="tarjeta">
    </div>
    
    <div class="form-item">
        <label for="cheque">Fecha de Corte:</label>
        <input type="date" id="cheque" name="cheque">
    </div>

    <div class="form-item">
        <label for="campox">X:</label>
        <input type="text" id="campox" name="campox">
    </div>
    
    <div class="form-item">
        <label for="pagar">Marque total las Transacciones:</label>
        <input type="text" id="pagar" name="pagar">
    </div>

    <div class="form-item">
        <label for="fecha">Depositos:</label>
        <input type="text" id="fecha" name="fecha" required>
    </div>
    
    <div class="form-item">
        <label for="cantidad">Retiros:</label>
        <input type="text" id="cantidad" name="cantidad" step="0.01" required>
    </div>
    
    <div class="form-item">
        <label for="total_aclarado">Total Aclarado:</label>
        <input type="text" id="total_aclarado" name="total_aclarado" step="0.01">
    </div>
    
    <button type="submit">Conciliar</button>
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



    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html>