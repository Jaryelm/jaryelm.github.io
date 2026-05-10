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

           <!-- multistep form -->
           <button class="button" onclick="cambiarColor(this, '../pacientes/nuevo.php')">Registro de Pacientes</button>
           <button class="button" onclick="cambiarColor(this, '../pacientes/historial.php')">Expediente Clínico</button>
           <button class="button" onclick="cambiarColor(this, '../pacientes/documentos.php')">Agregar Documentos</button>
           <button class="button" onclick="cambiarColor(this, '../citas/nueva.php')">Nueva Cita</button>
           <button class="button" onclick="cambiarColor(this, '../citas/calendario.php')">Calendario de Citas</button>

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

<button class="accordion">Gastos Quirófano</button>
<div class="panel">
    <div class="botons-modal">
        <button class="register-btn" onclick="descargarGastosQuirofanoPDF()">Descargar Hoja de Gastos Quirófano</button>
        <label for="gastosquirofano-modal">
            Registrar
        </label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM gastos_quirofano WHERE idpa= :id");
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
            <h3>Datos Generales</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Médico Anestesiólogo</th>
                        <th scope="col">Cirujano Principal</th>
                        <th scope="col">Procesado por</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Médico Referente"><?php echo $f->medico_referente; ?></td>
                            <td data-title="Cirujano Principal"><?php echo $f->cirujano_principal; ?></td>
                            <td data-title="Procesado por"><?php echo $f->procesado_por; ?></td>
                            <td data-title="Fecha"><?php echo $f->created_at; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Insumos y Material Descartable</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Insumo</th>
                        <th scope="col">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->insumo_material_descartable; ?></td>
                            <td><?php echo $f->cantidad_material_descartable; ?></td> 
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Medicamentos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Insumo</th>
                        <th scope="col">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->insumo_medicamentos; ?></td>
                            <td><?php echo $f->cantidad_medicamentos; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Anestésicos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Insumo</th>
                        <th scope="col">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->insumo_anestesicos; ?></td>
                            <td><?php echo $f->cantidad_anestesicos; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Equipo Médico Quirúrgico</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Insumo</th>
                        <th scope="col">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->insumo_equipo_medico; ?></td>
                            <td><?php echo $f->cantidad_equipo_medico; ?></td>
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

<script type="text/javascript">
function enviarGasto() {
    // Capturar valores y asignar "No Registrado" si están vacíos
    var idpa = document.getElementById('idpa')?.value || "No Registrado";
    var procesado_por = document.getElementById('procesado_por')?.value || "No Registrado";
    var medico_referente = document.getElementById('medico_referente')?.value || "No Registrado";
    var cirujano_principal = document.getElementById('cirujano_principal')?.value || "No Registrado";

    var insumo_material_descartable = document.getElementById('insumo_material_descartable')?.value || "No Registrado";
    var cantidad_material_descartable = document.getElementById('cantidad_material_descartable')?.value || "No Registrado";

    var insumo_medicamentos = document.getElementById('insumo_medicamentos')?.value || "No Registrado";
    var cantidad_medicamentos = document.getElementById('cantidad_medicamentos')?.value || "No Registrado";

    var insumo_anestesicos = document.getElementById('insumo_anestesicos')?.value || "No Registrado";
    var cantidad_anestesicos = document.getElementById('cantidad_anestesicos')?.value || "No Registrado";

    var insumo_equipo_medico = document.getElementById('insumo_equipo_medico')?.value || "No Registrado";
    var cantidad_equipo_medico = document.getElementById('cantidad_equipo_medico')?.value || "No Registrado";

    // Construcción del objeto de datos para AJAX
    var dataen = {
        idpa: idpa,
        procesado_por: procesado_por,
        medico_referente: medico_referente,
        cirujano_principal: cirujano_principal,
        insumo_material_descartable: insumo_material_descartable,
        cantidad_material_descartable: cantidad_material_descartable,
        insumo_medicamentos: insumo_medicamentos,
        cantidad_medicamentos: cantidad_medicamentos,
        insumo_anestesicos: insumo_anestesicos,
        cantidad_anestesicos: cantidad_anestesicos,
        insumo_equipo_medico: insumo_equipo_medico,
        cantidad_equipo_medico: cantidad_equipo_medico
    };

    console.log("Datos enviados en AJAX:", dataen); // Depuración en consola

    // Enviar los datos mediante AJAX
    $.ajax({
        type: "POST",
        url: "add_gastos_quirofano.php",
        data: dataen,
        cache: false,
        success: function (response) {
            console.log("Respuesta del servidor:", response);
            if (response.success) {
                swal("Éxito", "Gasto de quirófano registrado correctamente", "success")
                .then(() => {
                    window.location.reload();
                });
            } else {
                swal("Error", response.error || "No se pudo registrar el gasto", "error");
            }
        },
        error: function (xhr) {
            swal("Error", "No se pudo registrar el gasto. Intente nuevamente.", "error");
            console.error("Error en AJAX:", xhr);
        }
    });
}
</script>

