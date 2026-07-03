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
            <div class="input-block">

<a type="button" href="imprimir.php?id=<?php echo $d->idpa; ?>" class="button">Imprimir</a>

<br><br>

<div class="wrap-line">

    <!-- Inputs -->
    <div class="brise-input">
        <label for="numhs">Número DNI</label>
        <input type="text" id="numhs" value="<?php echo $d->numhs; ?>" name="numhs" required>
        <span class="line"></span>
    </div>

    <div class="brise-input">
        <label for="nompa">Nombre</label>
        <input type="text" id="nompa" value="<?php echo $d->nompa; ?>" name="nompa" required>
        <span class="line"></span>
    </div>

    <div class="brise-input">
        <label for="apepa">Apellido</label>
        <input type="text" id="apepa" value="<?php echo $d->apepa; ?>" name="apepa" required>
        <span class="line"></span>
    </div>

    <div class="brise-input">
        <label for="direc">Domicilio</label>
        <input type="text" id="direc" value="<?php echo $d->direc; ?>" name="direc" required>
        <span class="line"></span>
    </div>

    <div class="brise-input">
        <label for="cump">Fecha de Nacimiento</label>
        <input type="text" id="cump" value="<?php echo $d->cump; ?>" name="cump" required>
        <span class="line"></span>
    </div>

    <div class="brise-input">
        <label for="sex">Sexo</label>
        <input type="text" id="sex" value="<?php echo $d->sex; ?>" name="sex" required>
        <span class="line"></span>
    </div>

    <div class="brise-input">
        <label for="phon">Teléfono</label>
        <input type="text" id="phon" value="<?php echo $d->phon; ?>" name="phon" required>
        <span class="line"></span>
    </div>

</div>

</div>

<style>
    .brise-input label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
}
</style>

<!-- Estilo para el boton "Registrar" -->
<Style>
.register-btn {
    background-color: #035c67; /* Color de fondo */
    color: #fff; /* Color de texto */
    padding: 8px 12px; /* Espaciado interno */
    border: none; /* Sin borde */
    border-radius: 5px; /* Bordes redondeados */
    font-size: 0.9rem; /* Tamaño de fuente */
    cursor: pointer; /* Cursor de mano */
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.register-btn:hover {
    background-color: #06adbf; /* Color al pasar el cursor */
    transform: scale(1.05); /* Aumentar ligeramente el tamaño */
}

.register-btn:active {
    transform: scale(1); /* Restaurar tamaño al hacer clic */
}
</Style>

<style>
.input-container {
    display: flex;
    flex-direction: column;
    gap: 5px; /* Espaciado entre el label y el textarea */
    width: 100%;
}

.input-container label {
    font-weight: bold; /* Para resaltar los títulos */
    text-align: left; /* Asegura que los títulos estén alineados a la izquierda */
}

.input-container textarea {
    width: 100%;
    max-width: 100%;
    resize: none; /* Evita que el usuario cambie el tamaño */
}
</style>

<div class="data">
    <div class="content-data">

<button class="accordion">Autorización Quirúrgica</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarAutorizacionPDF()">Descargar Hoja de Autorización Quirúrgica</button>
        <button class="register-btn" onclick="registrarAutorizacion()">Registrar</button>
    </div>
    <br>
    <div class="table-responsive">
    <table class="responsive-table">
        <tbody>
            <tr>
                <td colspan="2">
                    <div class="input-container">
                        <label for="intervencion_quirurgica">Intervención Quirúrgica:</label>
                        <textarea id="intervencion_quirurgica" rows="3"></textarea>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td colspan="2">
                    <div class="input-container">
                        <label for="consistente_en">Consistente en:</label>
                        <textarea id="consistente_en" rows="3"></textarea>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

<script>
function registrarAutorizacion() {
    const idpa = <?php echo $_GET['id']; ?>;
    const consistente_en = document.getElementById("consistente_en").value;
    const intervencion_quirurgica = document.getElementById("intervencion_quirurgica").value;

    if (!consistente_en.trim() || !intervencion_quirurgica.trim()) {
        Swal.fire('Error', 'Debe ingresar los datos indicados.', 'error');
        return;
    }

    $.ajax({
        type: "POST",
        url: "save_intervencion_quirurgica.php",
        data: { idpa, consistente_en, intervencion_quirurgica },
        success: function (response) {
            if (response.success) {
                Swal.fire('Guardado', response.message, 'success').then(() => {
                    cargarDatosAlta();
                });
            } else {
                Swal.fire(response.type === "warning" ? 'Advertencia' : 'Error', response.message, response.type);
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Hubo un problema al registrar la solicitud.', 'error');
        }
    });
}

function cargarDatosAlta() {
    const idpa = <?php echo $_GET['id']; ?>;

    fetch(`fetch_autorizacion_quirurgica.php?idpa=${idpa}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("consistente_en").value = data.consistente_en || '';
                document.getElementById("intervencion_quirurgica").value = data.intervencion_quirurgica || '';
            }
        })
        .catch(error => console.error("Error al cargar los datos:", error));
}

document.addEventListener("DOMContentLoaded", cargarDatosAlta);


// Descargar PDF de la solicitud de alta
function descargarAutorizacionPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay motivo guardado antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_autorizacion_quirurgica.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si existe un motivo, generar el PDF
                    window.open(`autorizacion_quirurgica_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay motivo guardado
                    Swal.fire('Advertencia', 'No se puede generar la hoja de Solicitud de Alta Exigida ya que no has registrado el Dianostico y Motivo para completar la solicitud.', 'warning');
                }
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al verificar el motivo.', 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', 'No se pudo verificar el motivo. Intente nuevamente más tarde.', 'error');
        }
    });
}
</script>



