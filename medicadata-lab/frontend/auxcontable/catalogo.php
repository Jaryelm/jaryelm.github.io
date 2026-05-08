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

        <button class="button" onclick="cambiarColor(this, 'catalogo.php')">Catálogo de Cuentas</button>
        <button class="button" onclick="cambiarColor(this, 'diariogeneral.php')">Diario General</button>
        <button class="button" onclick="cambiarColor(this, 'partida_manual.php')">Partida Manual</button>
        <button class="button" onclick="cambiarColor(this, 'transacciones.php')">Transacciones Capturadas</button>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <style>
        /* Asteriscos rojos para campos obligatorios */
        .required {
            color: red;
            margin-left: 5px;
        }
        /* Estilo para campos con errores */
        input.error {
            border-color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sección de Registro de Catálogo -->
        <div class="form-section">
            <h2>Nueva Cuenta</h2>
            <form id="registro-form">
                <label for="tipo-cuenta">Tipo Cuenta: <span class="required">*</span></label>
                <select id="tipo-cuenta" name="tipo-cuenta" required>
                    <option value="" disabled selected>Seleccione Cuenta</option>
                    <option value="activos">Activos</option>
                    <option value="pasivos">Pasivos</option>
                    <option value="patrimonio">Patrimonio</option>
                    <option value="ingresos">Ingresos</option>
                    <option value="costos">Costos</option>
                    <option value="gastos">Gastos</option>
                    <option value="otros ingresos">Otros Ingresos</option>
                    <option value="otros gastos no deducibles">Otros Gastos Deducibles</option>
                </select>
                <label for="cuenta">Cuenta: <span class="required">*</span></label>
                <input type="text" id="cuenta" name="cuenta" required>
                <label for="nombre">Nombre: <span class="required">*</span></label>
                <input type="text" id="nombre" name="nombre" required>
                <button type="submit">Registrar</button>
            </form>
        </div>
    <!-- Sección de Actualización -->
        <div class="form-section">
    <h2>Actualizar Nombre de Cuenta</h2>
    <form id="actualizar-form">
        <label for="cuenta-actualizar">Cuenta a Actualizar: <span class="required">*</span></label>
        <input type="text" id="cuenta-actualizar" name="cuenta-actualizar" required placeholder="Ingrese el número de cuenta">
        
        <div style="background-color: #e3f2fd; border: 1px solid #2196f3; border-radius: 5px; padding: 10px; margin: 10px 0;">
            <strong>Nota:</strong> Solo se actualizará el nombre del servicio. La categoría se mantendrá sin cambios.
        </div>
        
        <label for="tipo-cuenta-actualizar">Nuevo Tipo de Cuenta: <span class="required">*</span></label>
        <select id="tipo-cuenta-actualizar" name="tipo-cuenta-actualizar" required>
            <option value="" disabled selected>Seleccione Cuenta</option>
            <option value="activos">Activos</option>
            <option value="pasivos">Pasivos</option>
            <option value="patrimonio">Patrimonio</option>
            <option value="ingresos">Ingresos</option>
            <option value="costos">Costos</option>
            <option value="gastos">Gastos</option>
            <option value="otros ingresos">Otros Ingresos</option>
            <option value="otros gastos no deducibles">Otros Gastos Deducibles</option>
        </select>

        <label for="nuevo-nombre">Nuevo Nombre del Servicio: <span class="required">*</span></label>
        <input type="text" id="nuevo-nombre" name="nuevo-nombre" required placeholder="Ingrese el nuevo nombre del servicio">

        <button type="submit">Actualizar Nombre</button>
    </form>
</div>
        <!-- Sección de Búsqueda -->
        <div class="search-section">
            <h2>Buscar Cuentas Registradas</h2>
            <form id="search-form">
                <label for="search">Buscar:</label>
                <input type="text" id="search" name="search" placeholder="Ingrese el nombre o número de cuenta">
                <button type="submit">Buscar</button>
            </form>
                <!-- Conteo de registros -->
                <div id="count-display">Total de registros: 0</div>

            <div class="table-container">
                <table id="results-table" class="styled-table">
                    <thead>
                        <tr>
                            <th>Tipo de Cuenta</th>
                            <th>Cuenta</th>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Las filas se llenarán dinámicamente aquí -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>
        <!-- Sección de Eliminación -->
<div class="container">

        <div class="form-section">
            <h2>Eliminar Cuenta</h2>
            <form id="eliminar-form">
                <label for="cuenta-eliminar">Cuenta a Eliminar: <span class="required">*</span></label>
                <input type="text" id="cuenta-eliminar" name="cuenta-eliminar" required placeholder="Ingrese el número de cuenta">
                <button type="submit">Eliminar</button>
            </form>
        </div>
</div>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
    <!-- Script para manejar la busqueda de cuentas -->
    <script src="../../backend/registros/script/busqueda_catalogo.js"></script>
    <!-- Script para manejar la eliminación de cuentas -->
    <script src="../../backend/registros/script/eliminar_catalogo.js"></script>
    <!-- Script para manejar la registro de cuentas -->
    <script src="../../backend/registros/script/reg_catalogo.js"></script>
    <!-- Script para manejar la actualización de cuentas -->
    <script src="../../backend/registros/script/actualizar_catalogo.js"></script>
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

</body>
</html>