<script>
function descargarGastosQuirofanoPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay datos antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_gastos_quirofano.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si hay datos, generar el PDF
                    window.open(`gastos_quirurgico_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay registros
                    swal('Advertencia', 'No se puede generar el PDF porque no hay datos registrados.', 'warning');
                }
            } else {
                swal('Error', response.message || 'Hubo un problema al verificar los datos.', 'error');
            }
        },
        error: function (xhr) {
            swal('Error', 'No se pudo verificar los datos. Intente nuevamente más tarde.', 'error');
        }
    });
}
</script>

<button class="accordion">Periodo Post Operativo</button>
<div class="panel">
    <div class="botons-modal">
        <button class="register-btn" onclick="descargarPostOperativoPDF()">Descargar Período Post Operativo</button>
        <label for="postoperativo-modal">
            Registrar
        </label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM periodo_post_operativo WHERE idpa= :id");
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
            <h3>Evaluación del Riesgo de Caídas (Escala Crischton)</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Riesgo de Caídas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Riesgo de Caídas"><?php echo $f->riesgo_caidas; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

<h3>Medidas de Seguridad Utilizadas en la Prevención de Caídas</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th scope="col">Medidas de Seguridad</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $f): ?>
            <tr>
                <td data-title="Medidas de Seguridad">
                    <?php 
                    $medidas_guardadas = !empty($f->medidas_seguridad) ? json_decode($f->medidas_seguridad, true) : [];
                    echo (!empty($medidas_guardadas)) ? htmlspecialchars(implode(", ", $medidas_guardadas), ENT_QUOTES, 'UTF-8') : "No se seleccionaron medidas."; 
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


            <h3>Evaluación del Dolor (Escala 1 al 10)</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Hora</th>
                        <th scope="col">Grado</th>
                        <th scope="col">Localización</th>
                        <th scope="col">Actividad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Hora"><?php echo $f->hora_dolor; ?></td>
                            <td data-title="Grado"><?php echo $f->grado_dolor; ?></td>
                            <td data-title="Localización"><?php echo $f->localizacion_dolor; ?></td>
                            <td data-title="Actividad"><?php echo $f->actividad_dolor; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Escala de Valoración de Aldrete</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th scope="col">Actividad Muscular</th>
            <th scope="col">Respiración</th>
            <th scope="col">Circulación</th>
            <th scope="col">Estado de Conciencia</th>
            <th scope="col">Coloración</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $f): ?>
            <tr>
                <td data-title="Actividad Muscular"><?php echo $f->actividad_muscular ?? 'No registrado'; ?></td>
                <td data-title="Respiración"><?php echo $f->respiracion ?? 'No registrado'; ?></td>
                <td data-title="Circulación"><?php echo $f->circulacion ?? 'No registrado'; ?></td>
                <td data-title="Estado de Conciencia"><?php echo $f->estado_conciencia ?? 'No registrado'; ?></td>
                <td data-title="Coloración"><?php echo $f->coloracion ?? 'No registrado'; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Sala de Recuperación</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th scope="col">De Alta</th>
            <th scope="col">Hora Alta</th>
            <th scope="col">A Su Cuarto</th>
            <th scope="col">A Su Domicilio</th>
            <th scope="col">Tiempos</th>
            <th scope="col">Procesado por</th>
            <th scope="col">Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $f): ?>
            <tr>
                <td data-title="De Alta">
                <?php 
                    $valor_alta = ($f->alta_si === 'Si') ? 'Si' : (($f->alta_no === 'Si') ? 'No' : 'No registrado');
                    echo htmlspecialchars($valor_alta, ENT_QUOTES, 'UTF-8'); 
                    ?>
                </td>
                <td data-title="Hora Alta"><?php echo htmlspecialchars($f->hora_alta, ENT_QUOTES, 'UTF-8'); ?></td>
                <td data-title="A Su Cuarto"><?php echo htmlspecialchars($f->a_cuarto, ENT_QUOTES, 'UTF-8'); ?></td>
                <td data-title="A Su Domicilio"><?php echo htmlspecialchars($f->a_domicilio, ENT_QUOTES, 'UTF-8'); ?></td>
                <td data-title="Tiempos">
                    <?php 
                    $sala_recuperacion = !empty($f->sala_recuperacion) ? json_decode($f->sala_recuperacion, true) : [];

                    if (!empty($sala_recuperacion)) {
                        echo "<ul style='padding-left: 20px; list-style-type: square;'>";
                        foreach ($sala_recuperacion as $tiempo => $valores) {
                            echo "<li><strong>" . ucfirst(str_replace("_", " ", $tiempo)) . ":</strong> " . implode(", ", $valores) . "</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "No se registraron tiempos.";
                    }
                    ?>
                </td>
                <td data-title="Procesado por"><?php echo htmlspecialchars($f->procesado_por, ENT_QUOTES, 'UTF-8'); ?></td>
                <td data-title="Fecha"><?php echo htmlspecialchars($f->created_at, ENT_QUOTES, 'UTF-8'); ?></td>
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

<script type="text/javascript">
function getSelectedText(name) {
    var selected = document.querySelector(`input[name="${name}"]:checked`);
    return selected ? selected.value : ''; // Devuelve el texto completo
}

function enviarPostOperativo() {
    var formData = new FormData();

    // Capturar valores básicos
    formData.append("idpa", document.getElementById("idpa")?.value || '');
    formData.append("procesado_por", document.getElementById("procesado_por")?.value || '');
    
    formData.append("riesgo_caidas", document.querySelector('input[name="riesgo_caidas"]:checked')?.value || '');

    // Capturar medidas de seguridad seleccionadas y estructurarlas en JSON
    var medidas_seguridad = Array.from(document.querySelectorAll('input[name="medidas_seguridad[]"]:checked'))
        .map(el => el.value);
    formData.append("medidas_seguridad", JSON.stringify(medidas_seguridad));

    // Evaluación del Dolor
    formData.append("hora_dolor", document.getElementsByName('hora_dolor')[0]?.value || '');
    formData.append("grado_dolor", document.getElementsByName('grado_dolor')[0]?.value || '');
    formData.append("localizacion_dolor", document.getElementsByName('localizacion_dolor')[0]?.value || '');
    formData.append("actividad_dolor", document.getElementsByName('actividad_dolor')[0]?.value || '');

    // ✅ Escala de Valoración de Aldrete (Ahora guarda el texto completo)
    formData.append("actividad_muscular", getSelectedText("actividad_muscular"));
    formData.append("respiracion", getSelectedText("respiracion"));
    formData.append("circulacion", getSelectedText("circulacion"));
    formData.append("estado_conciencia", getSelectedText("estado_conciencia"));
    formData.append("coloracion", getSelectedText("coloracion"));

    // Sala de Recuperación - Capturar valores de cada grupo de 8 inputs
    var salaRecuperacionData = {};
    var tiempos = ["al_salir", "20_minutos", "60_minutos", "90_minutos", "120_minutos"];

    tiempos.forEach(tiempo => {
        var valores = Array.from(document.querySelectorAll(`input[name="sala_recuperacion[${tiempo}][]"]`))
            .map(el => el.value.trim());
        
        salaRecuperacionData[tiempo] = valores;
    });

    formData.append("sala_recuperacion", JSON.stringify(salaRecuperacionData));

    // Alta
    formData.append("hora_alta", document.getElementsByName('hora_alta')[0]?.value || '');
    formData.append("alta_si", document.querySelector('input[name="alta_si"]:checked')?.value || '');
    formData.append("alta_no", document.querySelector('input[name="alta_no"]:checked')?.value || '');
    formData.append("a_cuarto", document.querySelector('input[name="a_cuarto"]:checked')?.value || '');
    formData.append("a_domicilio", document.querySelector('input[name="a_domicilio"]:checked')?.value || '');

    // Validación de campos obligatorios
    if (!formData.get("idpa") || !formData.get("procesado_por")) {
        swal("Error", "ID del paciente y procesado por son obligatorios", "error");
        return;
    }

    $.ajax({
        type: "POST",
        url: "add_post_operatorio.php",
        data: formData,
        processData: false,
        contentType: false,
        cache: false,
        success: function (result) {
            swal("Éxito", "Datos de post operatorio registrados correctamente", "success")
                .then(() => window.location.reload());
        },
        error: function () {
            swal("Error", "No se pudieron registrar los datos", "error");
        }
    });
}
</script>

<script>
function descargarPostOperativoPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay datos antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_post_operatorio.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si hay datos, generar el PDF
                    window.open(`generate_post_operatorio_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay registros
                    swal('Advertencia', 'No se puede generar el PDF porque no hay datos registrados.', 'warning');
                }
            } else {
                swal('Error', response.message || 'Hubo un problema al verificar los datos.', 'error');
            }
        },
        error: function (xhr) {
            swal('Error', 'No se pudo verificar los datos. Intente nuevamente más tarde.', 'error');
        }
    });
}
</script>