<button class="accordion">Procedimientos</button>
    <div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarProcedimientosPDF()">Descargar Hoja de Procedimientos</button>
    </div>
    <br>
    <div class="table-responsive">
    <table class="responsive-table">
        <thead>
            <tr>
                <th>FECHA</th>
                <th>HORA</th>
                <th>PROCESADO POR</th>
                <th>TURNO</th>
                <th>PROCEDIMIENTO REALIZADO</th>
                <th>ACCIONES</th>
            </tr>
        </thead>
        <tbody id="procedimientosBody">
            <!-- Aquí se llenarán los datos dinámicamente -->
        </tbody>
    </table>
    </div>

</div>

<script>
function registrarProcedimiento() {
    const idpa = <?php echo $_GET['id']; ?>;
    const turno = document.getElementById("turno").value;
    const procedimiento_realizado = document.getElementById("procedimiento_realizado").value;
    const procesado_por = "<?php echo $_SESSION['name']; ?>"; // Capturar usuario actual

    if (!turno.trim() || !procedimiento_realizado.trim()) {
        Swal.fire('Error', 'Debe ingresar los datos indicados.', 'error');
        return;
    }

    $.ajax({
        type: "POST",
        url: "save_procedimiento.php",
        data: { idpa, turno, procedimiento_realizado, procesado_por },
        success: function (response) {
            if (response.success) {
                Swal.fire('Guardado', response.message, 'success').then(() => {
                    cargarDatosProcedimientos();
                });
            } else {
                Swal.fire(response.message, 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', xhr.responseJSON?.message || 'Hubo un problema al registrar el procedimiento.', 'error');
        }
    });
}

function cargarDatosProcedimientos() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_procedimientos.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            let content = '';

            if (response.success && response.data.length > 0) {
                response.data.forEach(item => {
                    content += `
                        <tr>
                            <td>${item.fecha_registro}</td>
                            <td>${item.hora ?? 'N/A'}</td>
                            <td>${item.procesado_por}</td>
                            <td>${item.turno}</td>
                            <td>${item.procedimiento_realizado}</td>
                            <td><button class="register-btn" disabled>Registrado</button></td>
                        </tr>
                    `;
                });
            } else {
                content += `<tr><td colspan="6">No hay procedimientos registrados.</td></tr>`;
            }

            // Agregar fila para ingresar nuevos datos
            content += `
                <tr>
                    <td><input type="date" id="fecha"></td>
                    <td><input type="time" id="hora"></td>
                    <td><input type="text" id="procesadoPor" value="<?php echo $_SESSION['name']; ?>" readonly></td>
                    <td>
                        <select id="turno">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
<td>
    <select id="procedimiento_realizado">
        <option value="">Seleccione un procedimiento</option>
        <option value="Curaciones Generales">Curaciones Generales</option>
        <option value="Conducción de Sonda Vesical">Conducción de Sonda Vesical</option>
        <option value="Colocación de Sonda Nasogástricas">Colocación de Sonda Nasogástricas</option>
        <option value="Irrigaciones Vesicales">Irrigaciones Vesicales</option>
        <option value="Toma de Gaseo Arteriales">Toma de Gaseo Arteriales</option>
        <option value="Aspiración de Secciones">Aspiración de Secciones</option>
        <option value="Asistencia en Colocación de CVC">Asistencia en Colocación de CVC</option>
        <option value="Alimentación Parental">Alimentación Parental</option>
        <option value="Alimentación Entera">Alimentación Entera</option>
        <option value="Cuidados de Gastrostomía">Cuidados de Gastrostomía</option>
        <option value="Cuidados de Ileostomía">Cuidados de Ileostomía</option>
        <option value="Colocación de Enema Fleet o Jabonoso">Colocación de Enema Fleet o Jabonoso</option>
        <option value="Transfusión de Hemoderivados">Transfusión de Hemoderivados</option>
        <option value="Otros">Otros</option>
    </select>
</td>

                    <td><button class="register-btn" onclick="registrarProcedimiento()">Registrar</button></td>
                </tr>
            `;

            $("#procedimientosBody").html(content);
        },
        error: function (xhr) {
            console.error("Error al cargar los datos: " + xhr.responseText);
        }
    });
}

