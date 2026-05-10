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

<h1 class="title"><?php echo $saludo . ', <strong>' . $_SESSION['name'] . '</strong>'; ?></h1>

            <button class="button" onclick="location.href='nuevo.php'">Nuevo Pago</button>
            <button class="button" onclick="location.href='mostrar.php'">Historial de Pagos</button>
           
           <!-- multistep form -->


<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off">
  <div class="containerss">
    <h1>Nueva pago de la cita</h1>
   
    <div class="alert-danger">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span><br>
 
</div>
    <hr>
<br>
    <label for="email"><b>Motivo de la cita</b></label><span class="badge-warning">*</span>
    <textarea name="appnam" style="height:200px" placeholder="Write something.."> </textarea>
  
    <label for="psw"><b>Nombre del paciente</b></label><span class="badge-warning">*</span>
    <select required name="apppac" id="pati">
        <option>Seleccione</option>
    </select>

    <label for="psw"><b>Nombre del médico</b></label><span class="badge-warning">*</span>
    <select required name="appdoc" id="doc">
        <option>Seleccione</option>
    </select>

    <label for="email"><b>Especialidad del médico</b></label><span class="badge-warning">*</span>

     <select disabled id="spe">
        <option>Seleccione</option>
    </select>


    <label for="psw"><b>Laboratorio</b></label><span class="badge-warning">*</span>
    <select required name="applab" id="lab">
        <option>Seleccione</option>
    </select>

    <label for="psw"><b>Color</b></label><span class="badge-warning">*</span>
    <select required name="appco" id="gep">
        <option style="color:#CD5C5C;" value="#CD5C5C">&#9724; Indio Rojo</option>
        <option style="color:#F08080;" value="#F08080">&#9724; Coral claro</option>
        <option style="color:#8B0000;" value="#8B0000">&#9724; Rojo oscuro</option>
        <option style="color:#0071c5;" value="#0071c5">&#9724; Azul oscuro</option>
        <option style="color:#FFC0CB;" value="#FFC0CB">&#9724; Rosado</option>
        <option style="color:#FFB6C1;" value="#FFB6C1">&#9724; Rosa claro</option>
        <option style="color:#FF7F50;" value="#FF7F50">&#9724; Coral</option>
        <option style="color:#FF4500;" value="#FF4500">&#9724; Rojo naranja</option>
        <option style="color:#FFFF00;" value="#FFFF00">&#9724; Amarillo</option>
        <option style="color:#EE82EE;" value="#EE82EE">&#9724; Violeta</option>
        
          
    </select>

    <label for="email"><b>Fecha inicial</b></label><span class="badge-warning">*</span>
    <input type="datetime-local"  name="appini"required="">

    <label for="email"><b>Fecha final</b></label><span class="badge-warning">*</span>
    <input type="datetime-local"  name="appfin"required="">

     <label for="email"><b>Monto a pagar</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="S/. 0.00" name="appmont" required="" value="0.00">

     <label for="email"><b>Realiza pago</b></label><span class="badge-warning">*</span>
     <label>SI</label>
    <input type="checkbox" name="appreal"   value="1">


    <hr>
   
    <button type="submit" name="add_appointment" class="registerbtn">Guardar</button>
  </div>
  
</form>

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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
     <?php include_once '../../backend/php/add_appointment.php' ?>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
   
</body>
</html>


