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
include_once '../servicioalcliente/menu.php';
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
include_once '../servicioalcliente/perfil.php';
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
           
           <!-- multistep form -->
           <button class="button" onclick="cambiarColor(this, '../servicioalcliente/nuevo.php')">Registro de Pacientes</button>
           <button class="button" onclick="cambiarColor(this, '../servicioalcliente/historial.php')">Expediente Clínico</button>
           <button class="button" onclick="cambiarColor(this, '../servicioalcliente/documentos.php')">Agregar Documentos</button>
           <button class="button" onclick="cambiarColor(this, '../servicioalcliente/nueva.php')">Nueva Cita</button>
           <button class="button" onclick="cambiarColor(this, '../servicioalcliente/calendario.php')">Calendario de Citas</button>


<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Registro de Pacientes</h1>
    <div class="alert-danger">
  <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
  <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
</div>
    <hr>
    <br>
    <label for="psw"><b>Nombres del Paciente</b></label><span class="badge-warning">*</span>
    <input type="text" name="namp" placeholder="Primer y Segundo Nombre" required>

    <label for="psw"><b>Apellidos del Paciente</b></label><span class="badge-warning">*</span>
    <input type="text" name="apep" placeholder="Coloque la Profesión" required>

    <label for="email"><b>DNI del Paciente</b></label><span class="badge-warning">*</span>
    <input type="text" name="nhi" maxlength="13" placeholder="Coloquelo sin guiones" required>

    <label for="psw"><b>Fecha de nacimiento del Paciente</b></label><span class="badge-warning">*</span>
    <input type="date" name="cump" required>

    <label for="psw"><b>Género del Paciente</b></label><span class="badge-warning">*</span>
    <select required name="gep" id="gep">
        <option>Seleccione</option>
        <option value="Masculino">Masculino</option>
        <option value="Femenino">Femenino</option>
    </select>

    <label for="psw"><b>Domicilio</b></label><span class="badge-warning">*</span>
    <input type="text" name="dip" required>

    <label for="psw"><b>Correo Electronico</b></label><span class="badge-warning">*</span>
    <input type="text" name="dip_correo" required>

    <label for="psw"><b>Teléfono del Paciente</b></label><span class="badge-warning">*</span>
    <input type="text" maxlength="13" placeholder="+504 " name="telp" required>

    <label for="psw"><b>Profesión</b></label><span class="badge-warning">*</span>
    <input type="text" name="profesion" placeholder="Coloque la Profesión" required>

    <label for="psw"><b>Nombre Completo Responsable/Familiar</b></label><span class="badge-warning">*</span>
    <input type="text" name="resnamp" placeholder="Coloque El Nombre Completo" required>

    <label for="psw"><b>Teléfono del Responsable/Familiar</b></label><span class="badge-warning">*</span>
    <input type="text" maxlength="13" placeholder="+504 " name="telp_responsable" required>

    <label for="estado_civic"><b>Estado Civil</b></label><span class="badge-warning">*</span>
    <select name="estado_civic" id="estado_civic" required>
        <option value="" disabled selected>Seleccione su estado civil</option>
        <option value="Soltero/a">Soltero/a</option>
        <option value="Casado/a">Casado/a</option>
        <option value="Divorciado/a">Divorciado/a</option>
        <option value="Unión Libre">Unión Libre</option>
        <option value="Viudo/a">Viudo/a</option>
    </select>

    <hr>
   
    <button type="submit" name="add_patiens" class="registerbtn">Guardar</button>
  </div>
  
</form>

        </main>
        <!-- MAIN -->
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
<?php include_once '../../backend/php/add_patiens.php' ?>

    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="../../backend/js/vpat.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>
    

    <script type="text/javascript">
    let popUp = document.getElementById("cookiePopup");
//When user clicks the accept button
document.getElementById("acceptCookie").addEventListener("click", () => {
  //Create date object
  let d = new Date();
  //Increment the current time by 1 minute (cookie will expire after 1 minute)
  d.setMinutes(2 + d.getMinutes());
  //Create Cookie withname = myCookieName, value = thisIsMyCookie and expiry time=1 minute
  document.cookie = "myCookieName=thisIsMyCookie; expires = " + d + ";";
  //Hide the popup
  popUp.classList.add("hide");
  popUp.classList.remove("shows");
});
//Check if cookie is already present
const checkCookie = () => {
  //Read the cookie and split on "="
  let input = document.cookie.split("=");
  //Check for our cookie
  if (input[0] == "myCookieName") {
    //Hide the popup
    popUp.classList.add("hide");
    popUp.classList.remove("shows");
  } else {
    //Show the popup
    popUp.classList.add("shows");
    popUp.classList.remove("hide");
  }
};
//Check if cookie exists when page loads
window.onload = () => {
  setTimeout(() => {
    checkCookie();
  }, 2000);
};
    </script>
   
</body>
</html>


