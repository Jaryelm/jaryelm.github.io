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
            <h3>Material Descartable</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Insumo</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Procesado por</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Insumo"><?php echo $f->insumo_material_descartable; ?></td>
                            <td data-title="Cantidad"><?php echo $f->cantidad_material_descartable; ?></td> 
                            <td data-title="Cantidad"><?php echo $f->procesado_por; ?></td>
                            <td data-title="Cantidad"><?php echo $f->created_at; ?></td>
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
                        <th scope="col">Procesado por</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Insumo"><?php echo $f->insumo_medicamentos; ?></td>
                            <td data-title="Cantidad"><?php echo $f->cantidad_medicamentos; ?></td>
                            <td data-title="Cantidad"><?php echo $f->procesado_por; ?></td>
                            <td data-title="Cantidad"><?php echo $f->created_at; ?></td>
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
        var insumo_material_descartable = document.getElementById('insumo_material_descartable').value;
        var cantidad_material_descartable = document.getElementById('cantidad_material_descartable').value;
        var insumo_medicamentos = document.getElementById('insumo_medicamentos').value;
        var cantidad_medicamentos = document.getElementById('cantidad_medicamentos').value;
        var idpa = document.getElementById('idpa').value;
        var procesado_por = document.getElementById('procesado_por').value;

        if (!insumo_material_descartable || !cantidad_material_descartable || !insumo_medicamentos || !cantidad_medicamentos) {
            Swal.fire("Error", "Todos los campos son obligatorios", "error");
            return;
        }

        var dataen = 
            'insumo_material_descartable=' + encodeURIComponent(insumo_material_descartable) + 
            '&cantidad_material_descartable=' + encodeURIComponent(cantidad_material_descartable) + 
            '&insumo_medicamentos=' + encodeURIComponent(insumo_medicamentos) + 
            '&cantidad_medicamentos=' + encodeURIComponent(cantidad_medicamentos) + 
            '&idpa=' + encodeURIComponent(idpa) + 
            '&procesado_por=' + encodeURIComponent(procesado_por);

        $.ajax({
            type: "POST",
            url: "add_gastos_quirofano.php",
            data: dataen,
            cache: false,
            success: function (result) {
                Swal.fire("Éxito", "Gasto de quirófano registrado correctamente", "success")
                .then(() => {
                    window.location.reload();
                });
            },
            error: function () {
                Swal.fire("Error", "No se pudo registrar el gasto", "error");
            }
        });
    }
</script>

<button class="accordion">Periodo Post Operativo</button>
<div class="panel">
    <div class="botons-modal">
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
            <h3>Evaluación del Riesgo de Caídas</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Riesgo de Caídas</th>
                        <th scope="col">Procesado por</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Riesgo de Caídas"><?php echo $f->riesgo_caidas; ?></td>
                            <td data-title="Procesado por"><?php echo $f->procesado_por; ?></td>
                            <td data-title="Fecha"><?php echo $f->created_at; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Medidas de Seguridad</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Medidas de Seguridad</th>
                        <th scope="col">Procesado por</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
<td data-title="Medidas de Seguridad">
    <?php 
    $medidas_guardadas = json_decode($f->medidas_seguridad, true);
    echo (!empty($medidas_guardadas)) ? implode(", ", $medidas_guardadas) : "No se seleccionaron medidas."; 
    ?>
</td>

                            <td data-title="Procesado por"><?php echo $f->procesado_por; ?></td>
                            <td data-title="Fecha"><?php echo $f->created_at; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Evaluación del Dolor</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Hora</th>
                        <th scope="col">Grado</th>
                        <th scope="col">Localización</th>
                        <th scope="col">Actividad</th>
                        <th scope="col">Procesado por</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td data-title="Hora"><?php echo $f->hora_dolor; ?></td>
                            <td data-title="Grado"><?php echo $f->grado_dolor; ?></td>
                            <td data-title="Localización"><?php echo $f->localizacion_dolor; ?></td>
                            <td data-title="Actividad"><?php echo $f->actividad_dolor; ?></td>
                            <td data-title="Procesado por"><?php echo $f->procesado_por; ?></td>
                            <td data-title="Fecha"><?php echo $f->created_at; ?></td>
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
            <th scope="col">Procesado por</th>
            <th scope="col">Fecha</th>
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
                <td data-title="Procesado por"><?php echo $f->procesado_por; ?></td>
                <td data-title="Fecha"><?php echo $f->created_at; ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

            <h3>Sala de Recuperación</h3>
