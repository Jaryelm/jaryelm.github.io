<?php
include_once '../../backend/registros/session_check.php';
$medidataPuedeAprobarSignosVitales = (($_SESSION['rol'] ?? '') === 'Administrador');
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

<a type="button" href="imprimir.php?id=<?php echo $d->idpa; ?>" class="button">Informe General</a>

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

<div class="data">
    <div class="content-data">

<button class="accordion">Consulta</button>
<div class="panel">
    <div class="botons-modal">
        <label for="btns-modal">
            Registrar
        </label>
    </div>
    <h3>Datos Generales</h3>
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
                        <th scope="col">Motivo</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Médico Tratante</th>
                        <th scope="col">Especialidad</th>
                        <th scope="col">Servicio</th>
                        <th scope="col">NO. Habitación</th>
                        <th scope="col">Fecha Ingreso</th>
                        <th scope="col">Fecha Egreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <th scope="row"><?php echo $f->nompa; ?></th>
                            <td data-title="Motivo"><?php echo $f->mtcl; ?></td>
                            <td data-title="Fecha"><?php echo $f->fere; ?></td>
                            <td data-title="Médico Tratante"><?php echo $f->medico_tratante; ?></td>
                            <td data-title="Especialidad"><?php echo $f->especialidad; ?></td>
                            <td data-title="Servicio"><?php echo $f->servicio; ?></td>
                            <td data-title="No Habitación"><?php echo !empty($f->habitacion_no) ? $f->habitacion_no : 'N/A'; ?></td>
                            <td data-title="Fecha Ingreso"><?php echo !empty($f->fecha_hora_ingreso) ? $f->fecha_hora_ingreso : 'N/A'; ?></td>
                            <td data-title="Fecha Egreso"><?php echo !empty($f->fecha_hora_egreso) ? $f->fecha_hora_egreso : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
    </div>
</div>

<!-- Antecedentes -->
<button class="accordion">Antecedentes</button>
<div class="panel">
    <div class="boton-modal">
        <label for="btn-modal">Registrar</label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM genogram WHERE idpa= :id");
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
            <!-- Sección 1: Datos Generales -->
            <h3>Datos Generales</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Antecedente Familiares</th>
                        <th>Alergias</th>
                        <th>Medicamentos Actuales</th>
                        <th>Tipo Sanguíneo</th>
                        <th>Fecha</th>
                        <th>Procesado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->antecedentes_familiares; ?></td>
                            <td><?php echo $f->alergias; ?></td>
                            <td><?php echo $f->medicamentos_actuales; ?></td>
                            <td><?php echo $f->tipeo_sanguineo; ?></td>
                            <td><?php echo $f->fere; ?></td>
                            <td><?php echo $f->procesado_por; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <h3>Antecedentes Médicos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Antecedentes Médicos</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->antecedentes_medicos; ?></td>
                            <td><?php echo $f->notas_medicas; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <br>

            <h3>Complicaciones Agudas en Diabetes</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Complicaciones Agudas en Diabetes</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->complicaciones_diabetes; ?></td>
                            <td><?php echo $f->notas_diabetes; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <h3>Enfermedades Crónicas</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Enfermedades Crónicas</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->enfermedades_cronicas; ?></td>
                            <td><?php echo $f->notas_cronicas; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <h3>Antecedentes Quirúrgicos</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Antecedentes Quirúrgicos</th>
                        <th>Notas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->antecedentes_quirurgicos; ?></td>
                            <td><?php echo $f->notas_quirurgicas; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>


        <?php else: ?>
            <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
        <br>
    </div>
</div>





<button class="accordion">Plan DX</button>
<div class="panel">

    <div class="botons-modals">
        <label for="btns-modals">
        Registrar
        </label>
    </div>
    <h3>Datos Generales</h3>
    <div class="table-responsive">
<?php 
        $id = $_GET['id'];
$sentencia = $connect->prepare("SELECT * FROM treatment  WHERE idpa= '$id';");
 $sentencia->execute();
$data =  array();
if($sentencia){
  while($r = $sentencia->fetchObject()){
    $data[] = $r;
  }
}
         ?>
         <?php if(count($data)>0):?>
        <table class="responsive-table">
            <thead>
                <tr>
                    <th scope="col">Paciente</th>
                    <th scope="col">Tratamiento</th>
                    <th scope="col">Fecha</th>
                    
                </tr>
            </thead>

            <tbody>
                 <?php foreach($data as $a):?>
                 <tr>
                     <th scope="row"><?php echo $a->nompa; ?></th>
                     <td data-title="Motivo"><?php echo $a->nomtra; ?></td>
                     <td data-title="Fecha"><?php echo $a->fere; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else:?>
      <p class="alert alert-warning">No hay datos</p>
    <?php endif; ?>
</div>

</div>

<button class="accordion">Signos Vitales</button>
<div class="panel">
    <br>
<div class="table-header">
    <button class="register-btn" onclick="descargarPDF()">Descargar Hoja de Signos Vitales</button>
</div>
    <br>
    <h3>Datos Generales</h3>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th scope="col">FECHA</th>
                    <th scope="col">HORA</th>
                    <th scope="col">REALIZADO POR</th>
                    <th scope="col">REVISADO POR</th>
                    <th scope="col">PESO (kg/lb)</th>
                    <th scope="col">TALLA (cm/in)</th>
                    <th scope="col">PA (mmHg)</th>
                    <th scope="col">PAM</th>
                    <th scope="col">FC (lpm)</th>
                    <th scope="col">FR (rpm)</th>
                    <th scope="col">SAT (SatO2)</th>
                    <th scope="col">TEMP (°C/°F)</th>
                    <th scope="col">GLUCOSA (mg/dL / mmol/L)</th>
                    <th scope="col">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="signosVitalesBody">
                <!-- Aquí se llenarán los datos dinámicamente -->
            </tbody>
        </table>
    </div>
