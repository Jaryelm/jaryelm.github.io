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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
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

<button class="button" onclick="cambiarColor(this, '../pacientes/uci_reg_lista.php')">Registros de UCI</button>

<br>
            
<form action="" enctype="multipart/form-data" method="POST"  autocomplete="off" onsubmit="return validacion()">
  <div class="containerss">
    <h1>Unidad de Cuidados Intensivos e Intermedios</h1>

    <!-- Alerta de Información -->
    <div class="alert-danger">
      <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
      <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
    </div>
    
    <hr><br>

    <!-- Datos Generales -->
    <label for="nombre_paciente"><b>Nombre del Paciente</b></label><span class="badge-warning">*</span>
    <input type="text" id="nombre_paciente" name="nombre_paciente" required>

    <label for="edad_paciente"><b>Edad</b></label><span class="badge-warning">*</span>
    <input type="number" id="edad_paciente" name="edad_paciente" required>

    <label for="fecha_registro"><b>Fecha</b></label><span class="badge-warning">*</span>
    <input type="date" id="fecha_registro" name="fecha_registro" required>

    <label for="dx_paciente"><b>DX</b></label>
    <input type="text" id="dx_paciente" name="dx_paciente">

    <label for="medico_paciente"><b>Médico</b></label>
    <input type="text" id="medico_paciente" name="medico_paciente">

    <label for="peso_kg"><b>Peso (kg)</b></label>
    <input type="number" id="peso_kg" name="peso_kg" step="0.1">

    <label for="talla_cm"><b>Talla (cm)</b></label>
    <input type="number" id="talla_cm" name="talla_cm" step="0.1">

    <label for="sonda_foley"><b>Sonda Foley</b></label>
    <input type="text" id="sonda_foley" name="sonda_foley">

    <label for="sng"><b>SNG</b></label>
    <input type="text" id="sng" name="sng">

    <label for="dias_hospitalizacion"><b>Días Hospitalización</b></label>
    <input type="number" id="dias_hospitalizacion" name="dias_hospitalizacion">

    <!-- Radios -->
    <div class="radio-group">
      <p><b>Ventilación Mecánica:</b></p>
      <br>
      <label><input type="radio" name="ventilacion_mecanica" value="Si"> Sí</label>
      <label><input type="radio" name="ventilacion_mecanica" value="No"> No</label>
    </div>

    <br>

    <div class="radio-group">
      <p><b>Monitor Cardiaco:</b></p>
      <br>
      <label><input type="radio" name="monitor_cardiaco" value="Si"> Sí</label>
      <label><input type="radio" name="monitor_cardiaco" value="No"> No</label>
    </div>

    <hr><br>

    <!-- Signos Vitales -->
    <h3>Signos Vitales</h3>

    <br>

    <label for="presion_arterial"><b>Presión Arterial</b></label>
    <input type="text" id="presion_arterial" name="presion_arterial">

    <label for="frecuencia_cardiaca"><b>Frecuencia Cardiaca</b></label>
    <input type="number" id="frecuencia_cardiaca" name="frecuencia_cardiaca">

    <label for="frecuencia_respiratoria"><b>Frecuencia Respiratoria</b></label>
    <input type="number" id="frecuencia_respiratoria" name="frecuencia_respiratoria">

    <label for="temperatura"><b>Temperatura (°C)</b></label>
    <input type="number" id="temperatura" name="temperatura" step="0.1">

    <label for="saturacion"><b>Saturación (%)</b></label>
    <input type="number" id="saturacion" name="saturacion">

    <label for="pvc"><b>P.V.C</b></label>
    <input type="text" id="pvc" name="pvc">

    <label for="pic"><b>P.I.C</b></label>
    <input type="text" id="pic" name="pic">

    <label for="pia"><b>PIA</b></label>
    <input type="text" id="pia" name="pia">

    <label for="glucometria"><b>Glucometría</b></label>
    <input type="number" id="glucometria" name="glucometria">

    <hr>

    <!-- Aportes -->
    <h3>Aportes</h3>

    <br>

    <label for="soluciones_endovenosas"><b>Soluciones Endovenosas</b></label>
    <textarea id="soluciones_endovenosas" name="soluciones_endovenosas"></textarea>

    <hr>

    <!-- Medicación -->
    <h3>Medicación</h3>
    <p>Aquí se agregará la información más adelante.</p>

    <hr>

    <br>

    <!-- Balance -->
    <h3>Balance</h3>
<br>
    <h4>Ingestas</h4>
<br>
    <label for="agua_endogena"><b>Agua Endógena</b></label>
    <input type="text" id="agua_endogena" name="agua_endogena">

    <label for="alimentacion"><b>Alimentación</b></label>
    <input type="text" id="alimentacion" name="alimentacion">

    <label for="hemoderivados"><b>Hemoderivados</b></label>
    <input type="text" id="hemoderivados" name="hemoderivados">

    <h4>Excretas</h4>
<br>
    <label for="perdidas_insensibles"><b>Pérdidas Insensibles</b></label>
    <input type="text" id="perdidas_insensibles" name="perdidas_insensibles">

    <label for="residuo_gastrico"><b>Residuo Gástrico</b></label>
    <input type="text" id="residuo_gastrico" name="residuo_gastrico">

    <label for="hemovac"><b>Hemovac</b></label>
    <input type="text" id="hemovac" name="hemovac">

    <label for="succion_drenos"><b>Succión/Drenos</b></label>
    <input type="text" id="succion_drenos" name="succion_drenos">

    <label for="vomitos_sng"><b>Vómitos/SNG</b></label>
    <input type="text" id="vomitos_sng" name="vomitos_sng">

    <label for="heces"><b>Heces</b></label>
    <input type="text" id="heces" name="heces">

    <div class="radio-group">
      <p><b>Diuresis por:</b></p>
      <label><input type="radio" name="diuresis_por" value="Hora"> Hora</label>
      <label><input type="radio" name="diuresis_por" value="Fracción"> Fracción</label>
    </div>
<br>
    <label for="diuresis_acumulada"><b>Diuresis Acumulada</b></label>
    <input type="text" id="diuresis_acumulada" name="diuresis_acumulada">

    <hr>

    <!-- Botón -->
    <button type="submit" class="registerbtn">Registrar Datos</button>
  </div>
</form>


        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <?php include_once '../../frontend/pacientes/save_cuidados_intensivos.php' ?>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

        <!-- Script para manejar el cambio de color en los botones -->
        <script src="../../backend/registros/script/botones_color.js"></script>

</body>
</html>