<button class="accordion">Recuperación</button>
<div class="panel">
    <div class="botons-modal">
        <button class="register-btn" onclick="descargarRecuperacionPDF()">Descargar Hoja de Recuperación</button>
        <label for="recuperacion-modal">
            Registrar
        </label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM recuperacion WHERE idpa = :id");
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
            <h3>Datos Generales</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Diagnóstico</th>
                        <th>Cirujano Realizada</th>
                        <th>Cirujano Principal</th>
                        <th>Anestesista</th>
                        <th>Tipo de Anestesia</th>
                        <th>Fecha</th>
                        <th>Hora Inicio</th>
                        <th>Hora Fin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->diagnostico; ?></td>
                            <td><?php echo $f->cirujano_realizada; ?></td>
                            <td><?php echo $f->cirujano_principal; ?></td>
                            <td><?php echo $f->anestesista; ?></td>
                            <td><?php echo $f->tipo_anestesia; ?></td>
                            <td><?php echo $f->fecha; ?></td>
                            <td><?php echo $f->hora_inicio_cirugia; ?></td>
                            <td><?php echo $f->hora_fin_cirugia; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Cuidados Post Operatorios Inmediatos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Reflejos</th>
                        <th>Canula Endotraqueal</th>
                        <th>Oxígeno</th>
                        <th>Sonda Foley</th>
                        <th>Sonda NSG</th>
                        <th>CVP</th>
                        <th>CVC</th>
                        <th>Drenos</th>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->reflejos; ?></td>
                            <td><?php echo $f->canula_endotraqueal; ?></td>
                            <td><?php echo $f->oxigeno; ?></td>
                            <td><?php echo $f->sonda_foley; ?></td>
                            <td><?php echo $f->sonda_nsg; ?></td>
                            <td><?php echo $f->cvp; ?></td>
                            <td><?php echo $f->cvc; ?></td>
                            <td><?php echo $f->drenos; ?></td>
                            <td><?php echo $f->tipo_cuidado; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Signos Vitales</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <th>PA</th>
                        <th>FC</th>
                        <th>FR</th>
                        <th>TA</th>
                        <th>SPO2</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->hora_signos; ?></td>
                            <td><?php echo $f->pa_signos; ?></td>
                            <td><?php echo $f->fc_signos; ?></td>
                            <td><?php echo $f->fr_signos; ?></td>
                            <td><?php echo $f->ta_signos; ?></td>
                            <td><?php echo $f->spo2_signos; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Medicamentos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Medicamento</th>
                        <th>Dosis</th>
                        <th>Via</th>
                        <th>Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->medicamento; ?></td>
                            <td><?php echo $f->dosis; ?></td>
                            <td><?php echo $f->via; ?></td>
                            <td><?php echo $f->hora_medicamento; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Control de Líquidos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Orales</th>
                        <th>I/V</th>
                        <th>Orina</th>
                        <th>Vómitos</th>
                        <th>Succión</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->ingestas_orales; ?></td>
                            <td><?php echo $f->ingestas_iv; ?></td>
                            <td><?php echo $f->excretas_orina; ?></td>
                            <td><?php echo $f->excretas_vomitos; ?></td>
                            <td><?php echo $f->excretas_succion; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Observaciones de Enfermeria</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->observaciones; ?></td>
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
function enviarRecuperacion() {
    var formData = new FormData();

    // Obtener valores de los inputs
    formData.append("idpa", document.getElementById("csidpa").value);
    formData.append("diagnostico", document.getElementsByName("diagnostico")[0].value);
    formData.append("cirujano_realizada", document.getElementsByName("cirujano_realizada")[0].value);
    formData.append("cirujano_principal", document.getElementsByName("cirujano_principal")[0].value);
    formData.append("anestesista", document.getElementsByName("anestesista")[0].value);
    formData.append("tipo_anestesia", document.getElementsByName("tipo_anestesia")[0].value);
    formData.append("fecha", document.getElementsByName("fecha")[0].value);
    formData.append("hora_inicio_cirugia", document.getElementsByName("hora_inicio_cirugia")[0].value);
    formData.append("hora_fin_cirugia", document.getElementsByName("hora_fin_cirugia")[0].value);

    // Capturar valores de radios
    function getRadioValue(name) {
        var radios = document.getElementsByName(name);
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) return radios[i].value;
        }
        return "No"; // Si no está seleccionado, devuelve "No"
    }

    var cuidados = ["reflejos", "canula_endotraqueal", "oxigeno", "sonda_foley", "sonda_nsg", "cvp", "cvc", "drenos"];
    cuidados.forEach(cuidado => {
        formData.append(cuidado, getRadioValue(cuidado));
    });

    formData.append("tipo_cuidado", document.getElementsByName("tipo_cuidado")[0].value);
    formData.append("liquidos_infusion", document.getElementsByName("liquidos_infusion")[0].value);
    formData.append("cantidad_liquidos", document.getElementsByName("cantidad_liquidos")[0].value);
    formData.append("mezcla_liquidos", document.getElementsByName("mezcla_liquidos")[0].value);

    formData.append("hora_signos", document.getElementsByName("hora_signos")[0].value);
    formData.append("pa_signos", document.getElementsByName("pa_signos")[0].value);
    formData.append("fc_signos", document.getElementsByName("fc_signos")[0].value);
    formData.append("fr_signos", document.getElementsByName("fr_signos")[0].value);
    formData.append("ta_signos", document.getElementsByName("ta_signos")[0].value);
    formData.append("spo2_signos", document.getElementsByName("spo2_signos")[0].value);

    formData.append("medicamento", document.getElementsByName("medicamento")[0].value);
    formData.append("dosis", document.getElementsByName("dosis")[0].value);
    formData.append("via", document.getElementsByName("via")[0].value);
    formData.append("hora_medicamento", document.getElementsByName("hora_medicamento")[0].value);

    formData.append("ingestas_orales", document.getElementsByName("ingestas_orales")[0].value);
    formData.append("ingestas_iv", document.getElementsByName("ingestas_iv")[0].value);
    formData.append("excretas_orina", document.getElementsByName("excretas_orina")[0].value);
    formData.append("excretas_vomitos", document.getElementsByName("excretas_vomitos")[0].value);
    formData.append("excretas_succion", document.getElementsByName("excretas_succion")[0].value);
    formData.append("observaciones", document.getElementsByName("observaciones")[0].value);

    // Enviar datos con Fetch API
    fetch("add_recuperacion.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            swal("Éxito", "Datos guardados correctamente", "success")
                .then(() => window.location.reload());
        } else {
            swal("Error", result.error, "error");
        }
    })
    .catch(error => {
        console.error("Error en la petición:", error);
        swal("Error", "No se pudieron registrar los datos", "error");
    });
}
</script>