document.addEventListener("DOMContentLoaded", cargarDatosProcedimientos);


// Descargar PDF de la solicitud de alta
function descargarProcedimientosPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay motivo guardado antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_procedimientos.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si existe un motivo, generar el PDF
                    window.open(`procedimientos_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay motivo guardado
                    Swal.fire('Advertencia', 'No se puede generar la hoja solicitada ya que no has registrado los datos para completar la solicitud.', 'warning');
                }
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al verificar el motivo.', 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', 'No se pudo verificar el motivo. Intente nuevamente más tarde.', 'error');
        }
    });
}
</script>


<!-- Referencia -->
<button class="accordion">Referencia</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarAnexoReferenciaPDF()">Descargar Hoja de Anexo de Referencia</button>
        <button class="register-btn" onclick="registrarAnexoReferencia()">Registrar</button>
    </div>
    <br>

    <!-- Sección 1: Datos Generales -->
    <h3>Datos Generales</h3>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>FECHA</th>
                    <th>HORA</th>
                    <th>PROCESADO POR</th>
                    <th>MÉDICO REFERENCIA</th>
                    <th>HOSPITAL</th>
                    <th>SERVICIO</th>
                    <th>RESUMEN CLÍNICO</th>
                    <th>DIAGNÓSTICO</th>
                    <th>MOTIVO REFERENCIA</th>
                </tr>
            </thead>
            <tbody id="anexoReferenciaBody1">
                <!-- Aquí se mostrarán los registros existentes -->
            </tbody>
            <tfoot>
                <tr>
                    <td><input type="date" id="fecha"></td>
                    <td><input type="time" id="hora"></td>
                    <td><input type="text" id="procesadoPor" value="<?php echo $_SESSION['name']; ?>" readonly></td>
                    <td><input type="text" id="medico_ref"></td>
                    <td><input type="text" id="hospital_ref"></td>
                    <td><input type="text" id="servicio_ref"></td>
                    <td><textarea id="resumen_clinico" rows="2"></textarea></td>
                    <td><textarea id="diagnostico_ref" rows="2"></textarea></td>
                    <td><textarea id="motivo_ref" rows="2"></textarea></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <br>

    <!-- Sección 2: Signos Vitales -->
    <h3>Signos Vitales</h3>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>TEMPERATURA</th>
                    <th>FRECUENCIA CARDÍACA</th>
                    <th>FRECUENCIA RESPIRATORIA</th>
                    <th>PRESIÓN ARTERIAL</th>
                    <th>TENSIÓN ARTERIAL</th>
                    <th>LLENADO CAPILAR</th>
                    <th>SATURACIÓN OXÍGENO</th>
                    <th>ESCALA GLASGOW</th>
                </tr>
            </thead>
            <tbody id="anexoReferenciaBody2">
                <!-- Aquí se mostrarán los registros existentes -->
            </tbody>
            <tfoot>
                <tr>
                    <td><input type="text" id="temperatura_ref"></td>
                    <td><input type="text" id="fc_ref"></td>
                    <td><input type="text" id="fr_ref"></td>
                    <td><input type="text" id="pa_ref"></td>
                    <td><input type="text" id="ta_ref"></td>
                    <td><input type="text" id="llenado_capilar"></td>
                    <td><input type="text" id="spo2_ref"></td>
                    <td><input type="text" id="escala_glasgow_ref"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<script>
function registrarAnexoReferencia() {
    const idpa = <?php echo $_GET['id']; ?>;
    const procesado_por = "<?php echo $_SESSION['name']; ?>";

    const data = {
        idpa,
        procesado_por,
        fecha_registro: document.getElementById("fecha").value.trim(),
        hora_registro: document.getElementById("hora").value.trim(),
        medico_ref: document.getElementById("medico_ref").value.trim(),
        hospital_ref: document.getElementById("hospital_ref").value.trim(),
        servicio_ref: document.getElementById("servicio_ref").value.trim(),
        resumen_clinico: document.getElementById("resumen_clinico").value.trim(),
        diagnostico_ref: document.getElementById("diagnostico_ref").value.trim(),
        motivo_ref: document.getElementById("motivo_ref").value.trim(),
        temperatura_ref: document.getElementById("temperatura_ref").value.trim(),
        fc_ref: document.getElementById("fc_ref").value.trim(),
        fr_ref: document.getElementById("fr_ref").value.trim(),
        pa_ref: document.getElementById("pa_ref").value.trim(),
        ta_ref: document.getElementById("ta_ref").value.trim(),
        llenado_capilar: document.getElementById("llenado_capilar").value.trim(),
        spo2_ref: document.getElementById("spo2_ref").value.trim(),
        escala_glasgow_ref: document.getElementById("escala_glasgow_ref").value.trim()
    };

    $.ajax({
        type: "POST",
        url: "save_anexo_referencia.php",
        data,
        success: function(response) {
            if (response.success) {
                Swal.fire('Guardado', response.message, 'success').then(() => {
                    cargarDatosAnexoReferencia();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al registrar el anexo de referencia.', 'error');
        }
    });
}

function cargarDatosAnexoReferencia() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_anexo_referencia.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.success && response.data.length > 0) {
                let content1 = '';
                let content2 = '';

                response.data.forEach(item => {
                    // Sección 1: Datos Generales
                    content1 += `
                        <tr>
                            <td>${item.fecha_registro}</td>
                            <td>${item.hora_registro ?? 'N/A'}</td>
                            <td>${item.procesado_por}</td>
                            <td>${item.medico_ref}</td>
                            <td>${item.hospital_ref}</td>
                            <td>${item.servicio_ref}</td>
                            <td>${item.resumen_clinico}</td>
                            <td>${item.diagnostico_ref}</td>
                            <td>${item.motivo_ref}</td>
                        </tr>
                    `;

                    // Sección 2: Signos Vitales
                    content2 += `
                        <tr>
                            <td>${item.temperatura_ref ?? 'N/A'}</td>
                            <td>${item.fc_ref ?? 'N/A'}</td>
                            <td>${item.fr_ref ?? 'N/A'}</td>
                            <td>${item.pa_ref ?? 'N/A'}</td>
                            <td>${item.ta_ref ?? 'N/A'}</td>
                            <td>${item.llenado_capilar ?? 'N/A'}</td>
                            <td>${item.spo2_ref ?? 'N/A'}</td>
                            <td>${item.escala_glasgow_ref ?? 'N/A'}</td>
                        </tr>
                    `;
                });

                $("#anexoReferenciaBody1").html(content1);
                $("#anexoReferenciaBody2").html(content2);
            }
        },
        error: function(xhr) {
            Swal.fire('Error', 'Hubo un problema al cargar los datos.', 'error');
        }
    });
}

// Descargar PDF de la solicitud de alta
function descargarAnexoReferenciaPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay motivo guardado antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_anexo_referencia.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si existe un motivo, generar el PDF
                    window.open(`anexo_ref_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay motivo guardado
                    Swal.fire('Advertencia', 'No se puede generar la hoja solicitada ya que no has registrado los datos para completar la solicitud.', 'warning');
                }
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al verificar el motivo.', 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', 'No se pudo verificar el motivo. Intente nuevamente más tarde.', 'error');
        }
    });
}