<table class="responsive-table">
    <thead>
        <tr>
            <th scope="col">Hora Alta</th>
            <th scope="col">Valores</th>
            <th scope="col">Procesado por</th>
            <th scope="col">Fecha</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $f): ?>
            <tr>
                <td data-title="Hora Alta"><?php echo $f->hora_alta; ?></td>
                <td data-title="Valores"><?php echo $f->sala_recuperacion; ?></td>
                <td data-title="Procesado por"><?php echo $f->procesado_por; ?></td>
                <td data-title="Fecha"><?php echo $f->created_at; ?></td>
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
    function enviarPostOperativo() {
        var riesgo_caidas = document.querySelector('input[name="riesgo_caidas"]:checked')?.value || '';
        var medidas_seguridad = Array.from(document.querySelectorAll('input[name="medidas_seguridad[]"]:checked')).map(el => el.value).join(", ");
        var hora_dolor = document.getElementsByName('hora_dolor')[0]?.value || '';
        var grado_dolor = document.getElementsByName('grado_dolor')[0]?.value || '';
        var localizacion_dolor = document.getElementsByName('localizacion_dolor')[0]?.value || '';
        var actividad_dolor = document.getElementsByName('actividad_dolor')[0]?.value || '';
        var actividad_muscular = document.querySelector('input[name="actividad_muscular"]:checked')?.value || '';
        var respiracion = document.querySelector('input[name="respiracion"]:checked')?.value || '';
        var circulacion = document.querySelector('input[name="circulacion"]:checked')?.value || '';
        var estado_conciencia = document.querySelector('input[name="estado_conciencia"]:checked')?.value || '';
        var coloracion = document.querySelector('input[name="coloracion"]:checked')?.value || '';
        var hora_alta = document.getElementsByName('hora_alta')[0]?.value || '';
        var alta_si = document.querySelector('input[name="alta_si"]:checked')?.value || '';
        var alta_no = document.querySelector('input[name="alta_no"]:checked')?.value || '';
        var a_cuarto = document.querySelector('input[name="a_cuarto"]:checked')?.value || '';
        var a_domicilio = document.querySelector('input[name="a_domicilio"]:checked')?.value || '';
        var idpa = document.getElementById('idpa')?.value || '';
        var procesado_por = document.getElementById('procesado_por')?.value || '';

        if (!riesgo_caidas || !hora_dolor || !grado_dolor || !localizacion_dolor || !actividad_dolor || !hora_alta) {
            Swal.fire("Error", "Todos los campos obligatorios deben ser llenados", "error");
            return;
        }

        var dataen = 
            'riesgo_caidas=' + encodeURIComponent(riesgo_caidas) + 
            '&medidas_seguridad=' + encodeURIComponent(medidas_seguridad) + 
            '&hora_dolor=' + encodeURIComponent(hora_dolor) + 
            '&grado_dolor=' + encodeURIComponent(grado_dolor) + 
            '&localizacion_dolor=' + encodeURIComponent(localizacion_dolor) + 
            '&actividad_dolor=' + encodeURIComponent(actividad_dolor) + 
            '&actividad_muscular=' + encodeURIComponent(actividad_muscular) + 
            '&respiracion=' + encodeURIComponent(respiracion) + 
            '&circulacion=' + encodeURIComponent(circulacion) + 
            '&estado_conciencia=' + encodeURIComponent(estado_conciencia) + 
            '&coloracion=' + encodeURIComponent(coloracion) + 
            '&hora_alta=' + encodeURIComponent(hora_alta) + 
            '&alta_si=' + encodeURIComponent(alta_si) + 
            '&alta_no=' + encodeURIComponent(alta_no) + 
            '&a_cuarto=' + encodeURIComponent(a_cuarto) + 
            '&a_domicilio=' + encodeURIComponent(a_domicilio) + 
            '&idpa=' + encodeURIComponent(idpa) + 
            '&procesado_por=' + encodeURIComponent(procesado_por);

        $.ajax({
            type: "POST",
            url: "add_post_operatorio.php",
            data: dataen,
            cache: false,
            success: function (result) {
                Swal.fire("Éxito", "Datos de post operatorio registrados correctamente", "success")
                .then(() => {
                    window.location.reload();
                });
            },
            error: function () {
                Swal.fire("Error", "No se pudieron registrar los datos", "error");
            }
        });
    }
