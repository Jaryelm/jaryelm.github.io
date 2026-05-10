<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="../../backend/css/admin.css">
  <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

  <!-- DataTables CSS -->
  <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
  <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
  <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">

  <title>Registros UCI</title>
</head>
<body>
  
<?php include_once '../admin/menu.php'; ?>

<!-- NAVBAR -->
<section id="content">
  <nav>
      <i class='bx bx-menu toggle-sidebar'></i>
      <form action="#">
          <div class="form-group"></div>
      </form>
      <span class="divider"></span>
      <?php include_once '../admin/perfil.php'; ?>
  </nav>
  <!-- MAIN -->
  <main>
    <?php
    // Obtener la hora actual para el saludo
    $hora_actual = date('H');
    if ($hora_actual >= 6 && $hora_actual < 12) {
        $saludo = "Buenos Días";
    } elseif ($hora_actual >= 12 && $hora_actual < 18) {
        $saludo = "Buenas Tardes";
    } else {
        $saludo = "Buenas Noches";
    }
    ?>
    <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

    <button class="button" onclick="cambiarColor(this, '../pacientes/uci.php')">Regresar al Formulario UCI</button>

    <br>

<?php
// Consulta para obtener los registros de la tabla cuidados_intensivos
$stmt = $connect->prepare("SELECT * FROM cuidados_intensivos ORDER BY fecha_creacion DESC");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_OBJ); // O PDO::FETCH_ASSOC si prefieres un array asociativo
?>
    
<!-- Tabla de Registros UCI - Datos Generales -->
<div class="data">
  <div class="content-data">
    <div class="head">
      <h3>Registros UCI - Datos Generales</h3>
    </div>
    <div class="table-responsive" style="overflow-x:auto;">
      <?php if(count($data) > 0): ?>
        <table id="uciTableGeneral" class="responsive-table">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Nombre del Paciente</th>
              <th scope="col">Edad</th>
              <th scope="col">Fecha de Registro</th>
              <th scope="col">DX</th>
              <th scope="col">Médico</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($data as $d): ?>
              <tr>
                <td><?php echo htmlspecialchars($d->id); ?></td>
                <td><?php echo htmlspecialchars($d->nombre_paciente); ?></td>
                <td><?php echo htmlspecialchars($d->edad); ?></td>
                <td><?php echo htmlspecialchars($d->fecha_registro); ?></td>
                <td><?php echo htmlspecialchars($d->dx_paciente); ?></td>
                <td><?php echo htmlspecialchars($d->medico_paciente); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="alert">
          <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
          <strong>Alerta!</strong> No hay registros disponibles.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Tabla de Registros UCI - Parámetros Adicionales -->
<div class="data">
  <div class="content-data">
    <div class="head">
      <h3>Registros UCI - Parámetros Adicionales</h3>
    </div>
    <div class="table-responsive" style="overflow-x:auto;">
      <?php if(count($data) > 0): ?>
        <table id="uciTableAdicional" class="responsive-table">
          <thead>
            <tr>
              <th scope="col">Peso (kg)</th>
              <th scope="col">Talla (cm)</th>
              <th scope="col">Sonda Foley</th>
              <th scope="col">SNG</th>
              <th scope="col">Días Hospitalizado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($data as $d): ?>
              <tr>
                <td><?php echo htmlspecialchars($d->peso_kg); ?></td>
                <td><?php echo htmlspecialchars($d->talla_cm); ?></td>
                <td><?php echo htmlspecialchars($d->sonda_foley); ?></td>
                <td><?php echo htmlspecialchars($d->sng); ?></td>
                <td><?php echo htmlspecialchars($d->dias_hospitalizacion); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="alert">
          <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
          <strong>Alerta!</strong> No hay registros disponibles.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Tabla de Registros UCI - Signos Vitales -->
<div class="data">
  <div class="content-data">
    <div class="head">
      <h3>Registros UCI - Signos Vitales</h3>
    </div>
    <div class="table-responsive" style="overflow-x:auto;">
      <?php if(count($data) > 0): ?>
        <table id="uciTableVital" class="responsive-table">
          <thead>
            <tr>
              <th scope="col">Ventilación Mecanica</th>
              <th scope="col">Monitor Cardiaco</th>
              <th scope="col">Presión Arterial</th>
              <th scope="col">F.C.</th>
              <th scope="col">F.R.</th>
              <th scope="col">Temperatura</th>
              <th scope="col">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($data as $d): ?>
              <tr>
                <td><?php echo htmlspecialchars($d->ventilacion_mecanica); ?></td>
                <td><?php echo htmlspecialchars($d->monitor_cardiaco); ?></td>
                <td><?php echo htmlspecialchars($d->presion_arterial); ?></td>
                <td><?php echo htmlspecialchars($d->frecuencia_cardiaca); ?></td>
                <td><?php echo htmlspecialchars($d->frecuencia_respiratoria); ?></td>
                <td><?php echo htmlspecialchars($d->temperatura); ?></td>
                <td>
                  <button class="register-btn" onclick="descargarUCIPDF(<?php echo htmlspecialchars($d->id, ENT_QUOTES, 'UTF-8'); ?>)">Descargar Registros</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="alert">
          <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span> 
          <strong>Alerta!</strong> No hay registros disponibles.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>


<!-- Estilo para el boton "Acciones" -->
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

<script>
function descargarUCIPDF(id) {
    if (id) {
        window.open(`generate_uci_pdf.php?id=${id}`, '_blank');
    } else {
        swal('Error', 'No se proporcionó un ID válido.', 'error');
    }
}
</script>

  </main>
  <!-- MAIN -->
</section>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

<!-- NAVBAR -->
<script src="../../backend/js/jquery.min.js"></script>
<!-- DataTables Scripts -->
<script src="../../backend/js/datatable.js"></script>
<script src="../../backend/js/datatablebuttons.js"></script>
<script src="../../backend/js/jszip.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/js/buttonshtml5.js"></script>
<script src="../../backend/js/buttonsprint.js"></script>
<script>
$(document).ready(function() {
    $('#uciTable').DataTable({
        scrollX: true,  // Esto habilita el scroll horizontal
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        order: [[14, 'desc']], // Ajusta según la columna de orden
        language: {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        }
    });
});
</script>
</body>
</html>
