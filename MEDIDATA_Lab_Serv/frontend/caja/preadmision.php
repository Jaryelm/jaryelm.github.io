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





    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../caja/menu.php';
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
include_once '../caja/perfil.php';
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

<br>

<form action="phpemail/pre-admision.php" method="post" enctype="multipart/form-data" autocomplete="off">
  <div class="containerss">
    <h1>Formulario de Pre-Admisión</h1>
    <p>Nuestro propósito es facilitarle el proceso de admisión a nuestro servicio de Hospitalización/Cirugía. Por favor proceda en llenar los datos solicitados:</p>
    <hr>
<br>
    <h3>A. Datos Demográficos (Datos de Paciente)</h3>
    <label for="nombre_paciente"><b>Nombre Completo</b></label><span class="badge-warning">*</span>
    <input type="text" id="nombre_paciente" name="nombre_paciente" placeholder="Ingrese el nombre completo" required>

    <label for="documento_identidad_paciente"><b>DNI o Pasaporte</b></label><span class="badge-warning">*</span>
    <input type="text" id="documento_identidad_paciente" name="documento_identidad_paciente" placeholder="Ingrese el número de documento" required>

    <label for="sexo_paciente"><b>Sexo</b></label><span class="badge-warning">*</span>
    <select id="sexo_paciente" name="sexo_paciente" required>
      <option value="" disabled selected>Selecciona</option>
      <option value="Femenino">Femenino</option>
      <option value="Masculino">Masculino</option>
    </select>

    <label for="fecha_nacimiento_paciente"><b>Fecha de Nacimiento</b></label><span class="badge-warning">*</span>
    <input type="date" id="fecha_nacimiento_paciente" name="fecha_nacimiento_paciente" placeholder="dd/mm/aaaa" required>

    <label for="nacionalidad_paciente"><b>Nacionalidad</b></label><span class="badge-warning">*</span>
    <input type="text" id="nacionalidad_paciente" name="nacionalidad_paciente" placeholder="Ingrese la nacionalidad" required>

    <label for="ciudad_municipio_paciente"><b>Ciudad/Municipio</b></label><span class="badge-warning">*</span>
    <input type="text" id="ciudad_municipio_paciente" name="ciudad_municipio_paciente" placeholder="Ingrese la ciudad o municipio" required>

    <label for="estado_civil_paciente"><b>Estado Civil</b></label><span class="badge-warning">*</span>
    <select id="estado_civil_paciente" name="estado_civil_paciente" required>
      <option value="" disabled selected>Selecciona</option>
      <option value="Soltero">Soltero</option>
      <option value="Casado">Casado</option>
      <option value="Unión Libre">Unión Libre</option>
      <option value="Viudo">Viudo</option>
    </select>

    <label for="celular_paciente"><b>Celular</b></label><span class="badge-warning">*</span>
    <input type="text" id="celular_paciente" name="celular_paciente" placeholder="Ingrese el número de celular" required>

    <label for="correo_electronico_paciente"><b>Correo Electrónico</b></label><span class="badge-warning">*</span>
    <input type="email" id="correo_electronico_paciente" name="correo_electronico_paciente" placeholder="Ingrese el correo electrónico" required>

    <hr>
<br>
    <h3>B. Datos Hospitalarios</h3>
    <label for="servicio_solicitado"><b>Servicio Solicitado</b></label><span class="badge-warning">*</span>
    <select id="servicio_solicitado" name="servicio_solicitado" required>
      <option value="" disabled selected>Selecciona</option>
      <option value="Hospitalización">Hospitalización</option>
      <option value="Cirugía">Cirugía</option>
      <option value="Maternidad">Maternidad</option>
      <option value="UCI (Unidad de Cuidados Intensivos)">UCI (Unidad de Cuidados Intensivos)</option>
    </select>

    <label for="fechas_ingreso"><b>Fechas de Ingreso</b></label>
    <input type="date" id="fechas_ingreso" name="fechas_ingreso" placeholder="dd/mm/aaaa">

    <hr>
<br>
    <h3>C. Datos de Persona Responsable</h3>
    <p>La Persona Responsable es la encargada de toda la gestión administrativa del paciente...</p>

    <label for="nombre_responsable"><b>Nombre completo</b></label><span class="badge-warning">*</span>
    <input type="text" id="nombre_responsable" name="nombre_responsable" placeholder="Ingrese el nombre completo" required>

    <label for="documento_identidad_responsable"><b>DNI o Pasaporte</b></label><span class="badge-warning">*</span>
    <input type="text" id="documento_identidad_responsable" name="documento_identidad_responsable" placeholder="Ingrese el documento de identidad" required>

    <label for="firma_digital"><b>Firma Digital</b></label><span class="badge-warning">*</span>
    <canvas id="signaturePad" class="firma"></canvas>
    <button type="button" class="registerbtn" onclick="clearSignature()">Limpiar Firma</button>
    <input type="hidden" id="firma_digital" name="firma_digital">

    <hr>
    <button type="submit" class="registerbtn">Enviar Solicitud</button>
  </div>
</form>

<!-- Estilos -->
<style>
  .firma {
    border: 1px solid #000;
    width: 100%;
    height: 200px;
    touch-action: none;
  }
</style>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery"></script>
<script>
  // Inicializar el canvas de la firma
  function inicializarCanvasFirma() {
    const canvas = document.getElementById("signaturePad");
    const ctx = canvas.getContext("2d");
    let isDrawing = false;

    // Ajustar el tamaño del canvas al contenedor
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        const ratio = window.devicePixelRatio || 1;
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.clearRect(0, 0, canvas.width, canvas.height); // Limpiar cualquier contenido previo
    }

    // Inicializar el tamaño del canvas
    resizeCanvas();
    window.addEventListener("resize", resizeCanvas);

    // Función para obtener coordenadas relativas al canvas
    function getCanvasCoordinates(event) {
        const rect = canvas.getBoundingClientRect();
        const x = (event.clientX - rect.left) * (canvas.width / rect.width);
        const y = (event.clientY - rect.top) * (canvas.height / rect.height);
        return { x, y };
    }

    // Eventos de dibujo
    canvas.addEventListener("mousedown", (e) => {
        isDrawing = true;
        const { x, y } = getCanvasCoordinates(e);
        ctx.beginPath();
        ctx.moveTo(x, y);
    });

    canvas.addEventListener("mousemove", (e) => {
        if (isDrawing) {
            const { x, y } = getCanvasCoordinates(e);
            ctx.lineTo(x, y);
            ctx.stroke();
        }
    });

    canvas.addEventListener("mouseup", () => {
        isDrawing = false;
    });

    canvas.addEventListener("mouseout", () => {
        isDrawing = false;
    });

    // Limpiar firma
    window.clearSignature = function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    };
}

document.addEventListener("DOMContentLoaded", function () {
    inicializarCanvasFirma();

    // Capturar la firma al enviar el formulario
    document.querySelector("form").addEventListener("submit", function (e) {
        const canvas = document.getElementById("signaturePad");
        const firma = canvas.toDataURL();

        if (firma === "data:,") {
            e.preventDefault();
            alert("Por favor, dibuje su firma.");
        } else {
            document.getElementById("firma_digital").value = firma;
        }
    });
});
</script>

        </main>
        <!-- MAIN -->
    </section>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

</body>
</html>