</div>
<!-- Función para descargar PDF -->
<script>
    function descargarPDF() {
    const idpa = <?php echo $_GET['id']; ?>; // Obtener el ID del paciente
    const url = `generate_signos_vitales_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank'); // Abrir el PDF en una nueva pestaña
}

    function descargarPDFSignoVital(signoId) {
        const idpa = <?php echo (int)($_GET['id'] ?? 0); ?>;
        if (!signoId) return;
        window.open(`generate_signos_vitales_pdf.php?idpa=${idpa}&signo_id=${signoId}`, '_blank');
    }
</script>
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


<button class="accordion">Hospitalización</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarHojaHospitalizacionPDF()">Descargar Hoja de Hospitalización</button>
        <button class="register-btn" onclick="registrarHospitalizacion()">Registrar</button>
    </div>
    <br>
    <input type="hidden" id="idpa" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id']) : ''; ?>">
    <div class="table-responsive" style="display: flex; justify-content: center; padding: 20px; overflow-x: auto; margin: 0 auto; max-width: 1400px;">
        <table class="responsive-table" style="width: 100%; max-width: 1300px; border-collapse: collapse; text-align: left;">
            <tbody>
                <tr>
                    <!-- Control de Oxígeno -->
                    <td style="padding: 15px; vertical-align: top; width: 50%;">
                        <h3 style="margin-bottom: 10px; text-align: center;">CONTROL DE OXÍGENO</h3>
                        <label for="oxigenoInicio" style="display: block; text-align: center; margin-bottom: 5px;">INICIO:</label>
                        <input type="datetime-local" id="oxigenoInicio" style="width: 100%; max-width: 600px;">
                        <label for="oxigenoHora" style="display: block; text-align: center; margin-bottom: 5px;">HORA:</label>
                        <input type="time" id="oxigenoHora" style="width: 100%; max-width: 600px;"><br><br>
                        <label for="oxigenoFinaliza" style="display: block; text-align: center; margin-bottom: 5px;">FINALIZA:</label>
                        <input type="datetime-local" id="oxigenoFinaliza" style="width: 100%; max-width: 600px;">
                        <label for="oxigenoObservacion" style="display: block; text-align: center; margin-bottom: 5px;">OBSERVACIÓN:</label>
                        <textarea id="oxigenoObservacion" rows="3" style="width: 100%; max-width: 600px; resize: none;"></textarea>
                        <label for="oxigenoTurno" style="display: block; text-align: center; margin-bottom: 5px;">TURNO:</label>
                        <select id="oxigenoTurno" style="width: 100%; max-width: 600px;">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
                    <!-- Control de Uso Colchón Aire -->
                    <td style="padding: 15px; vertical-align: top; width: 50%;">
                        <h3 style="margin-bottom: 10px; text-align: center;">CONTROL DE USO COLCHÓN AIRE</h3>
                        <label for="colchonInicio" style="display: block; text-align: center; margin-bottom: 5px;">INICIO:</label>
                        <input type="datetime-local" id="colchonInicio" style="width: 100%; max-width: 600px;">
                        <label for="colchonHora" style="display: block; text-align: center; margin-bottom: 5px;">HORA:</label>
                        <input type="time" id="colchonHora" style="width: 100%; max-width: 600px;"><br><br>
                        <label for="colchonFinaliza" style="display: block; text-align: center; margin-bottom: 5px;">FINALIZA:</label>
                        <input type="datetime-local" id="colchonFinaliza" style="width: 100%; max-width: 600px;">
                        <label for="colchonObservacion" style="display: block; text-align: center; margin-bottom: 5px;">OBSERVACIÓN:</label>
                        <textarea id="colchonObservacion" rows="3" style="width: 100%; max-width: 600px; resize: none;"></textarea>
                        <label for="colchonTurno" style="display: block; text-align: center; margin-bottom: 5px;">TURNO:</label>
                        <select id="colchonTurno" style="width: 100%; max-width: 600px;">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <!-- Control de Oxígeno con Reservorio -->
                    <td style="padding: 15px; vertical-align: top; width: 50%;">
                        <h3 style="margin-bottom: 10px; text-align: center;" style="display: block; text-align: center; margin-bottom: 5px;">CONTROL DE OXÍGENO CON RESERVORIO</h3>
                        <label for="reservorioInicio" style="display: block; text-align: center; margin-bottom: 5px;">INICIO:</label>
                        <input type="datetime-local" id="reservorioInicio" style="width: 100%; max-width: 600px;">
                        <label for="reservorioHora" style="display: block; text-align: center; margin-bottom: 5px;">HORA:</label>
                        <input type="time" id="reservorioHora" style="width: 100%; max-width: 600px;"><br><br>
                        <label for="reservorioFinaliza" style="display: block; text-align: center; margin-bottom: 5px;">FINALIZA:</label>
                        <input type="datetime-local" id="reservorioFinaliza" style="width: 100%; max-width: 600px;">
                        <label for="reservorioObservacion" style="display: block; text-align: center; margin-bottom: 5px;">OBSERVACIÓN:</label>
                        <textarea id="reservorioObservacion" rows="3" style="width: 100%; max-width: 600px; resize: none;"></textarea>
                        <label for="reservorioTurno" style="display: block; text-align: center; margin-bottom: 5px;">TURNO:</label>
                        <select id="reservorioTurno" style="width: 100%; max-width: 600px;">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
                    <!-- Control de Monitorización -->
                    <td style="padding: 15px; vertical-align: top; width: 50%;">
                        <h3 style="margin-bottom: 10px; text-align: center;">CONTROL DE MONITORIZACIÓN</h3>
                        <label for="monitorInicio" style="display: block; text-align: center; margin-bottom: 5px;">INICIO:</label>
                        <input type="datetime-local" id="monitorInicio" style="width: 100%; max-width: 600px;">
                        <label for="monitorHora" style="display: block; text-align: center; margin-bottom: 5px;">HORA:</label>
                        <input type="time" id="monitorHora" style="width: 100%; max-width: 600px;"><br><br>
                        <label for="monitorFinaliza" style="display: block; text-align: center; margin-bottom: 5px;">FINALIZA:</label>
                        <input type="datetime-local" id="monitorFinaliza" style="width: 100%; max-width: 600px;">
                        <label for="monitorObservacion" style="display: block; text-align: center; margin-bottom: 5px;">OBSERVACIÓN:</label>
                        <textarea id="monitorObservacion" rows="3" style="width: 100%; max-width: 600px; resize: none;"></textarea>
                        <label for="monitorTurno" style="display: block; text-align: center; margin-bottom: 5px;">TURNO:</label>
                        <select id="monitorTurno" style="width: 100%; max-width: 600px;">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <!-- Nebulizaciones -->
                    <td style="padding: 15px; vertical-align: top; width: 50%;">
                        <h3 style="margin-bottom: 10px; text-align: center;">NEBULIZACIONES</h3>
                        <label for="nebulizacionInicio" style="display: block; text-align: center; margin-bottom: 5px;">INICIO:</label>
                        <input type="datetime-local" id="nebulizacionInicio" style="width: 100%; max-width: 600px;">
                        <label for="nebulizacionHora" style="display: block; text-align: center; margin-bottom: 5px;">HORA:</label>
                        <input type="time" id="nebulizacionHora" style="width: 100%; max-width: 600px;"><br><br>
                        <label for="nebulizacionFinaliza" style="display: block; text-align: center; margin-bottom: 5px;">FINALIZA:</label>
                        <input type="datetime-local" id="nebulizacionFinaliza" style="width: 100%; max-width: 600px;">
                        <label for="nebulizacionObservacion" style="display: block; text-align: center; margin-bottom: 5px;">OBSERVACIÓN:</label>
                        <textarea id="nebulizacionObservacion" rows="3" style="width: 100%; max-width: 600px; resize: none;"></textarea>
                        <label for="nebulizacionTurno" style="display: block; text-align: center; margin-bottom: 5px;">TURNO:</label>
                        <select id="nebulizacionTurno" style="width: 100%; max-width: 600px;">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
                    <!-- Succión -->
                    <td style="padding: 15px; vertical-align: top; width: 50%;">
                        <h3 style="margin-bottom: 10px; text-align: center;">SUCCIÓN</h3>
                        <label for="succionInicio" style="display: block; text-align: center; margin-bottom: 5px;">INICIO:</label>
                        <input type="datetime-local" id="succionInicio" style="width: 100%; max-width: 600px;">
                        <label for="succionHora" style="display: block; text-align: center; margin-bottom: 5px;">HORA:</label>
                        <input type="time" id="succionHora" style="width: 100%; max-width: 600px;"><br><br>
                        <label for="succionFinaliza" style="display: block; text-align: center; margin-bottom: 5px;">FINALIZA:</label>
                        <input type="datetime-local" id="succionFinaliza" style="width: 100%; max-width: 600px;">
                        <label for="succionObservacion" style="display: block; text-align: center; margin-bottom: 5px;">OBSERVACIÓN:</label>
                        <textarea id="succionObservacion" rows="3" style="width: 100%; max-width: 600px; resize: none;"></textarea>
                        <label for="succionTurno" style="display: block; text-align: center; margin-bottom: 5px;">TURNO:</label>
                        <select id="succionTurno" style="width: 100%; max-width: 600px;">
                            <option value="">Seleccione un turno</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Función para descargar PDF de Hoja de Hospitalización -->
<script>
    function descargarHojaHospitalizacionPDF() {
        const idpa = <?php echo $_GET['id']; ?>; // Obtener el ID del paciente
        if (!idpa) {
            alert('ID del paciente no encontrado.');
            return;
        }
        const url = `generate_hospitalizacion_pdf.php?idpa=${idpa}`; // Archivo PHP para generar el PDF de la hoja de hospitalización
        window.open(url, '_blank'); // Abrir el PDF en una nueva pestaña
    }
</script>

<!-- Gráfica de Temperatura -->
<button class="accordion" id="accordion-temperatura">Gráfica de Temperatura</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="graftempPDF()">Descargar Gráfica de Temperatura</button>
        <button class="register-btn" onclick="prepararModal()">Registrar</button>
    </div>
    <br>

    <!-- Sección 1: Signos Vitales -->
    <h3>Signos Vitales</h3>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>TEMPERATURA (°C)</th>
                    <th>FRECUENCIA CARDIACA</th>
                    <th>PRESIÓN ARTERIAL</th>
                    <th>SPO2</th>
                    <th>PESO (KG)</th>
                    <th>TALLA (CM)</th>
                </tr>
            </thead>
            <tbody id="temperaturaBody1">
                <!-- Filas dinámicas generadas por JavaScript -->
            </tbody>
        </table>
    </div>
    
    <br>

    <!-- Sección 2: Datos Médicos -->
    <h3>Datos Médicos</h3>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>IMC</th>
                    <th>GLUCOMETRÍA</th>
                    <th>FRECUENCIA RESPIRATORIA</th>
                    <th>TURNO</th>
                    <th>FECHA Y HORA</th>
                    <th>PROCESADO POR</th>
                </tr>
            </thead>
            <tbody id="temperaturaBody2">
                <!-- Filas dinámicas generadas por JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Función para descargar PDF de Gráfica de Temperatura -->
<script>
    function graftempPDF() {
        const idpa = <?php echo $_GET['id']; ?>;
        if (!idpa) {
            alert('ID del paciente no encontrado.');
            return;
        }
        const url = `grafica_temp_pdf.php?idpa=${idpa}`;
        window.open(url, '_blank');
    }
</script>

<script>
function determinarTurno() {
    const horas = new Date().getHours();
    if (horas >= 7 && horas < 15) return 'A';
    if (horas >= 15 && horas < 22) return 'B';
    return 'C';
}

function prepararModal() {
    $('#turnoActual').val(determinarTurno());
    $('#btns-modal-temps').prop('checked', true);
    $('#frecuenciac, #tensiona, #temps, #spo_2, #peso_kg, #talla_temp, #imc_temp, #glucap_temp, #fresp_temp').val('');
}

function guardarDato() {
    const datos = {
        idpa: $('#idpa').val(),
        procesado_por: $('#procesado_por').val(),
        turno: $('#turnoActual').val(),
        frecuenciac: $('#frecuenciac').val(),
        tensiona: $('#tensiona').val(),
        temps: $('#temps').val(),
        spo_2: $('#spo_2').val(),
        peso_kg: $('#peso_kg').val(),
        talla_temp: $('#talla_temp').val(),
        imc_temp: $('#imc_temp').val(),
        glucap_temp: $('#glucap_temp').val(),
        fresp_temp: $('#fresp_temp').val()
    };

    if (!datos.temps.trim()) {
        swal('Error', 'Por favor, ingrese la temperatura.', 'warning');
        return;
    }

    $.post('save_temperatura.php', datos, function(response) {
        if (response.error) {
            swal('Error', response.error, 'error');
        } else {
            swal('Guardado', 'La temperatura se ha registrado correctamente.', 'success');
            cerrarModal();
            cargarDatos();
        }
    }).fail(function(xhr) {
        swal('Error', 'Ocurrió un problema: ' + xhr.responseText, 'error');
    });
}

function cargarDatos() {
    const idpa = $('#idpa').val();
    if (!idpa) {
        swal('Error', 'El ID del paciente es obligatorio.', 'error');
        return;
    }

    $.get('fetch_temperatura.php', { idpa }, function(response) {
        if (response.error) {
            swal('Error', response.error, 'error');
        } else {
            let rows1 = '', rows2 = '';

            response.data.forEach(item => {
                rows1 += `<tr>
                            <td>${item.temps}</td>
                            <td>${item.frecuenciac}</td>
                            <td>${item.tensiona}</td>
                            <td>${item.spo_2}</td>
                            <td>${item.peso_kg}</td>
                            <td>${item.talla_temp}</td>
                        </tr>`;

                rows2 += `<tr>
                            <td>${item.imc_temp}</td>
                            <td>${item.glucap_temp}</td>
                            <td>${item.fresp_temp}</td>
                            <td>${item.turno}</td>
                            <td>${item.created_at}</td>
                            <td>${item.procesado_por}</td>
                        </tr>`;
            });

            $('#temperaturaBody1').html(rows1);
            $('#temperaturaBody2').html(rows2);
        }
    }).fail(function(xhr) {
        swal('Error', 'Ocurrió un problema al cargar los datos: ' + xhr.responseText, 'error');
    });
}

function cerrarModal() {
    $('#btns-modal-temps').prop('checked', false);
}

$(document).ready(cargarDatos);
</script>



<button class="accordion">Glucometrías e Insulinas</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarPDFInsulinas()">Descargar Hoja de Glucometrías e Insulinas</button>
    </div>
    <br>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th scope="col">FECHA</th>
                    <th scope="col">TURNO</th>
                    <th scope="col">HORA</th>
                    <th scope="col">GLUCOMETRIAS</th>
                    <th scope="col">INSULINA CRISTALINA</th>
                    <th scope="col">NPH</th>
                    <th scope="col">PROCESADO POR</th>
                    <th scope="col">FIRMA DIGITAL</th>
                    <th scope="col">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="glucometriasBody">
                <!-- Aquí se llenarán los datos dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<script>
// Descargar PDF
function descargarPDFInsulinas() {
    const idpa = <?php echo $_GET['id']; ?>;
    const url = `generate_glucometrias_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank');
}