document.addEventListener("DOMContentLoaded", cargarDatosAnexoReferencia);
</script>

<!-- Transfusión de Hemoderivados -->
<button class="accordion">Transfusión de Hemoderivados</button>
<div class="panel">
    <div class="botones-modals">
        <button class="register-btn" onclick="descargarTransfusionPDF()">Descargar Hoja de Transfusión de Hemoderivados</button>
        <label id="btnRegistrarTransfusion" for="btnTransfusionModal">Registrar</label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM transfusion_hemoderivados WHERE idpa = :id");
        $sentencia->bindParam(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $data = array();
        if ($sentencia) {
            while ($r = $sentencia->fetchObject()) {
                $data[] = $r;
            }
        }
        ?>

        <?php if (count($data) > 0): ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.getElementById("btnRegistrarTransfusion").addEventListener("click", function(event) {
                        event.preventDefault();
                        Swal.fire("Advertencia", "Este paciente ya tiene un registro de transfusión y no puede agregar otro.", "warning");
                    });
                });
            </script>
        <?php endif; ?>

        <?php if (count($data) > 0): ?>
            <!-- 🩸 Tabla 1: Información General -->
            <h3>Información General</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Tipo RH</th>
                        <th>Diagnóstico</th>
                        <th>Médico Tratante</th>
                        <th>Enfermero Responsable</th>
                        <th>Sangre Completa</th>
                        <th>Glóbulos Rojos</th>
                        <th>Plasma Normal</th>
                        <th>Plasma Fresco</th>
                        <th>Plaquetas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->tipo_rh; ?></td>
                            <td><?php echo $f->diagnostico_hemoderivados; ?></td>
                            <td><?php echo $f->medico_tratante_hemoderivados; ?></td>
                            <td><?php echo $f->enfermero_responsable_hemoderivados; ?></td>
                            <td><?php echo $f->sangre_completa_hemoderivados; ?></td>
                            <td><?php echo $f->globulos_rojos_hemoderivados; ?></td>
                            <td><?php echo $f->plasma_normal_hemoderivados; ?></td>
                            <td><?php echo $f->plasma_fresco_congelado_hemoderivados; ?></td>
                            <td><?php echo $f->plaquetas_hemoderivados; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 🩸 Tabla 2: Componentes Transfundidos -->
            <h3>Tipo de Hemoderivados a Transfundir</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Plaquetas Aféresis</th>
                        <th>Crio-Precipitado</th>
                        <th>Otros</th>
                        <th>Cantidad Unidades</th>
                        <th>Hora Inicio</th>
                        <th>Hora Finalización</th>
                        <th>Reacciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->plaquetas_aferesis_hemoderivados; ?></td>
                            <td><?php echo $f->crio_precipitado_hemoderivados; ?></td>
                            <td><?php echo $f->otros_hemoderivados; ?></td>
                            <td><?php echo $f->cantidad_unidades_hemoderivados; ?></td>
                            <td><?php echo $f->hora_inicio_hemoderivados; ?></td>
                            <td><?php echo $f->hora_finalizacion_hemoderivados; ?></td>
                            <td><?php echo $f->transfusion_reacciones; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 🩸 Tabla 3: Signos Vitales Antes de la Transfusión -->
            <h3>Signos Vitales Antes</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>PA Antes</th>
                        <th>FC Antes</th>
                        <th>TA Antes</th>
                        <th>FR Antes</th>
                        <th>SPO2 Antes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->pa_antes_transfundir; ?></td>
                            <td><?php echo $f->fc_antes_transfundir; ?></td>
                            <td><?php echo $f->ta_antes_transfundir; ?></td>
                            <td><?php echo $f->fr_antes_transfundir; ?></td>
                            <td><?php echo $f->spo2_antes_transfundir; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 🩸 Tabla 4: Signos Vitales Durante la Transfusión -->
            <h3>Signos Vitales Durante</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>PA 30 min</th>
                        <th>FC 30 min</th>
                        <th>TA 30 min</th>
                        <th>FR 30 min</th>
                        <th>SPO2 30 min</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->pa_30minutos_iniciar; ?></td>
                            <td><?php echo $f->fc_30minutos_iniciar; ?></td>
                            <td><?php echo $f->ta_30minutos_iniciar; ?></td>
                            <td><?php echo $f->fr_30minutos_iniciar; ?></td>
                            <td><?php echo $f->spo2_30minutos_iniciar; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- 🩸 Tabla 5: Signos Vitales a 1 Hora -->
            <h3>Signos Vitales Despues de 1 Hora</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>PA 1h</th>
                        <th>FC 1h</th>
                        <th>TA 1h</th>
                        <th>FR 1h</th>
                        <th>SPO2 1h</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->pa_1hora_iniciar; ?></td>
                            <td><?php echo $f->fc_1hora_iniciar; ?></td>
                            <td><?php echo $f->ta_1hora_iniciar; ?></td>
                            <td><?php echo $f->fr_1hora_iniciar; ?></td>
                            <td><?php echo $f->spo2_1hora_iniciar; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

                        <!-- 🩸 Tabla 5: Signos Vitales a 1 Hora -->
                        <h3>Signos Despues de 2 Hora</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>PA 2h</th>
                        <th>FC 2h</th>
                        <th>TA 2h</th>
                        <th>FR 2h</th>
                        <th>SPO2 2h</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->pa_2horas_iniciar; ?></td>
                            <td><?php echo $f->fc_2horas_iniciar; ?></td>
                            <td><?php echo $f->ta_2horas_iniciar; ?></td>
                            <td><?php echo $f->fr_2horas_iniciar; ?></td>
                            <td><?php echo $f->spo2_2horas_iniciar; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

                        <!-- 🩸 Tabla 5: Signos Vitales a 1 Hora -->
                        <h3>Signos Despues de 3 Hora</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>PA 3h</th>
                        <th>FC 3h</th>
                        <th>TA 3h</th>
                        <th>FR 3h</th>
                        <th>SPO2 3h</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->pa_3horas_iniciar; ?></td>
                            <td><?php echo $f->fc_3horas_iniciar; ?></td>
                            <td><?php echo $f->ta_3horas_iniciar; ?></td>
                            <td><?php echo $f->fr_3horas_iniciar; ?></td>
                            <td><?php echo $f->spo2_3horas_iniciar; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
        <br>
    </div>
