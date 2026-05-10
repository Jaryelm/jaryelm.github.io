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

<button class="button" onclick="cambiarColor(this, '../recursos/enfermera_nuevo.php')">Registrar Personal de Enfermeria</button>
<button class="button" onclick="cambiarColor(this, '../recursos/enfermera.php')">Personal de Enfermeria</button>
<button class="button" onclick="cambiarColor(this, '#')">Registrar Personal Administrativo</button>
<button class="button" onclick="cambiarColor(this, '#')">Personal Administrativo</button>
<button class="button" onclick="cambiarColor(this, '#')">Registrar Personal de Mantenimiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Personal de Mantenimiento</button>
<button class="button" onclick="cambiarColor(this, '../medicos/nuevo.php')">Registrar Personal Médico</button>
<button class="button" onclick="cambiarColor(this, '../medicos/mostrar.php')">Personal Médico</button>
<button class="button" onclick="cambiarColor(this, '../recursos/reclutamiento.php')">Reclutamiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Proceso de Entrevista</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratorios_nuevo.php')">Registro de Laboratirio</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratiorios.php')">Laboratorios</button>
           
           <!-- multistep form -->

<?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT * FROM nurse  WHERE idnur= '$id';");
 $sentencia->execute();

$data =  array();
if($sentencia){
  while($r = $sentencia->fetchObject()){
    $data[] = $r;
  }
}
   ?>
   <?php if(count($data)>0):?>
        <?php foreach($data as $d):?>

<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Actualizar Enfermera/o</h1>
   
   
    <hr>
    <input type="hidden" name="nuridp" value="<?php echo $d->idnur; ?>">
    <label for="email"><b>N° de identificación de la  enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: 09741478" value="<?php echo $d->numide; ?>"  name="nuriden" maxlength="14" required>

    <label for="psw"><b>Nombre de la enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Juan Raul" value="<?php echo $d->nomnur; ?>"  name="nurnam" required>

    <label for="psw"><b>Apellido de la enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Ramirez Requena"  value="<?php echo $d->apenur; ?>"  name="nurape" required>

    <label for="psw"><b>Fecha de nacimiento de la enfermera/o</b></label><span class="badge-warning">*</span>
    <input type="date" name="nurdat" value="<?php echo $d->nacinur; ?>"  required>

    <label for="psw"><b>Género de la enfermera/o</b></label><span class="badge-warning">*</span>
    <select required name="nurge" id="gep">
        <option><?php echo $d->sexnur; ?></option>
        <option>-------------------------</option>
        <option value="Masculino">Masculino</option>
        <option value="Femenino">Femenino</option>

    </select>

    <hr>
   <button type="submit" name="upd_nurse" class="registerbtn">Guardar</button>
  </div>
  
</form>

<?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    <script src="../../backend/js/jquery.min.js"></script>


    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="../../backend/js/vpat.js"></script>
   
   <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <?php include_once '../../backend/php/upd_nurse.php' ?>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
   
</body>
</html>