// Registrar datos
function registrarGlucometrias() {
    const uniqueId = "registroActualGlucometrias";
    const fecha = document.getElementById(`fecha_${uniqueId}`).value;
    const turno = document.getElementById(`turno_${uniqueId}`).value;
    const hora = document.getElementById(`hora_${uniqueId}`).value;
    const glucometria = document.getElementById(`glucometria_${uniqueId}`).value;
    const insulinaCristalina = document.getElementById(`insulinaCristalina_${uniqueId}`).value;
    const nph = document.getElementById(`nph_${uniqueId}`).value;
    const procesadoPor = document.getElementById(`procesadoPor_${uniqueId}`).value;
    const canvas = document.getElementById(`signaturePad_${uniqueId}`);
    const signature = canvas.toDataURL("image/png");

    // Validar campos vacíos
    if (!fecha || !turno || !hora || !glucometria || !insulinaCristalina || !nph || !procesadoPor || !signature) {
        swal("Error", "Todos los campos son obligatorios.", "error");
        return;
    }

    // Enviar datos al backend
    $.ajax({
        type: "POST",
        url: "add_glucometrias.php",
        data: {
            fecha,
            turno,
            hora,
            glucometria,
            insulina_cristalina: insulinaCristalina,
            nph,
            procesado_por: procesadoPor,
            firma: signature,
            idpa: <?php echo $_GET['id']; ?>
        },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal("Error", response.error, "error");
            } else if (response.success) {
                swal("Guardado", response.success, "success");
                cargarGlucometrias();
            }
        },
        error: function(xhr) {
            console.error("Error en la solicitud:", xhr.responseText);
            swal("Error", "Ocurrió un problema al registrar los datos.", "error");
        }
    });
}

// Cargar registros
function cargarGlucometrias() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_glucometrias.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            let content = '';
            response.data.forEach((item) => {
                const signature = item.firma
                    ? `<img src="data:image/png;base64,${item.firma}" alt="Firma Digital" style="width: 150px; height: 50px;" />`
                    : 'No disponible';

                content += `
                    <tr>
                        <td>${item.fecha}</td>
                        <td>${item.turno}</td>
                        <td>${item.hora}</td>
                        <td>${item.glucometria}</td>
                        <td>${item.insulina_cristalina}</td>
                        <td>${item.nph}</td>
                        <td>${item.procesado_por}</td>
                        <td>${signature}</td>
                        <td><button class="register-btn" disabled>Registrados</button></td>
                    </tr>
                `;
            });

            $("#glucometriasBody").html(content);
            agregarFilaNuevaGlucometrias(); // Asegúrate de agregar inputs después de cargar los datos existentes
        },
        error: function(xhr) {
            console.error("Error al cargar los datos:", xhr.responseText);
        }
    });
}

function agregarFilaNuevaGlucometrias() {
    const uniqueId = "registroActualGlucometrias"; // Nombre único
    const nuevaFila = `
        <tr>
            <td><input type="date" id="fecha_${uniqueId}"></td>
            <td><input type="text" id="turno_${uniqueId}"></td>
            <td><input type="time" id="hora_${uniqueId}"></td>
            <td><input type="text" id="glucometria_${uniqueId}"></td>
            <td><input type="text" id="insulinaCristalina_${uniqueId}"></td>
            <td><input type="text" id="nph_${uniqueId}"></td>
            <td><input type="text" id="procesadoPor_${uniqueId}" value="<?php echo $name; ?>" readonly></td>
            <td>
                <div class="signature-container">
                    <canvas id="signaturePad_${uniqueId}" width="150" height="50"></canvas>
                    <button class="register-btn clear-btn" onclick="clearSignature('${uniqueId}')">Limpiar</button>
                </div>
            </td>
            <td><button class="register-btn" onclick="registrarGlucometrias()">Registrar</button></td>
        </tr>
    `;

    $("#glucometriasBody").append(nuevaFila);
    inicializarCanvasFirma(uniqueId);
}