</script>

    <button class="accordion">Recuperación</button>
<div class="panel">
    <div class="botons-modal">
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

            <h3>Observaciones</h3>
            <p><?php echo $data[0]->observaciones; ?></p>

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
            Swal.fire("Éxito", "Datos guardados correctamente", "success")
                .then(() => window.location.reload());
        } else {
            Swal.fire("Error", result.error, "error");
        }
    })
    .catch(error => {
        console.error("Error en la petición:", error);
        Swal.fire("Error", "No se pudieron registrar los datos", "error");
    });
}
</script>

<button class="accordion">Anestesia</button>
<div class="panel">
    <div class="botons-modal">
        <label for="btns-modal">
            Registrar
        </label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM consult WHERE idpa= :id");
        $sentencia->bindParam(':id', $id, PDO::PARAM_INT);
        $sentencia->execute();
        $data =  array();
        if ($sentencia) {
            while ($r = $sentencia->fetchObject()) {
                $data[] = $r;
            }
        }
        ?>
        <?php if (count($data) > 0): ?>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Paciente</th>

                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <th scope="row"><?php echo $f->nompa; ?></th>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
    </div>
</div>

<button class="accordion">Farmacia (MEDIFARMA)</button>
<div class="panel">
    <div class="botons-modal">
        <label for="medifarma-modal">
            Registrar
        </label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM medicamentos_medifarma WHERE idpa = :id");
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
                        <th>Médico Operando</th>
                        <th>Cirugía a Realizar</th>
                        <th>Nombre Solicitante</th>
                        <th>Procesado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($f->medico_operando, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->cirugia_realizar, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->nombre_solicitante, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->procesado_por, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h3>Medicamento y Material Quirúrgico</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Medicamentos</th>
                        <th>Material</th>
                        <th>Cantidad</th>
                        <th>Procesado Por</th>
                        <th>Fecha de Registro</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo nl2br(htmlspecialchars($f->medicamentos, ENT_QUOTES, 'UTF-8')); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($f->material, ENT_QUOTES, 'UTF-8')); ?></td>
                            <td><?php echo htmlspecialchars($f->cantidad, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->procesado_por, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($f->created_at, ENT_QUOTES, 'UTF-8'); ?></td>
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
function enviarMedifarma() {
    var formData = new FormData();

    // Obtener valores del formulario
    formData.append("idpa", document.getElementById("csidpa").value);
    formData.append("medico_operando", document.getElementsByName("medico_operando")[0].value);
    formData.append("cirugia_realizar", document.getElementsByName("cirugia_realizar")[0].value);
    formData.append("nombre_solicitante", document.getElementsByName("nombre_solicitante")[0].value);
    formData.append("medicamentos", document.getElementsByName("medicamentos")[0].value);
    formData.append("material", document.getElementsByName("material")[0].value);
    formData.append("cantidad", document.getElementsByName("cantidad")[0].value);
    formData.append("procesado_por", document.getElementById("procesado_por").value);  // Capturar el usuario que registra

    // Enviar datos con Fetch API
    fetch("add_medicamento_medifarma.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            Swal.fire("Éxito", "Registro guardado correctamente", "success")
                .then(() => window.location.reload());
        } else {
            Swal.fire("Error", result.error, "error");
        }
    })
    .catch(error => {
        console.error("Error en la petición:", error);
        Swal.fire("Error", "No se pudieron registrar los datos", "error");
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
<?php include_once '../../backend/modal/md_gastos_quirofano.php' ?>
<?php include_once '../../backend/modal/md_post_operatorio.php' ?>
<?php include_once '../../backend/modal/md_recuperacion.php' ?>
<?php include_once '../../backend/modal/md_medicamento_medifarma.php' ?>

</body>
</html>


