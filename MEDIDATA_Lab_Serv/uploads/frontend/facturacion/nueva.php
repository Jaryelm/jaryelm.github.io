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

    <!-- Include CSS de Select2 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <title>MEDIDATA</title>
</head>
<body>

<?php
include_once '../facturacion/menu.php';
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
include_once '../facturacion/perfil.php';
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


<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off">
  <div class="containerss">
    <h1>Programación</h1>

    <br>
   
    <div class="alert-danger">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span><br>
 
</div>
    <hr>
<br>
    <label for="email"><b>Motivo de la cita</b></label><span class="badge-warning">*</span>
    <textarea name="appnam" style="height:200px" placeholder="Write something.."> </textarea>
  
    <label for="psw"><b>Nombre del paciente</b></label><span class="badge-warning">*</span>
    <select required name="apppac" id="pati" class="select2">
        <option>Seleccione...</option>
    </select>

    <label for="psw"><b>Nombre del médico</b></label><span class="badge-warning">*</span>
    <select required name="appdoc" id="doc" class="select2">
        <option>Seleccione...</option>
    </select>

    <label for="email"><b>Especialidad del médico</b></label><span class="badge-warning">*</span>

     <select disabled id="spe">
        <option>Seleccione...</option>
    </select>

    <label for="psw"><b>Área de Servicio</b></label><span class="badge-warning">*</span>
    <select required name="applab" id="lab">
        <option value="" disabled selected>Seleccione...</option>

    </select>

    <label for="psw"><b>Prioridad Color</b></label><span class="badge-warning">*</span>
    <select required name="appco" id="gep">
        <option style="color:#0071c5;" value="#0071c5">&#9724; Azul oscuro</option>
        <option style="color:#FF4500;" value="#FF4500">&#9724; Rojo</option>
        <option style="color:#EE82EE;" value="#EE82EE">&#9724; Violeta</option>
    </select>

<label for="email"><b>Fecha inicial</b></label><span class="badge-warning">*</span>
<input type="date" name="appini" required="">

<label for="email"><b>Fecha final</b></label><span class="badge-warning">*</span>
<input type="date" name="appfin" required="">

<label for="start_time"><b>Hora de inicio</b></label><span class="badge-warning">*</span>
<input type="time" name="start_time" placeholder="HH:MM" value="00:00" required>

<label for="duration"><b>Duración del evento (en horas ó minutos)</b></label><span class="badge-warning">*</span>
<input 
    type="number" 
    name="duration" 
    placeholder="Duración en horas (ej. 1.5, vacío = 24 horas)" 
    step="0.1" 
    min="0" 
    title="Si dejas este campo vacío, el evento será de 24 horas completas."
>

    <!-- Nuevos campos -->
    <label for="room_number"><b>No. Habitación</b></label>
    <input type="text" name="room_number" placeholder="No. Habitación">

    <label for="insurer"><b>Aseguradora</b></label>
    <input type="text" name="insurer" placeholder="Aseguradora">

    <label for="policy_number"><b>No. Póliza</b></label>
    <input type="text" name="policy_number" placeholder="No. Póliza">

    <label for="certificate_number"><b>No. Certificado</b></label>
    <input type="text" name="certificate_number" placeholder="No. Certificado">

    <!-- Nuevos campos -->
    <label for="surgery"><b>Cirugía</b></label>
    <input type="text" name="surgery" placeholder="Cirugía">

    <label for="hospitalization"><b>Hospitalización</b></label>
    <input type="text" name="hospitalization" placeholder="Hospitalización">

    <label for="assistant"><b>Ayudante</b></label>
    <input type="text" name="assistant" placeholder="Ayudante">

    <label for="anesthetist"><b>Anestesiólogo/a</b></label>
    <input type="text" name="anesthetist" placeholder="Anestesiólogo/a">

    <label for="circulating"><b>Circulante</b></label>
    <input type="text" name="circulating" placeholder="Circulante">

    <label for="technician"><b>Técnico</b></label>
    <input type="text" name="technician" placeholder="Técnico">

    <label for="instrumentist"><b>Instrumentista</b></label>
    <input type="text" name="instrumentist" placeholder="Instrumentista">

    <label for="evaluation"><b>Valoración</b></label>
    <input type="text" name="evaluation" placeholder="Valoración">
    
    <label for="email"><b>Monto a pagar</b></label><span class="badge-warning">*</span>
    <input type="text" placeholder="LPS. 0.00" name="appmont" required="" value="0.00">

     <label for="email"><b>Realiza pago</b></label><span class="badge-warning">*</span>
     <label>SI</label>
    <input type="checkbox" name="appreal" value="1" required>


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
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    
     <?php include_once '../../backend/php/add_appointment.php' ?>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>

    <!-- Include jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<!-- Include Select2 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<!-- Inicializar Select2 JS -->
<script>
    $(document).ready(function() {
        $('.select2').select2(); // Inicializa Select2 para todos los select con clase select2
    });
</script>
   
</body>
</html>