function inicializarCanvasFirma(uniqueId) {
    const canvas = document.getElementById(`signaturePad_${uniqueId}`);
    const ctx = canvas.getContext("2d");

    let isDrawing = false;

    canvas.addEventListener("mousedown", (e) => {
        isDrawing = true;
        ctx.beginPath();
        ctx.moveTo(e.offsetX, e.offsetY);
    });

    canvas.addEventListener("mousemove", (e) => {
        if (isDrawing) {
            ctx.lineTo(e.offsetX, e.offsetY);
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
    window.clearSignature = function (id) {
        const canvasToClear = document.getElementById(`signaturePad_${id}`);
        const ctxToClear = canvasToClear.getContext("2d");
        ctxToClear.clearRect(0, 0, canvasToClear.width, canvasToClear.height);
    };
}

// Inicialización
$(document).ready(function() {
    cargarGlucometrias();
});
</script>

<style>
    .signature-container {
    display: flex;
    flex-direction: column;
    align-items: center; /* Centra todo horizontalmente */
    gap: 10px; /* Espaciado entre el canvas y el botón */
}

.signature-container canvas {
    border: 1px solid #000;
    width: 100%;
    max-width: 150px;
    height: 50px;
}

.clear-btn {
    width: 100%;
    max-width: 150px;
    padding: 5px;
    background-color: #035c67;
    color: white;
    border: none;
    cursor: pointer;
    text-align: center;
}

/* Responsivo: en pantallas menores a 600px, el botón ocupará todo el ancho */
@media (max-width: 600px) {
    .clear-btn {
        width: 100%;
    }
}
</style>

<button class="accordion">Control de Dieta Cafeteria</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarPDFDieta()">Descargar Hoja de Control de Dieta</button>
    </div>
    <br>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th scope="col">FECHA</th>
                    <th scope="col">TURNO</th>
                    <th scope="col">TIPO DE DIETA</th>
                    <th scope="col">PROCESADO POR</th>
                    <th scope="col">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="controlDietaBody">
                <!-- Aquí se llenarán los datos dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<script>
// Descargar PDF
function descargarPDFDieta() {
    const idpa = <?php echo $_GET['id']; ?>;
    const url = `generate_dieta_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank');
}

// Registrar datos
function registrarDieta() {
    const uniqueId = "registroDietas";
    const fecha = document.getElementById(`fecha_${uniqueId}`).value;
    const turno = document.getElementById(`turno_${uniqueId}`).value;
    const tipoDieta = document.getElementById(`tipoDieta_${uniqueId}`).value;
    const procesadoPor = document.getElementById(`procesadoPor_${uniqueId}`).value;

    if (!fecha || !turno || !tipoDieta || !procesadoPor) {
        swal("Error", "Todos los campos son obligatorios.", "error");
        return;
    }

    $.ajax({
        type: "POST",
        url: "add_dieta.php",
        data: {
            fecha,
            turno,
            tipo_dieta: tipoDieta,
            procesado_por: procesadoPor,
            idpa: <?php echo $_GET['id']; ?>
        },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal("Error", response.error, "error");
            } else if (response.success) {
                swal("Guardado", response.success, "success");
                cargarDieta();
            }
        },
        error: function(xhr) {
            console.error("Error en la solicitud:", xhr.responseText);
            swal("Error", "Ocurrió un problema al registrar los datos.", "error");
        }
    });
}

// Cargar registros
function cargarDieta() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_dieta.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            let content = '';
            response.data.forEach((item, index) => {
                content += `
                    <tr>
                        <td>${item.fecha}</td>
                        <td>${item.turno}</td>
                        <td>${item.tipo_dieta}</td>
                        <td>${item.procesado_por}</td>
                        <td>
                            <button class="register-btn" disabled>Registrados</button>
                        </td>
                    </tr>
                `;
            });

            $("#controlDietaBody").html(content);
            agregarFilaNuevaDieta(); // Agrega una fila vacía para nuevos registros
        },
        error: function(xhr) {
            console.error("Error al cargar los datos:", xhr.responseText);
        }
    });
}

function agregarFilaNuevaDieta() {
    const uniqueId = "registroDietas"; // Nombre único
    const nuevaFila = `
        <tr>
            <td><input type="date" id="fecha_${uniqueId}"></td>
            <td><input type="text" id="turno_${uniqueId}"></td>
            <td><input type="text" id="tipoDieta_${uniqueId}"></td>
            <td><input type="text" id="procesadoPor_${uniqueId}" value="<?php echo $name; ?>" readonly></td>
            <td><button class="register-btn" onclick="registrarDieta()">Registrar</button></td>
        </tr>
    `;

    $("#controlDietaBody").append(nuevaFila);
}

// Inicialización
$(document).ready(function() {
    cargarDieta();
});
</script>




<div class="hospital-emergency">
    <!-- Título del acordeón principal -->
    <button class="accordion">Control de Ingestas y Excretas</button>
    <div class="panel">
        <br>
        <!-- Subsección INGESTAS -->
        <div class="subsection">
            <button class="accordion">Ingestas</button>
            <div class="panel">
                <br>
                <div class="table-header">
                    <button class="register-btn" onclick="descargarPDFIngestas()">Descargar Hoja de Ingestas</button>
                </div>
                <br>
                <div class="table-responsive">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">FECHA</th>
                                <th scope="col">HORA</th>
                                <th scope="col">VIA ORAL (TIPO)</th>
                                <th scope="col">VIA ORAL (CANTIDAD)</th>
                                <th scope="col">VIA PARENTERAL (TIPO)</th>
                                <th scope="col">VIA PARENTERAL (CANTIDAD)</th>
                                <th scope="col">PROCESADO POR</th>
                                <th scope="col">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="ingestasBody">
                            <!-- Datos dinámicos de INGESTAS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><input type="date" id="fecha_ingestas"></td>
                                <td><input type="time" id="hora_ingestas"></td>
                                <td><input type="text" id="via_oral_tipo"></td>
                                <td><input type="text" id="via_oral_cantidad"></td>
                                <td><input type="text" id="via_parenteral_tipo"></td>
                                <td><input type="text" id="via_parenteral_cantidad"></td>
                                <td><input type="text" id="procesado_por_ingestas" value="<?php echo $name; ?>" readonly></td>
                                <td><button class="register-btn" onclick="registrarIngestas()">Registrar</button></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <br>

        <!-- Subsección EXCRETAS -->
        <div class="subsection">
            <button class="accordion">Excretas</button>
            <div class="panel">
                <br>
                <div class="table-header">
                    <button class="register-btn" onclick="descargarPDFExcretas()">Descargar Hoja de Excretas</button>
                </div>
                <br>
                <div class="table-responsive">
                    <table class="responsive-table">
                        <thead>
                            <tr>
                                <th scope="col">FECHA</th>
                                <th scope="col">HORA</th>
                                <th scope="col">ORINA</th>
                                <th scope="col">VÓMITO</th>
                                <th scope="col">DRENAJE</th>
                                <th scope="col">SUCCIÓN</th>
                                <th scope="col">OTROS</th>
                                <th scope="col">PROCESADO POR</th>
                                <th scope="col">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody id="excretasBody">
                            <!-- Datos dinámicos de EXCRETAS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><input type="date" id="fecha_excretas"></td>
                                <td><input type="time" id="hora_excretas"></td>
                                <td><input type="text" id="orina"></td>
                                <td><input type="text" id="vomito"></td>
                                <td><input type="text" id="drenaje"></td>
                                <td><input type="text" id="succion"></td>
                                <td><input type="text" id="otros"></td>
                                <td><input type="text" id="procesado_por_excretas" value="<?php echo $name; ?>" readonly></td>
                                <td><button class="register-btn" onclick="registrarExcretas()">Registrar</button></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <br>
    </div>
</div>

<script>
    function registrarIngestas() {
    const idpa = <?php echo $_GET['id']; ?>;
    const fecha = document.getElementById('fecha_ingestas').value;
    const hora = document.getElementById('hora_ingestas').value;
    const viaOralTipo = document.getElementById('via_oral_tipo').value;
    const viaOralCantidad = document.getElementById('via_oral_cantidad').value;
    const viaParenteralTipo = document.getElementById('via_parenteral_tipo').value;
    const viaParenteralCantidad = document.getElementById('via_parenteral_cantidad').value;
    const procesadoPor = document.getElementById('procesado_por_ingestas').value;

    if (!fecha || !hora || !viaOralTipo || !viaOralCantidad || !viaParenteralTipo || !viaParenteralCantidad || !procesadoPor) {
        swal('Error', 'Todos los campos son obligatorios.', 'error');
        return;
    }

    $.ajax({
        type: 'POST',
        url: 'add_ingestas.php',
        data: {
            idpa,
            fecha,
            hora,
            via_oral_tipo: viaOralTipo,
            via_oral_cantidad: viaOralCantidad,
            via_parenteral_tipo: viaParenteralTipo,
            via_parenteral_cantidad: viaParenteralCantidad,
            procesado_por: procesadoPor,
        },
        dataType: 'json',
        success: function (response) {
            if (response.error) {
                swal('Error', response.error, 'error');
            } else {
                swal('Guardado', 'Registro de Ingestas guardado correctamente.', 'success');
                cargarIngestas();
            }
        },
        error: function (xhr) {
            console.error('Error al registrar las Ingestas:', xhr.responseText);
        },
    });
}
</script>

<script>
    function registrarExcretas() {
    const idpa = <?php echo $_GET['id']; ?>;
    const fecha = document.getElementById('fecha_excretas').value;
    const hora = document.getElementById('hora_excretas').value;
    const orina = document.getElementById('orina').value;
    const vomito = document.getElementById('vomito').value;
    const drenaje = document.getElementById('drenaje').value;
    const succion = document.getElementById('succion').value;
    const otros = document.getElementById('otros').value;
    const procesadoPor = document.getElementById('procesado_por_excretas').value;

    // Validar que todos los campos estén completos
    if (!fecha || !hora || !orina || !vomito || !drenaje || !succion || !otros || !procesadoPor) {
        swal('Error', 'Todos los campos son obligatorios.', 'error');
        return;
    }

    // Enviar datos al servidor
    $.ajax({
        type: 'POST',
        url: 'add_excretas.php',
        data: {
            idpa,
            fecha,
            hora,
            orina,
            vomito,
            drenaje,
            succion,
            otros,
            procesado_por: procesadoPor,
        },
        dataType: 'json',
        success: function (response) {
            if (response.error) {
                swal('Error', response.error, 'error');
            } else {
                swal('Guardado', 'Registro de Excretas guardado correctamente.', 'success');
                cargarExcretas(); // Recargar la tabla después del registro
            }
        },
        error: function (xhr) {
            console.error('Error al registrar las Excretas:', xhr.responseText);
            swal('Error', 'Ocurrió un problema al registrar las Excretas.', 'error');
        },
    });
}

</script>

<script>
// Descargar PDF
function descargarPDFIngestas() {
    const idpa = <?php echo $_GET['id']; ?>;
    const url = `generate_ingestas_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank');
}
</script>

<script>
// Descargar PDF
function descargarPDFExcretas() {
    const idpa = <?php echo $_GET['id']; ?>;
    const url = `generate_excretas_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank');
}
</script>

<script>
    function cargarIngestas() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_ingestas.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            let content = '';
            response.data.forEach((item) => {
                content += `
                    <tr>
                        <td>${item.fecha}</td>
                        <td>${item.hora}</td>
                        <td>${item.via_oral_tipo}</td>
                        <td>${item.via_oral_cantidad}</td>
                        <td>${item.via_parenteral_tipo}</td>
                        <td>${item.via_parenteral_cantidad}</td>
                        <td>${item.procesado_por}</td>
                        <td><button class="register-btn" disabled>Registrados</button></td>
                    </tr>
                `;
            });

            $("#ingestasBody").html(content);
        },
        error: function(xhr) {
            console.error("Error al cargar los datos de INGESTAS:", xhr.responseText);
        }
    });
}
</script>

<script>
    function cargarExcretas() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_excretas.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            let content = '';
            response.data.forEach((item) => {
                content += `
                    <tr>
                        <td>${item.fecha}</td>
                        <td>${item.hora}</td>
                        <td>${item.orina}</td>
                        <td>${item.vomito}</td>
                        <td>${item.drenaje}</td>
                        <td>${item.succion}</td>
                        <td>${item.otros}</td>
                        <td>${item.procesado_por}</td>
                        <td><button class="register-btn" disabled>Registrados</button></td>
                    </tr>
                `;
            });

            $("#excretasBody").html(content);
        },
        error: function(xhr) {
            console.error("Error al cargar los datos de EXCRETAS:", xhr.responseText);
        }
    });
}
</script>

<script>
    $(document).ready(function() {
    cargarIngestas();
    cargarExcretas();
});
</script>






<button class="accordion">Evolución</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarPDFEvolucion()">Descargar Hoja de Evolución</button>
    </div>
    <br>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th scope="col">NOTA DE EVOLUCIÓN</th>
                    <th scope="col">FECHA Y HORA</th>
                    <th scope="col">ÓRDENES MÉDICAS</th>
                    <th scope="col">PROCESADO POR</th>
                    <th scope="col">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="controlEvolucionBody">
                <!-- Aquí se llenarán los datos dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<script>
// Descargar PDF
function descargarPDFEvolucion() {
    const idpa = <?php echo $_GET['id']; ?>;
    const url = `generate_evolucion_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank');
}

// Registrar datos
function registrarEvolucion() {
    const uniqueId = "registroEvolucion";
    const notaEvolucion = document.getElementById(`notaEvolucion_${uniqueId}`).value;
    const fechaHora = document.getElementById(`fechaHora_${uniqueId}`).value;
    const ordenesMedicas = document.getElementById(`ordenesMedicas_${uniqueId}`).value;
    const procesadoPor = document.getElementById(`procesadoPor_${uniqueId}`).value;

    if (!notaEvolucion || !fechaHora || !ordenesMedicas || !procesadoPor) {
        swal("Error", "Todos los campos son obligatorios.", "error");
        return;
    }

    $.ajax({
        type: "POST",
        url: "add_evolucion.php",
        data: {
            nota_evolucion: notaEvolucion,
            fecha_hora: fechaHora,
            ordenes_medicas: ordenesMedicas,
            procesado_por: procesadoPor,
            idpa: <?php echo $_GET['id']; ?>
        },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal("Error", response.error, "error");
            } else if (response.success) {
                swal("Guardado", response.success, "success");
                cargarEvolucion();
            }
        },
        error: function(xhr) {
            console.error("Error en la solicitud:", xhr.responseText);
            swal("Error", "Ocurrió un problema al registrar los datos.", "error");
        }
    });
}

// Cargar registros
function cargarEvolucion() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_evolucion.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            let content = '';
            response.data.forEach((item, index) => {
                content += `
                    <tr>
                        <td>${item.nota_evolucion}</td>
                        <td>${item.fecha_hora}</td>
                        <td>${item.ordenes_medicas}</td>
                        <td>${item.procesado_por}</td>
                        <td>
                            <button class="register-btn" disabled>Registrados</button>
                        </td>
                    </tr>
                `;
            });

            $("#controlEvolucionBody").html(content);
            agregarFilaNuevaEvolucion(); // Agrega una fila vacía para nuevos registros
        },
        error: function(xhr) {
            console.error("Error al cargar los datos:", xhr.responseText);
        }
    });
}

