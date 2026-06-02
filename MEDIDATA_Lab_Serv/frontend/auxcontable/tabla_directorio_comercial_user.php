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

<h1 class="title"><?php echo $saludo . ', <strong>' . $_SESSION['name'] . '</strong>'; ?></h1>

<button class="button" onclick="cambiarColor(this, 'formulario_directorio_user.php')">Registrar Proveedores</button>
<button class="button" onclick="cambiarColor(this, 'tabla_directorio_user.php')">Directorio Médico</button>
<button class="button" onclick="cambiarColor(this, 'tabla_directorio_comercial_user.php')">Directorio Comercial</button>

<!-- Formularios -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="ruta/a/tu/estilo.css">
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
</head>
<body>
    <!-- Título centrado -->
    <div class="table-title">
        <h1>Directorio Proveedores Comerciales</h1>
    </div>
    <!-- Contenedor para buscar y paginar -->
    <div class="controls">
        <input type="text" id="search-input" placeholder="Buscar...">
        <button id="search-button">Buscar</button>
        <div id="pagination"></div>
    </div>
    <!-- Tabla para mostrar los datos -->
    <div class="table-container">
    <table id="directorio-comercial-table" border="1">
    <thead>
    <tr>
        <th>Nombre Empresa</th>
        <th>Dirección</th>
        <th>RTN Comercial</th>
        <th>Teléfono Fijo</th>
        <th>Correo Comercial</th>
        <th>WhatsApp</th>
        <th>Nombre Legal</th>
        <th>DNI Comercial</th>
        <th>Celular</th>
        <th>Cuenta BAC</th>
        <th>Cuenta BAC Si</th>
        <th>Cuenta BAC No</th>
        <th>Tipo de Cuenta</th>
        <th>Nombre Contacto</th>
        <th>1ª Ref BAC</th>
        <th>Tel 1ª Ref BAC</th>
        <th>2ª Ref BAC</th>
        <th>Tel 2ª Ref BAC</th>
        <th>1ª Ref Contacto</th>
        <th>Tel 1ª Ref Contacto</th>
        <th>Firma Digital</th>
        <th>Archivo Constancia</th>
        <th>Acciones</th>
    </tr>
    </thead>
        <tbody>
            <!-- Las filas se llenarán dinámicamente aquí -->
        </tbody>
    </table>
</div>
    <!-- Script para visualizar datos del formulario en la tabla -->
    <script src="../../backend/registros/script/tabla_directorio_comercial.js"></script>
    <!-- Script para generar PDF -->
    <script src="../../backend/registros/script/directorio_pdf_comercial.js"></script>
    <!-- Script para el registro del formulario -->
    <script src="../../backend/registros/script/tabla_directorio.js"></script>
    <!-- Script para generar PDF -->
    <script src="../../backend/registros/script/directorio_pdf.js"></script>
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