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
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/6.6.9/sweetalert2.min.css">



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

        <button class="button" onclick="cambiarColor(this, 'enfermera_nuevo.php')">Registrar Enfermería</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera.php')">Enfermería</button>

           
           <!-- multistep form -->


<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Nueva Enfermero/a</h1>
   
    <div class="alert-danger">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
</div>
    <hr>

    <label for="email"><b>N° de identificación de la  enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: 09741478" name="nuriden" maxlength="14" required>

    <label for="psw"><b>Nombre de la enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Juan Raul" name="nurnam" required>

    <label for="psw"><b>Apellido de la enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Ramirez Requena" name="nurape" required>

    <label for="psw"><b>Fecha de nacimiento de la enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="date" name="nurdat" required>

    <label for="psw"><b>Género de la enfermera/o</b></label><span class="badge-warning">*</span>
    <select class="select2" required name="nurge" id="gep">
        <option>Seleccione</option>
        <option value="Masculino">Masculino</option>
        <option value="Femenino">Femenino</option>
    </select>

    <hr>
   
    <button type="submit" name="add_nurse" class="registerbtn">Guardar</button>
  </div>
  
</form>

        </main>
        <!-- MAIN -->
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_foot.php'; ?>

    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="../../backend/js/vpat.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
     <?php include_once '../../backend/php/add_nurse.php' ?>


    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
   
</body>
</html>