function agregarFilaNuevaEvolucion() {
    const uniqueId = "registroEvolucion"; // Nombre único
    const nuevaFila = `
        <tr>
            <td><input type="text" id="notaEvolucion_${uniqueId}"></td>
            <td><input type="datetime-local" id="fechaHora_${uniqueId}"></td>
            <td><input type="text" id="ordenesMedicas_${uniqueId}"></td>
            <td><input type="text" id="procesadoPor_${uniqueId}" value="<?php echo $name; ?>" readonly></td>
            <td><button class="register-btn" onclick="registrarEvolucion()">Registrar</button></td>
        </tr>
    `;

    $("#controlEvolucionBody").append(nuevaFila);
}

// Inicialización
$(document).ready(function() {
    cargarEvolucion();
});
</script>



<button class="accordion">Medicamentos</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarPDFMedicamentos()">Descargar Hoja de Medicamentos</button>
    </div>
    <br>
    <div class="table-responsive">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th scope="col">MEDICAMENTO Y TRATAMIENTO</th>
                    <th scope="col">FECHA HORA</th>
                    <th scope="col">PROCESADO POR</th>
                    <th scope="col">ACCIONES</th>
                </tr>
            </thead>
            <tbody id="controlMedicamentosBody">
                <!-- Aquí se llenarán los datos dinámicamente -->
            </tbody>
        </table>
    </div>
</div>

