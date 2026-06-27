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

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">




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
    $saludo = "Buenos DÃ­as";
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = "Buenas Tardes";
} else {
    $saludo = "Buenas Noches";
}
?>

<h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

<button class="button" onclick="cambiarColor(this, '../recursos/../recursos_humanos/agregar_colaborador.php')">Registrar Personal de Enfermeria</button>
<button class="button" onclick="cambiarColor(this, '../recursos/enfermera.php')">Personal de Enfermeria</button>
<button class="button" onclick="cambiarColor(this, '#')">Registrar Personal Administrativo</button>
<button class="button" onclick="cambiarColor(this, '#')">Personal Administrativo</button>
<button class="button" onclick="cambiarColor(this, '#')">Registrar Personal de Mantenimiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Personal de Mantenimiento</button>
<button class="button" onclick="cambiarColor(this, '../recursos_humanos/agregar_colaborador.php')">Registrar Personal MÃ©dico</button>
<button class="button" onclick="cambiarColor(this, '../medicos/mostrar.php')">Personal MÃ©dico</button>
<button class="button" onclick="cambiarColor(this, '../recursos/reclutamiento.php')">Reclutamiento</button>
<button class="button" onclick="cambiarColor(this, '#')">Proceso de Entrevista</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratorios_nuevo.php')">Registro de Laboratirio</button>
<button class="button" onclick="cambiarColor(this, '../recursos/laboratiorios.php')">Laboratorios</button>
 
 <?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT * FROM laboratory  WHERE idlab= '$id';");
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
    <h1>Actualizar Laboratorio</h1>
   
   
    <hr>
<br>
    <label for="email"><b>Nombre del laboratorio</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="ejm: Radiografia" name="labname" value="<?php echo $d->nomlab; ?>" required>
    <input type="hidden" name="labid" value="<?php echo $d->idlab; ?>">
    <hr>
   
   <button type="submit" name="upd_laboratory" class="registerbtn">Guardar</button>

  </div>
  
</form>

<?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

     <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <?php include_once '../../backend/php/upd_laboratory.php' ?>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
    
    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
   

</body>
</html>