</div>


<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("btnGuardar").addEventListener("click", function() {
        enviarTransfusion();
    });
});

function enviarTransfusion() {
    console.log("🚀 Función enviarTransfusion() ejecutada.");

    let formData = new FormData();
document.querySelectorAll("input, textarea").forEach(input => {
    formData.append(input.name, input.value.trim());
});
    
    let camposObligatorios = [
        'idpa', 'tipo_rh', 'diagnostico_hemoderivados', 'medico_tratante_hemoderivados',
        'enfermero_responsable_hemoderivados', 'cantidad_unidades_hemoderivados', 
        'hora_inicio_hemoderivados', 'hora_finalizacion_hemoderivados', 
        'pa_antes_transfundir', 'fc_antes_transfundir', 'ta_antes_transfundir',
        'fr_antes_transfundir', 'spo2_antes_transfundir'
    ];

    let faltantes = [];

    camposObligatorios.forEach(function(campo) {
        let valor = document.getElementById(campo)?.value.trim();
        if (!valor) faltantes.push(campo);
        formData.append(campo, valor);
    });

    if (faltantes.length > 0) {
        console.error("❌ Faltan los siguientes campos:", faltantes);
        Swal.fire('Error', 'Faltan los siguientes campos: ' + faltantes.join(', '), 'error');
        return;
    }

    console.log("📤 Enviando datos:", Array.from(formData.entries()));

    $.ajax({
        type: 'POST',
        url: 'add_transfusion.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            console.log("✅ Respuesta del servidor:", response);
            if (response.warning) {
                Swal.fire("Advertencia", response.message, "warning");
            } else if (response.success) {
                Swal.fire('Agregado correctamente', 'Buen trabajo', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function (xhr, status, error) {
            console.error("❌ Error en AJAX:", xhr.responseText);
            Swal.fire('Error', 'No se pudo agregar el registro', 'error');
        }
    });
}

// Descargar PDF de la solicitud de alta
function descargarTransfusionPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay motivo guardado antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_transfusion_hemoderivados.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si existe un motivo, generar el PDF
                    window.open(`generate_transfusión_hemoderivados.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay motivo guardado
                    Swal.fire('Advertencia', 'No se puede generar la hoja solicitada ya que no has registrado los datos para completar la solicitud.', 'warning');
                }
            } else {
                Swal.fire('Error', response.message || 'Hubo un problema al verificar el motivo.', 'error');
            }
        },
        error: function (xhr) {
            Swal.fire('Error', 'No se pudo verificar el motivo. Intente nuevamente más tarde.', 'error');
        }
    });
}

</script>




    </div>


    
</div>

        <?php endforeach; ?>
  
    <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>


        </main>
        <!-- MAIN -->
    </section>
   <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>


    <!-- NAVBAR -->
    
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="../../backend/js/vpat.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

<script>
var acc = document.getElementsByClassName("accordion");
var i;

for (i = 0; i < acc.length; i++) {
  acc[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var panel = this.nextElementSibling;
    if (panel.style.display === "block") {
      panel.style.display = "none";
    } else {
      panel.style.display = "block";
    }
  });
}
</script>

<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/modal/md_geog.php' ?>
<?php include_once '../../backend/modal/md_consul.php' ?>
<?php include_once '../../backend/modal/md_trat.php' ?>
<?php include_once '../../backend/modal/md_temperatura.php' ?>
<?php include_once '../../backend/modal/md_san.php' ?>
<?php include_once '../../backend/modal/md_transfusion_hemoderivados.php' ?>


</body>
</html>