<script>
// Descargar PDF
function descargarPDFMedicamentos() {
    const idpa = <?php echo $_GET['id']; ?>;
    const url = `generate_medicamentos_pdf.php?idpa=${idpa}`;
    window.open(url, '_blank');
}

// Registrar datos
function registrarMedicamentos() {
    const uniqueId = "registroMedicamentos";
    const medicamentoTratamiento = document.getElementById(`medicamentoTratamiento_${uniqueId}`).value;
    const fechaHora = document.getElementById(`fechaHora_${uniqueId}`).value;
    const procesadoPor = document.getElementById(`procesadoPor_${uniqueId}`).value;

    if (!medicamentoTratamiento || !fechaHora || !procesadoPor) {
        swal("Error", "Todos los campos son obligatorios.", "error");
        return;
    }

    $.ajax({
        type: "POST",
        url: "add_medicamentos.php",
        data: {
            medicamento_tratamiento: medicamentoTratamiento,
            fecha_hora: fechaHora,
            procesado_por: procesadoPor,
            idpa: <?php echo $_GET['id']; ?>
        },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal("Error", response.error, "error");
            } else if (response.success) {
                swal("Guardado", response.success, "success");
                cargarMedicamentos();
            }
        },
        error: function(xhr) {
            console.error("Error en la solicitud:", xhr.responseText);
            swal("Error", "Ocurrió un problema al registrar los datos.", "error");
        }
    });
}

// Cargar registros
function cargarMedicamentos() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_medicamentos.php",
        data: { idpa },
        dataType: "json",
        success: function(response) {
            if (response.error) {
                swal('Error', response.error, 'error');
                return;
            }

            let content = '';
            response.data.forEach((item, index) => {
                content += `
                    <tr>
                        <td>${item.medicamento_tratamiento}</td>
                        <td>${item.fecha_hora}</td>
                        <td>${item.procesado_por}</td>
                        <td>
                            <button class="register-btn" disabled>Registrados</button>
                        </td>
                    </tr>
                `;
            });

            $("#controlMedicamentosBody").html(content);
            agregarFilaNuevaMedicamentos(); // Agrega una fila vacía para nuevos registros
        },
        error: function(xhr) {
            console.error("Error al cargar los datos:", xhr.responseText);
        }
    });
}

function agregarFilaNuevaMedicamentos() {
    const uniqueId = "registroMedicamentos";
    const nuevaFila = `
        <tr>
            <td><input type="text" id="medicamentoTratamiento_${uniqueId}"></td>
            <td><input type="datetime-local" id="fechaHora_${uniqueId}"></td>
            <td><input type="text" id="procesadoPor_${uniqueId}" value="<?php echo $name; ?>" readonly></td>
            <td><button class="register-btn" onclick="registrarMedicamentos()">Registrar</button></td>
        </tr>
    `;

    $("#controlMedicamentosBody").append(nuevaFila);
}

// Inicialización
$(document).ready(function() {
    cargarMedicamentos();
});
</script>


<!-- Apoyo Nutrición -->
<button class="accordion">Apoyo Nutrición</button>
<div class="panel">
    <div class="botones-modals">
        <label for="btnapoyo-modal">Registrar</label>
    </div>

    <div class="table-responsive">
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM apoyo_nutricion WHERE idpa= :id");
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
            <!-- Sección 1: Datos Generales -->
            <h3>Datos Generales</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Paciente</th>
                        <th>PT</th>
                        <th>MB</th>
                        <th>CB</th>
                        <th>AR</th>
                        <th>Peso Habitual</th>
                        <th>Talla</th>
                        <th>IMC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->nompa; ?></td>
                            <td><?php echo $f->pt; ?></td>
                            <td><?php echo $f->mb; ?></td>
                            <td><?php echo $f->cb; ?></td>
                            <td><?php echo $f->ar; ?></td>
                            <td><?php echo $f->peso_habitual; ?> KG</td>
                            <td><?php echo $f->talla; ?> CM</td>
                            <td><?php echo $f->imc; ?> KG/M<sup>2</sup></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <!-- Sección 2: Evaluación Nutricional -->
            <h3>Evaluación Nutricional</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>IMC < 20.5</th>
                        <th>Pérdida de Peso</th>
                        <th>Ingesta Reducida</th>
                        <th>Paciente Grave</th>
                        <th>Peso Actual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->imc_20_5; ?></td>
                            <td><?php echo $f->perdida_peso; ?></td>
                            <td><?php echo $f->ingesta_reducida; ?></td>
                            <td><?php echo $f->paciente_grave; ?></td>
                            <td><?php echo $f->peso_actual; ?> KG</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <!-- Sección 3: Diagnóstico Inicial -->
            <h3>Diagnóstico Inicial</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Grado 1</th>
                        <th>Grado 2</th>
                        <th>Grado 3</th>
                        <th>Severidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->grado1; ?></td>
                            <td><?php echo $f->grado2; ?></td>
                            <td><?php echo $f->grado3; ?></td>
                            <td><?php echo $f->severidad; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>

            <!-- Sección 4: Evaluación Final -->
            <h3>Evaluación Final</h3>
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Marcador Total</th>
                        <th>Interpretación</th>
                        <th>Acciones</th>
                        <th>Diagnóstico</th>
                        <th>Edad</th>
                        <th>Fecha Evaluación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $f): ?>
                        <tr>
                            <td><?php echo $f->marcador_total; ?></td>
                            <td><?php echo $f->interpretacion; ?></td>
                            <td><?php echo $f->acciones; ?></td>
                            <td><?php echo $f->diagnostico; ?></td>
                            <td><?php echo ($f->edad == 1) ? "≥ 70 años" : "&lt; 70 años"; ?></td>
                            <td><?php echo $f->fecha_evaluacion; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
    </div>
</div>


<script>
    function enviarApoyo() {
    // Obtener los valores de todos los campos necesarios
    const pt = document.getElementById('pt-apoyo').value;
    const mb = document.getElementById('mb-apoyo').value;
    const cb = document.getElementById('cb-apoyo').value;
    const ar = document.getElementById('ar-apoyo').value;
    const pesoHabitual = document.getElementById('peso-habitual-apoyo').value;
    const talla = document.getElementById('talla-apoyo').value;
    const imc = document.getElementById('imc-apoyo').value;
    const imc_20_5 = document.querySelector('input[name="imc_20_5"]:checked')?.value;
    const perdida_peso = document.querySelector('input[name="perdida_peso"]:checked')?.value;
    const ingesta_reducida = document.querySelector('input[name="ingesta_reducida"]:checked')?.value;
    const paciente_grave = document.querySelector('input[name="paciente_grave"]:checked')?.value;
    const pesoActual = document.getElementById('peso-actual-apoyo').value;
    const csidpa = document.getElementById('csidpa').value;
    const csnopa = document.getElementById('csnopa').value;
    const grado1 = document.querySelector('input[name="grado1"]:checked')?.value;
    const grado2 = document.querySelector('input[name="grado2"]:checked')?.value;
    const grado3 = document.querySelector('input[name="grado3"]:checked')?.value;
    const severidad = document.querySelector('input[name="severidad"]:checked')?.value;
    const edad = document.querySelector('input[name="edad"]:checked')?.value;

    // Validar campos
    if (!pt || !mb || !cb || !ar || !pesoHabitual || !talla || !imc || !pesoActual || !csidpa || !csnopa) {
        swal('Error', 'Todos los campos obligatorios deben ser completados.', 'error');
        return;
    }

    // Enviar datos al backend
    $.ajax({
        type: 'POST',
        url: 'add_apoyo.php',
        data: {
            pt,
            mb,
            cb,
            ar,
            peso_habitual: pesoHabitual,
            talla,
            imc,
            peso_actual: pesoActual,
            csidpa,
            csnopa,
            imc_20_5,
            perdida_peso,
            ingesta_reducida,
            paciente_grave,
            grado1,
            grado2,
            grado3,
            severidad,
            edad
        },
        success: function (response) {
            swal('Agregado correctamente', 'Buen trabajo', 'success');
        },
        error: function () {
            swal('Error', 'No se pudo agregar el registro', 'error');
        },
    });
}
</script>


