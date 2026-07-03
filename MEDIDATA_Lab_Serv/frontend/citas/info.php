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

            <button class="button" onclick="location.href='nuevo.php'">Nueva Cita</button>
            <button class="button" onclick="location.href='mostrar.php'">Todas las citas</button>
            <button class="button" onclick="location.href='calendario.php'">Calendario de Citas</button>
           
           <!-- multistep form -->
<?php 
 $id = $_GET['id'];
 $sentencia = $connect->prepare("SELECT events.id, events.title, patients.idpa, patients.numhs,patients.nompa, patients.apepa, doctor.idodc, doctor.ceddoc, doctor.nodoc,doctor.nomesp, doctor.apdoc, laboratory.idlab, laboratory.nomlab, events.start, events.end, events.color, events.state,events.chec,events.monto FROM events INNER JOIN patients ON events.idpa = patients.idpa INNER JOIN doctor ON events.idodc = doctor.idodc INNER JOIN laboratory ON events.idlab = laboratory.idlab WHERE id= '$id';");
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
<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off">
  <div class="containerss">

<br>
    <label for="email"><b>Motivo de la cita</b></label><span class="badge-warning">*</span>
    <textarea name="appnam" style="height:200px" readonly placeholder="Write something.."><?php echo $d->title; ?> </textarea>
  
    <label for="psw"><b>Nombre del paciente</b></label><span class="badge-warning">*</span>
    <select readonly required name="apppac" id="pati">
        <option><?php echo $d->nompa; ?>&nbsp; <?php echo $d->apepa; ?></option>
    </select>

    <label for="psw"><b>Nombre del médico</b></label><span class="badge-warning">*</span>
    <select readonly required name="" id="doc">
        <option><?php echo $d->nodoc; ?>&nbsp; <?php echo $d->apdoc; ?></option>
    </select>

    <label for="email"><b>Especialidad del médico</b></label><span class="badge-warning">*</span>

     <select readonly id="spe">
        <option><?php echo $d->nomesp; ?></option>
    </select>


    <label for="psw"><b>Laboratorio</b></label><span class="badge-warning">*</span>
    <select required name="" id="lab" readonly>
        <option><?php echo $d->nomlab; ?></option>
    </select>

    <label for="psw"><b>Color</b></label><span class="badge-warning">*</span>
    <select required name="appco" id="gep">
        <option style="color:#CD5C5C;" value="#CD5C5C"><?php echo $d->color; ?></option>
        
        
          
    </select>

    <label for="email"><b>Fecha inicial</b></label><span class="badge-warning">*</span>
    <input readonly type="datetime-local" value="<?php echo $d->start; ?>" name="appini"required="">

    <label for="email"><b>Fecha final</b></label><span class="badge-warning">*</span>
    <input readonly type="datetime-local" value="<?php echo $d->end; ?>"  name="appfin"required="">

     <label for="email"><b>Monto a pagar</b></label><span class="badge-warning">*</span>
    <input type="text" readonly placeholder="S/. 0.00"  value="<?php echo $d->monto; ?>" name="appmont" required="" value="0.00">

     <label for="email"><b>Realiza pago</b></label><span class="badge-warning">*</span>
     <label>SI</label>
    <input type="checkbox" id="<?=$d->id?>" value="<?=$d->chec ?>" <?=$d->chec == '1' ? 'checked' : '' ;?> name="appreal"   value="1">


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


    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="../../backend/js/vpat.js"></script>

    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
  
   
</body>
</html>


