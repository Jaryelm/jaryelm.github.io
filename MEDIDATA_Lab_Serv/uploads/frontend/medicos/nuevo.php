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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">

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

        <button class="button" onclick="cambiarColor(this, '../medicos/nuevo.php')">Registrar Médicos</button>
        <button class="button" onclick="cambiarColor(this, '../medicos/mostrar.php')">Médicos</button>
<button class="button" onclick="cambiarColor(this, '../recursos/reclutamiento.php')">Reclutamiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Proceso de Entrevista</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratorios_nuevo.php')">Registrar Área de Servicio</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratiorios.php')">Äreas de Servicios</button>
           
           <!-- multistep form -->


<form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validacion()">
    <div class="containerss">
        <h1>Nuevo médico</h1>
        <div class="alert-danger">
            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
            <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
        </div>
        <hr>

        <label for="especialidad"><b>Especialidad del médico</b></label><span class="badge-warning">*</span>
        <input type="text" id="especialidad" name="espm" required placeholder="Ingrese la especialidad">

        <label for="dni"><b>DNI del médico</b></label><span class="badge-warning">*</span>
        <input type="text" placeholder="ejm: 13 Dígitos" name="cem" maxlength="13" required>

        <label for="nombre"><b>Nombre del médico</b></label><span class="badge-warning">*</span>
        <input type="text" placeholder="ejm: Juan Raul" name="named" required>

        <label for="apellido"><b>Apellido del médico</b></label><span class="badge-warning">*</span>
        <input type="text" placeholder="ejm: Ramirez Requena" name="apeme" required>

        <label for="direccion"><b>Dirección del médico</b></label><span class="badge-warning">*</span>
        <input type="text" placeholder="ejm: Comayagüela, Tegucigalpa Honduras" name="dime" required>

        <label for="correo"><b>Correo electrónico del médico</b></label><span class="badge-warning">*</span>
        <input type="email" placeholder="ejm: moises.castillo@medicasa.hn" name="corr" required>

        <label for="genero"><b>Género del médico</b></label><span class="badge-warning">*</span>
        <select required name="geme" id="gep">
            <option>Seleccione</option>
            <option value="Masculino">Masculino</option>
            <option value="Femenino">Femenino</option>
        </select>

        <label for="telefono"><b>Teléfono del médico</b></label><span class="badge-warning">*</span>
        <input type="text" maxlength="13" placeholder="ejm: +51 999 888 111" name="telme" required>

        <label for="nacimiento"><b>Fecha de nacimiento del médico</b></label><span class="badge-warning">*</span>
        <input type="date" name="cumme" required>

        <!-- Comisiona -->
        <label><b>Comisiona</b></label><span class="badge-warning">*</span>
        <div style="margin-bottom: 15px;">
            <label style="margin-right: 15px;">
                <input type="radio" name="comisiona" value="SI" required> Sí
            </label>
            <label>
                <input type="radio" name="comisiona" value="NO" required> No
            </label>
        </div>

        <hr>
        <button type="submit" name="add_doctor" class="registerbtn">Guardar</button>
    </div>
</form>

        </main>
        <!-- MAIN -->
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <?php include_once '../../backend/php/add_doctor.php' ?>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2();
        });
    </script>
   
</body>
</html>