<button class="accordion">Solicitud de Alta Exigida</button>
<div class="panel">
    <br>
    <div class="table-header">
        <button class="register-btn" onclick="descargarAltaExigidaPDF()">Descargar Hoja de Solicitud de Alta Exigida</button>
        <button class="register-btn" onclick="registrarAltaExigida()">Registrar</button>
    </div>
    <br>
    <div class="table-responsive">
    <table class="responsive-table">
        <tbody>
            <tr>
                <td colspan="2">
                    <div class="input-container">
                        <label for="diagnostico_alta">Diagnóstico:</label>
                        <textarea id="diagnostico_alta" rows="3"></textarea>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td colspan="2">
                    <div class="input-container">
                        <label for="motivo">Motivo:</label>
                        <textarea id="motivo" rows="3"></textarea>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

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

<script>
function registrarAltaExigida() {
    const idpa = <?php echo $_GET['id']; ?>;
    const motivo = document.getElementById("motivo").value;
    const diagnostico = document.getElementById("diagnostico_alta").value;

    if (!motivo.trim() || !diagnostico.trim()) {
        swal('Error', 'Debe ingresar un motivo y un diagnóstico.', 'error');
        return;
    }

    $.ajax({
        type: "POST",
        url: "save_alta_exigida.php",
        data: { idpa, motivo, diagnostico },
        success: function (response) {
            if (response.success) {
                swal('Guardado', response.message, 'success').then(() => {
                    cargarDatosAlta();
                });
            } else {
                swal(response.type === "warning" ? 'Advertencia' : 'Error', response.message, response.type);
            }
        },
        error: function (xhr) {
            swal('Error', xhr.responseJSON?.message || 'Hubo un problema al registrar la solicitud.', 'error');
        }
    });
}

