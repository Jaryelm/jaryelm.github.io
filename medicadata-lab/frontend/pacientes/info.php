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

            <button class="button" onclick="location.href='mostrar.php'">Lista de Pacientes</button>
            <button class="button" onclick="location.href='pagos.php'">Pagos</button>
            <button class="button" onclick="location.href='historial.php'">Historial de los Pacientes</button>
            <button class="button" onclick="location.href='documentos.php'">Documentos</button>
           
           <!-- multistep form -->
<?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT * FROM patients  WHERE idpa= '$id';");
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
    <h1>Información del paciente</h1>

    <hr>

    <label for="email"><b>DNI del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled placeholder="ejm: ASCS855CS74" value="<?php echo $d->numhs; ?>" name="nhi" maxlength="14" required>

    <label for="psw"><b>Nombre del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled placeholder="ejm: Juan Raul" value="<?php echo $d->nompa; ?>" name="namp" required>

    <label for="psw"><b>Apellido del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled placeholder="ejm: Ramirez Requena" value="<?php echo $d->apepa; ?>" name="apep" required>

    <label for="psw"><b>Dirección del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled placeholder="ejm: calle los medanos" value="<?php echo $d->direc; ?>" name="dip" required>

    <label for="psw"><b>Género del paciente</b></label><span class="badge-warning">*</span>
    <select required name="gep" id="gep" disabled>
        <option><?php echo $d->sex; ?></option>
        
    </select>

    <label for="psw"><b>Grupo sanguíneo del paciente</b></label><span class="badge-warning">*</span>
    <select required name="grp" id="grp" disabled>
        <option><?php echo $d->grup; ?></option>
       
    </select>

    <label for="psw"><b>Teléfono del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled maxlength="13" value="<?php echo $d->phon; ?>"  placeholder="ejm: +51 999 888 111" name="telp" required>

    <label for="psw"><b>Fecha de nacimiento del paciente</b></label><span class="badge-warning">*</span>
    <input type="date" disabled  value="<?php echo $d->cump; ?>" name="cump" required>

    <label for="psw"><b>Correo del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled value="<?php echo $d->corr; ?>" name="corr" required>

    <label for="psw"><b>Usuario del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" disabled value="<?php echo $d->username; ?>" name="username" required>

    <hr>
   
   
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
    <script src="../../backend/js/script.js"></script>
    
    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
   
</body>
</html>