<script>
function descargarRecuperacionPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay datos antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_recuperacion.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si hay datos, generar el PDF
                    window.open(`generate_recuperacion_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay registros
                    swal('Advertencia', 'No se puede generar el PDF porque no hay datos registrados.', 'warning');
                }
            } else {
                swal('Error', response.message || 'Hubo un problema al verificar los datos.', 'error');
            }
        },
        error: function (xhr) {
            swal('Error', 'No se pudo verificar los datos. Intente nuevamente más tarde.', 'error');
        }
    });
}
</script>


<button class="accordion">Anestesia</button>
<div class="panel">
    <div class="botons-modal">
        <button class="register-btn" onclick="descargarAnestesiaPDF()">Descargar Hoja de Anestesia</button>
        <label for="anestesia-modal">
            Registrar
        </label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM anestesia WHERE idpa = :id");
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
            <h3>Datos Generales</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Tiempo de Anestesia</th>
                        <th>Observaciones</th>
                        <th>Procesado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f->tiempo_anestesia, ENT_QUOTES, 'UTF-8'); ?> min</td>
                            <td><?php echo htmlspecialchars($f->observaciones, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->procesado_por, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Variables Monitorizadas</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Temperatura (°C)</th>
                        <th>T.A. (mmHg)</th>
                        <th>Pulso</th>
                        <th>Frecuencia Resp.</th>
                        <th>Frecuencia Card.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f->temp, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->tension_arterial, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->pulso, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->frecuencia_respiratoria, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->frecuencia_cardiaca, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Procedimientos Anestésicos</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th>Diagnóstico</th>
            <th>Operación Realizada</th>
            <th>Método y Técnica Anestésica</th>
            <th>Mascarilla</th>
            <th>Cánula</th>
            <th>Tubo Endotraqueal</th>
            <th>Globo Inflable</th>
            <th>Complicaciones</th>
            <th>Sangre y Soluciones</th>
            <th>Fármacos y Soluciones Administradas</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $f): ?>
            <tr>
                <td><?php echo htmlspecialchars($f->diagnostico, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->operacion, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->metodo_anestesia, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->mascarilla, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->canula, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->tubo_endotraqueal, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->globo_inflable, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($f->complicaciones, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo nl2br(htmlspecialchars($f->sangre_soluciones, ENT_QUOTES, 'UTF-8')); ?></td>
                <td><?php echo nl2br(htmlspecialchars($f->medicamentos, ENT_QUOTES, 'UTF-8')); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

            <h3>Casos Obstétricos (Si Aplica)</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f->caso_obstetrico, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Datos del Recién Nacido (Si Aplica)</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Hora de Nacimiento</th>
                        <th>Sexo</th>
                        <th>Peso (kg)</th>
                        <th>Talla (cm)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f->nombre_recien_nacido, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->hora_nacimiento, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->sexo, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->peso, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->talla, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Personal Médico Asignado</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Anestesiólogo</th>
                        <th>Clave</th>
                        <th>Cirujano</th>
                        <th>Ayudante</th>
                        <th>Instrumentista</th>
                        <th>Circulante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f->anestesiologo, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->clave, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->cirujano, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->ayudante, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->instrumentista, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->circulante, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <p class="alert alert-warning">No hay datos registrados</p>
        <?php endif; ?>
        <br>
    </div>
</div>


<script>
function enviarAnestesia() {
    var formData = new FormData();

    // Verificar todos los IDs antes de acceder a ellos
    console.log("Verificando elementos en el formulario...");
    var ids = [
        "idpa", "tiempo_anestesia", "temp", "tension_arterial", "pulso",
        "frecuencia_respiratoria", "frecuencia_cardiaca", "diagnostico",
        "operacion", "metodo_anestesia", "tubo_endotraqueal", "globo_inflable",
        "medicamentos", "caso_obstetrico", "nombre_recien_nacido", "hora_nacimiento",
        "sexo", "peso", "talla", "anestesiologo", "clave", "cirujano", "ayudante",
        "instrumentista", "circulante", "observaciones", "procesado_por"
    ];

    ids.forEach(id => {
        var element = document.getElementById(id);
        if (!element) {
            console.error("⚠ ERROR: Elemento con ID '" + id + "' no encontrado en el formulario.");
        }
    });

    // Detener la ejecución si faltan elementos
    if (ids.some(id => !document.getElementById(id))) {
        alert("Error: Hay campos faltantes en el formulario. Revisa la consola.");
        return;
    }

    // Continúa con el código solo si todos los campos existen
    formData.append("idpa", document.getElementById("idpa").value);
    formData.append("tiempo_anestesia", document.getElementById("tiempo_anestesia").value);
    formData.append("temp", document.getElementById("temp").value);
    formData.append("tension_arterial", document.getElementById("tension_arterial").value);
    formData.append("pulso", document.getElementById("pulso").value);
    formData.append("frecuencia_respiratoria", document.getElementById("frecuencia_respiratoria").value);
    formData.append("frecuencia_cardiaca", document.getElementById("frecuencia_cardiaca").value);
    formData.append("diagnostico", document.getElementById("diagnostico").value);
    formData.append("operacion", document.getElementById("operacion").value);
    formData.append("metodo_anestesia", document.getElementById("metodo_anestesia").value);
    formData.append("tubo_endotraqueal", document.getElementById("tubo_endotraqueal") ? document.getElementById("tubo_endotraqueal").value.trim() : "");
    formData.append("globo_inflable", document.getElementById("globo_inflable") ? document.getElementById("globo_inflable").value.trim() : "");
    formData.append("medicamentos", document.getElementById("medicamentos").value.trim());
    formData.append("caso_obstetrico", document.getElementById("caso_obstetrico").value);
    formData.append("nombre_recien_nacido", document.getElementById("nombre_recien_nacido").value.trim());

    var horaNacimiento = document.getElementById("hora_nacimiento").value;
    formData.append("hora_nacimiento", horaNacimiento ? horaNacimiento : "00:00");

    formData.append("sexo", document.getElementById("sexo").value);
    formData.append("peso", document.getElementById("peso").value.trim());
    formData.append("talla", document.getElementById("talla").value.trim());

    formData.append("anestesiologo", document.getElementById("anestesiologo").value.trim());
    formData.append("clave", document.getElementById("clave").value.trim());
    formData.append("cirujano", document.getElementById("cirujano").value.trim());
    formData.append("ayudante", document.getElementById("ayudante").value.trim());
    formData.append("instrumentista", document.getElementById("instrumentista").value.trim());
    formData.append("circulante", document.getElementById("circulante").value.trim());
    formData.append("observaciones", document.getElementById("observaciones").value.trim());
    formData.append("procesado_por", document.getElementById("procesado_por").value.trim());
    formData.append("sangre_soluciones", document.getElementById("sangre_soluciones").value.trim());

    // Enviar datos con Fetch API
    fetch("add_anestesia.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            swal("Éxito", "Registro guardado correctamente", "success")
                .then(() => window.location.reload());
        } else {
            swal("Error", result.error, "error");
        }
    })
    .catch(error => {
        console.error("Error en la petición:", error);
        swal("Error", "No se pudieron registrar los datos", "error");
    });
}
</script>

<script>
    function descargarAnestesiaPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay datos antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_anestesia.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasData) {
                    // Si hay datos, generar el PDF
                    window.open(`generate_anestesia_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay registros
                    swal('Advertencia', 'No se puede generar el PDF porque no hay datos registrados.', 'warning');
                }
            } else {
                swal('Error', response.message || 'Hubo un problema al verificar los datos.', 'error');
            }
        },
        error: function (xhr) {
            swal('Error', 'No se pudo verificar los datos. Intente nuevamente más tarde.', 'error');
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<?php include_once '../../backend/modal/md_gastos_quirofano.php' ?>
<?php include_once '../../backend/modal/md_post_operatorio.php' ?>
<?php include_once '../../backend/modal/md_recuperacion.php' ?>
<?php include_once '../../backend/modal/md_medicamento_medifarma.php' ?>
<?php include_once '../../backend/modal/md_anestesia.php' ?>

</body>
</html>