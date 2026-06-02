<?php
    ob_start();
     session_start();
    
    if(!isset($_SESSION['rol']) || $_SESSION['rol'] != 1){
    header('location: ../login.php');

    $id=$_SESSION['id'];
  }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/ico.svg">
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
                    <input type="text" placeholder="Search...">
                    <i class='bx bx-search icon' ></i>
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
            <h1 class="title">Bienvenido <?php echo '<strong>'.$_SESSION['username'].'</strong>'; ?></h1>
            <ul class="breadcrumbs">
                <li><a href="../admin/escritorio.php">Home</a></li>
                <li class="divider">></li>
                <li><a href="../citas/mostrar.php">Listado de las citas</a></li>
                <li class="divider">></li>
                <li><a href="#" class="active">Pago de la cita</a></li>
            </ul>
           
           <!-- multistep form -->
<?php 
require '../../backend/bd/Conexion.php';
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT events.id, events.title, patients.idpa, patients.numhs,patients.nompa, patients.apepa, doctor.idodc, doctor.ceddoc, doctor.nodoc, doctor.apdoc, laboratory.idlab, laboratory.nomlab, events.start, events.end, events.color, events.state,events.chec,events.monto FROM events INNER JOIN patients ON events.idpa = patients.idpa INNER JOIN doctor ON events.idodc = doctor.idodc INNER JOIN laboratory ON events.idlab = laboratory.idlab WHERE id= '$id';");
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
<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off"  onsubmit="return validacion()">
    <div class="containerss">
        <label for="psw"><b>Nombre del paciente</b></label><span class="badge-warning">*</span>
        <input type="text" readonly value="<?php echo $d->nompa; ?>&nbsp;<?php echo $d->apepa; ?>" name="appfin">
        <input type="hidden" name="appid" value="<?php echo $d->id; ?>">

        <label for="email"><b>Monto a pagar</b></label><span class="badge-warning">*</span>
        <input type="text" placeholder="S/. 0.00" name="appmont" id="appmont" required value="0.00">

        <button type="submit" name="upd_app_mone" class="registerbtn">Realizar pago</button>
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
    <script src="../../backend/js/patiens.js"></script>
    <script src="../../backend/js/doctor.js"></script>
    <script src="../../backend/js/laboratory.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
     <?php include_once '../../backend/php/upd_app_mone.php' ?>
   
</body>
</html>


