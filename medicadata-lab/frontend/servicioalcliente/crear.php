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
    <h1>Crear perfil del paciente</h1>
    <?php include_once '../../backend/php/add_profile.php' ?>

    <br>
    <hr>

    <label for="email"><b>Nombre y apellidos del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" value="<?php echo $d->nompa; ?> &nbsp; <?php echo $d->apepa; ?>" placeholder="ejm: admin@gmail.com" disabled>

    <label for="email"><b>Correo electrónico del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: admin@gmail.com" name="cop"  required>
    <input type="hidden" name="pid" value="<?php echo $d->idpa; ?>">

    <label for="psw"><b>Usuario del paciente</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: juanjo" name="usp" required>

    <label for="psw"><b>Contraseña del paciente</b></label><span class="badge-warning">*</span>
    <input type="password" placeholder="*******" name="pwdp" required>

    <label for="psw"><b>Rol del paciente</b></label><span class="badge-warning">*</span>
    <select required name="rlp">
        <option value="2">Paciente</option>

    </select>

    <hr>
   
    <button type="submit" name="add_profile" class="registerbtn">Guardar</button>
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

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

   
</body>
</html>