function cargarDatosAlta() {
    const idpa = <?php echo $_GET['id']; ?>;

    fetch(`fetch_motivo_alta.php?idpa=${idpa}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById("motivo").value = data.motivo || '';
                document.getElementById("diagnostico_alta").value = data.diagnostico || '';
            }
        })
        .catch(error => console.error("Error al cargar los datos:", error));
}

document.addEventListener("DOMContentLoaded", cargarDatosAlta);


// Descargar PDF de la solicitud de alta
function descargarAltaExigidaPDF() {
    const idpa = <?php echo $_GET['id']; ?>;

    // Verificar si hay motivo guardado antes de generar el PDF
    $.ajax({
        type: "GET",
        url: "check_motivo_alta.php",
        data: { idpa },
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (response.hasMotivo) {
                    // Si existe un motivo, generar el PDF
                    window.open(`generate_alta_pdf.php?idpa=${idpa}`, '_blank');
                } else {
                    // Mostrar advertencia si no hay motivo guardado
                    swal('Advertencia', 'No se puede generar la hoja de Solicitud de Alta Exigida ya que no has registrado el Dianostico y Motivo para completar la solicitud.', 'warning');
                }
            } else {
                swal('Error', response.message || 'Hubo un problema al verificar el motivo.', 'error');
            }
        },
        error: function (xhr) {
            swal('Error', 'No se pudo verificar el motivo. Intente nuevamente más tarde.', 'error');
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
const medidataPuedeAprobarSv = <?php echo !empty($medidataPuedeAprobarSignosVitales) ? 'true' : 'false'; ?>;
const medidataApproveSvUrl = 'approve_signos_vitales.php';

function svEstaAprobadoHistoria(item) {
    const txt = item.reviews_by != null ? String(item.reviews_by).trim() : '';
    if (txt !== '' && txt !== '-') return true;
    if (item.reviewed_at != null && String(item.reviewed_at).trim() !== '') return true;
    const rid = item.reviewed_by_user_id != null ? parseInt(item.reviewed_by_user_id, 10) : 0;
    return !isNaN(rid) && rid > 0;
}

function aprobarSignosVitalesRow(signoId) {
    var idpac = <?php echo (int)($_GET['id'] ?? 0); ?>;
    swal({
        title: '¿Aprobar registro?',
        text: 'Se registrarán su nombre y firma digital del perfil en "Revisado por".',
        icon: 'info',
        buttons: true,
        dangerMode: false
    }).then(function(ok) {
        if (!ok) return;
        $.ajax({
            type: 'POST',
            url: medidataApproveSvUrl,
            dataType: 'json',
            data: { signo_id: signoId, idpa: idpac },
            success: function(resp) {
                if (resp && resp.error) {
                    swal('Error', resp.error, 'error');
                    return;
                }
                swal('Listo', (resp && resp.message) ? resp.message : 'Aprobación guardada.', 'success');
                cargarSignosVitales();
            },
            error: function(xhr) {
                swal('Error', xhr.responseText || 'No se pudo aprobar.', 'error');
            }
        });
    });
}

function cargarSignosVitales() {
    const idpa = <?php echo $_GET['id']; ?>;

    $.ajax({
        type: "GET",
        url: "fetch_signos_vitales.php",
        data: { idpa: idpa },
        dataType: "json",
        success: function(result) {
            let content = '';
            result.forEach(item => {
                const pdfSvBtn =
                    '<button type="button" class="register-btn" title="PDF de este registro" onclick="descargarPDFSignoVital(' +
                    item.id +
                    ')">PDF</button>';
                const accFlexOpen = '<div style="display:flex;flex-direction:column;gap:10px;">';
                const accFlexClose = '</div>';
                let accBtns = '';
                if (!svEstaAprobadoHistoria(item)) {
                    if (medidataPuedeAprobarSv) {
                        accBtns =
                            accFlexOpen +
                            pdfSvBtn +
                            '<button type="button" class="register-btn" onclick="aprobarSignosVitalesRow(' +
                            item.id +
                            ')">Aprobar</button>' +
                            accFlexClose;
                    } else {
                        accBtns =
                            accFlexOpen +
                            pdfSvBtn +
                            '<button type="button" class="register-btn" disabled>Pendiente aprobación</button>' +
                            accFlexClose;
                    }
                } else {
                    accBtns =
                        accFlexOpen +
                        pdfSvBtn +
                        '<button type="button" class="register-btn" disabled>Registrados</button>' +
                        accFlexClose;
                }
                content += `
                    <tr>
                        <td>${item.fecha}</td>
                        <td>${item.hora}</td>
                        <td>${item.processed_by}</td>
                        <td>${item.reviews_by || '-'}</td>
                        <td>${item.weight ? parseFloat(item.weight) + '/' + (parseFloat(item.weight) * 2.20462).toFixed(2) : ''}</td>
                        <td>${item.stature ? parseFloat(item.stature) + '/' + (parseFloat(item.stature) / 2.54).toFixed(2) : ''}</td>
                        <td>${item.blood_pressure}</td>
                        <td>${item.map_pressure}</td>
                        <td>${item.heart_rate}</td>
                        <td>${item.respiratory_rate}</td>
                        <td>${item.oxygen_saturation}</td>
                        <td>${item.temperature ? parseFloat(item.temperature) + '/' + ((parseFloat(item.temperature) * 9/5) + 32).toFixed(1) : ''}</td>
                        <td>${item.glucose ? parseFloat(item.glucose) + '/' + (parseFloat(item.glucose) / 18).toFixed(2) : ''}</td>
                        <td>${accBtns}</td>
                    </tr>
                `;
            });

            $("#signosVitalesBody").html(content);
        },
        error: function(xhr) {
            console.error("Error al cargar los datos: " + xhr.responseText);
        }
    });
}

// Llamar a la función al cargar la página
$(document).ready(function() {
    cargarSignosVitales();
});
</script>

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
      if (this.id === "accordion-temperatura") cargarDatos();
    }
  });
}
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<?php include_once '../../backend/modal/md_geog.php' ?>
<?php include_once '../../backend/modal/md_consul.php' ?>
<?php include_once '../../backend/modal/md_trat.php' ?>
<?php include_once '../../backend/modal/md_temperatura.php' ?>
<?php include_once '../../backend/modal/md_san.php' ?>

<script type="text/javascript">
$(document).ready(function() {
    // Enviar formulario para Genogram
    $("#form").submit(function(event) {
        event.preventDefault(); // Evita el envío normal del formulario

        var geidpa = $("#geidpa").val();
        var genopa = $("#genopa").val();

        // Capturar valores de los nuevos campos
        var antecedentesFamiliares = $("#antecedentes-familiares").val();
        var alergias = $("#alergias").val();
        var medicamentosActuales = $("#medicamentos-actuales").val();
        var tipeoSanguineo = $("#tipeo-sanguineo").val();
        var antecedentesMedicos = JSON.stringify($("input[name='antecedentes_medicos[]']:checked").map(function() {
            return this.value;
        }).get());
        var notasMedicas = $("#notas-medicas").val();
        var complicacionesDiabetes = JSON.stringify($("input[name='complicaciones_diabetes[]']:checked").map(function() {
            return this.value;
        }).get());
        var notasDiabetes = $("#notas-diabetes").val();
        var enfermedadesCronicas = JSON.stringify($("input[name='enfermedades_cronicas[]']:checked").map(function() {
            return this.value;
        }).get());
        var notasCronicas = $("#notas-cronicas").val();
        var antecedentesQuirurgicos = JSON.stringify($("input[name='antecedentes_quirurgicos[]']:checked").map(function() {
            return this.value;
        }).get());
        var notasQuirurgicas = $("#notas-quirurgicas").val();
        var procesado_por = $("#procesado_por").val(); 

        // Validar que todos los campos estén llenos
        if (geidpa === '' || genopa === '') {
            swal(
                'Campos vacíos',
                'Por favor, complete todos los campos obligatorios.',
                'error'
            );
            return false;
        }

        // Enviar datos al backend usando AJAX
        $.ajax({
            type: "POST",
            url: "add_ge.php", // Archivo backend
            data: {
                pa1: geidpa,
                nomp1: genopa,
                antecedentes_familiares: antecedentesFamiliares,
                alergias: alergias,
                medicamentos_actuales: medicamentosActuales,
                tipeo_sanguineo: tipeoSanguineo,
                antecedentes_medicos: antecedentesMedicos,
                notas_medicas: notasMedicas,
                complicaciones_diabetes: complicacionesDiabetes,
                notas_diabetes: notasDiabetes,
                enfermedades_cronicas: enfermedadesCronicas,
                notas_cronicas: notasCronicas,
                antecedentes_quirurgicos: antecedentesQuirurgicos,
                notas_quirurgicas: notasQuirurgicas,
                procesado_por: procesadoPor
            },
            cache: false,
            dataType: "json", // Espera respuesta en JSON
            success: function(result) {
                if (result.error) {
                    swal(
                        'Error',
                        result.error,
                        'error'
                    );
                } else {
                    swal(
                        'Agregado correctamente',
                        'Buen trabajo.',
                        'success'
                    );
                    // Actualizar el contenido de la tabla
                    location.reload(); // Recargar la página para ver los nuevos datos
                }
            },
            error: function(xhr) {
                swal(
                    'Error',
                    'Ocurrió un problema: ' + xhr.responseText,
                    'error'
                );
            }
        });
        return false; // Evita recargar la página
    });
});
</script>

<script type="text/javascript">
    function enviar() {
        // Obtener los valores de todos los campos necesarios
        var consl = document.getElementById('consl').value;
        var csidpa = document.getElementById('csidpa').value;
        var csnopa = document.getElementById('csnopa').value;
        var medico_tratante = document.getElementById('medico_tratante').value;
        var especialidad = document.getElementById('especialidad').value;
        var servicio = document.getElementById('servicio').value;
        var habitacion_no = document.getElementById('habitacion_no').value;
        var fecha_hora_ingreso = document.getElementById('fecha_hora_ingreso').value;
        var fecha_hora_egreso = document.getElementById('fecha_hora_egreso').value;

        // Verificar si todos los campos tienen valores
        if (!consl || !csidpa || !csnopa || !medico_tratante || !especialidad || !servicio || !habitacion_no || !fecha_hora_ingreso || !fecha_hora_egreso) {
            swal(
                'Error',
                'Todos los campos son obligatorios',
                'error'
            );
            return;
        }

        // Crear el string con los datos
        var dataen = 
            'consl=' + encodeURIComponent(consl) + 
            '&csidpa=' + encodeURIComponent(csidpa) + 
            '&csnopa=' + encodeURIComponent(csnopa) +
            '&medico_tratante=' + encodeURIComponent(medico_tratante) + 
            '&especialidad=' + encodeURIComponent(especialidad) + 
            '&servicio=' + encodeURIComponent(servicio) +
            '&habitacion_no=' + encodeURIComponent(habitacion_no) +
            '&fecha_hora_ingreso=' + encodeURIComponent(fecha_hora_ingreso) +
            '&fecha_hora_egreso=' + encodeURIComponent(fecha_hora_egreso);

        // Enviar los datos al backend con AJAX
        $.ajax({
            type: "POST",
            url: "add_consut.php",
            data: dataen,
            cache: false,
            success: function (result) {
                swal(
                    'Agregado correctamente',
                    'Buen trabajo',
                    'success'
                );
            },
            error: function () {
                swal(
                    'Error',
                    'No se pudo agregar la consulta',
                    'error'
                );
            }
        });
    }
</script>

<script type="text/javascript">
    function trata(){
       var trat = document.getElementById('trat').value; 
       var tratdpa = document.getElementById('tratdpa').value; 
       var tratnopa = document.getElementById('tratnopa').value;

       var dataens = 'trat='+trat +'&tratdpa='+tratdpa +'&tratnopa='+tratnopa;

       $.ajax({
                    type: "POST", //definimos el método de envío
                    url: "add_trat.php", //el archivo al cual se enviaran
                    data:dataens,
                    cache: false,
                    success: function(result){

                    swal(
                            'Agregado correctamente',
                            'Buen trabajo',
                            'success'
                          )
}
                }); 
    };
</script>

<script type="text/javascript">

function getIdpa() {
    const idpa = document.getElementById('idpa')?.value || '';
    if (!idpa) {
        swal('Error', 'El ID del paciente no se encontró.', 'error');
        throw new Error('ID del paciente no definido.');
    }
    return idpa;
}

    function registrarHospitalizacion() {
        const idpa = document.getElementById('idpa').value; // Obtener ID del paciente
        if (!idpa) {
            swal('Error', 'ID del paciente no encontrado.', 'error');
            return;
        }

        // Recopilar datos dinámicamente
        const fields = [
            'oxigenoInicio', 'oxigenoHora', 'oxigenoFinaliza', 'oxigenoObservacion',
            'colchonInicio', 'colchonHora', 'colchonFinaliza', 'colchonObservacion',
            'reservorioInicio', 'reservorioHora', 'reservorioFinaliza', 'reservorioObservacion',
            'monitorInicio', 'monitorHora', 'monitorFinaliza', 'monitorObservacion',
            'nebulizacionInicio', 'nebulizacionHora', 'nebulizacionFinaliza', 'nebulizacionObservacion',
            'succionInicio', 'succionHora', 'succionFinaliza', 'succionObservacion', 'oxigenoTurno', 'colchonTurno', 'reservorioTurno', 'monitorTurno',
            'nebulizacionTurno', 'succionTurno'
        ];

        const data = { idpa }; // Incluir ID del paciente
        fields.forEach((field) => {
            const value = document.getElementById(field)?.value || '';
            if (value) {
                data[field] = value; // Solo incluir campos con valores
            }
        });

        // Verificar si hay datos además de `idpa`
        if (Object.keys(data).length === 1) { // Solo `idpa` significa que no hay datos adicionales
            swal('Error', 'Debe completar al menos un campo antes de guardar.', 'error');
            return;
        }

        // Enviar datos al backend
        $.ajax({
            type: "POST",
            url: "save_hospitalizacion.php",
            data: data,
            cache: false,
            success: function (response) {
                swal('Guardado', 'Datos registrados correctamente.', 'success');
                actualizarVista(); // Refrescar datos
            },
            error: function (response) {
                const error = response.responseJSON?.error || 'No se pudieron guardar los datos.';
                swal('Error', error, 'error');
            }
        });
    }

    function actualizarVista() {
        const idpa = document.getElementById('idpa').value; // Obtener ID del paciente
        if (!idpa) {
            console.error('ID del paciente no definido.');
            return;
        }

        // Recuperar datos del backend
        $.ajax({
            type: "GET",
            url: `fetch_hospitalizacion.php?idpa=${idpa}`,
            cache: false,
            success: function (response) {
                if (response && response.message !== "No se encontraron registros para este paciente.") {
                    Object.keys(response).forEach((key) => {
                        const field = document.getElementById(key);
                        if (field) {
                            field.value = response[key] || ''; // Actualizar campos con valores guardados
                        }
                    });
                }
            },
            error: function () {
                console.error('Error al actualizar la vista.');
            }
        });
    }

    // Llamar a actualizarVista al cargar la página
    document.addEventListener('DOMContentLoaded', function () {
        actualizarVista();
    });
</script>



</body>
</html>


