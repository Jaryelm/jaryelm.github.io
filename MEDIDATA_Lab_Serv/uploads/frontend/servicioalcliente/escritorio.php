<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<?php
require_once('../../backend/bd/Conexion.php');

// Consulta con JOIN para obtener datos relacionados
$req = $connect->prepare("
    SELECT 
        e.id, 
        e.title, 
        e.start, 
        e.end, 
        e.color, 
        p.nompa AS patient_name, 
        p.apepa AS patient_surname, 
        d.nodoc AS doctor_name, 
        d.apdoc AS doctor_surname, 
        d.nomesp AS specialty, 
        l.nomlab AS area_name,
        e.room_number,
        e.insurer,
        e.policy_number,
        e.certificate_number,
        e.surgery,
        e.hospitalization,
        e.assistant,
        e.anesthetist,
        e.circulating,
        e.technician,
        e.instrumentist,
        e.evaluation
    FROM events e
    INNER JOIN patients p ON e.idpa = p.idpa
    INNER JOIN doctor d ON e.idodc = d.idodc
    INNER JOIN laboratory l ON e.idlab = l.idlab
");
$req->execute();
$events = $req->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- DataTables -->
        <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4.css" />
        <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4-custom.css" />
        <link href="../../backend/vendor/datatables/buttons.bs.css" rel="stylesheet" />

    <!-- FullCalendar -->
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <style>
        #calendar-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            width: 100%;
        }
        #calendar {
            flex: 1;
            max-width: 60%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background-color: #fff;
        }
        #notification-panel {
            flex: 1;
            max-width: 35%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
            overflow-y: auto;
            max-height: 75.5vh;
        }
        #notification-panel h4 {
            margin-bottom: 10px;
        }
        .notification-item {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
        }
        .notification-item.occupied {
            background-color: #ffdddd;
        }
        .notification-item.available {
            background-color: #ddffdd;
        }
        #weekly-status, #future-events, #past-events {
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            #calendar-container {
                flex-direction: column;
                gap: 15px;
            }
            #calendar, #notification-panel {
                max-width: 100%;
                flex: none;
            }
        }
    </style>

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


<!-- Dashboard Caja Start -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<style>
    /* Estilo previo adaptado */

body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    color: #000;
}

.dashboard-container {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 20px;
}

header {
    text-align: center;
    margin-bottom: 20px;
}

.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.card {
    background-color: #06adbf;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    color: #fff;
    transition: transform 0.3s ease;
}

.card h2 {
    margin-top: 0;
    color: #fff;
}

.card p {
    color: #f4f4f4;
}

.card:hover {
    transform: translateY(-5px);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
}

table th {
    background-color: #06adbf;
    color: #fff;
}

@media (max-width: 768px) {
    .dashboard {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}
</style>

<?php
// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Corregir la fecha mala
$hoyInicio = date('Y-m-d 00:00:00');
$hoyFin = date('Y-m-d 23:59:59');

// Consultar datos para el Dashboard
$totalFacturas = $connect->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$facturasCobradas = $connect->query("SELECT COUNT(*) FROM orders WHERE invoice_status = 'Cobrada'")->fetchColumn();
$facturasPendientes = $connect->query("SELECT COUNT(*) FROM orders WHERE invoice_status = 'Pendiente'")->fetchColumn();

$ultimasEntregadas = $connect->query("
    SELECT invoice_number, nomcl, tipo, processed_by, placed_on, total_price 
    FROM orders 
    WHERE invoice_status = 'Cobrada' 
    ORDER BY placed_on DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$ultimasPendientes = $connect->query("
    SELECT invoice_number, nomcl, tipo, processed_by, placed_on, total_price 
    FROM orders 
    WHERE invoice_status = 'Pendiente' 
    ORDER BY placed_on DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$facturasHospitalizacion = $connect->query("
    SELECT invoice_number, nomcl, tipo, processed_by, placed_on, total_price 
    FROM orders 
    WHERE invoice_status = 'Pendiente' AND tipo = 'Hospitalizado' 
    ORDER BY placed_on DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Ventas Diarias Ajustadas
$ventasDiarias = $connect->prepare("
    SELECT SUM(total_price) AS total 
    FROM orders 
    WHERE placed_on >= ? 
      AND placed_on <= ? 
      AND invoice_status = 'Cobrada'
");
$ventasDiarias->execute([$hoyInicio, $hoyFin]);
$ventasDiarias = $ventasDiarias->fetchColumn();

if ($ventasDiarias === null) {
    $ventasDiarias = 0;
}

$ventasSemanales = $connect->query("
    SELECT SUM(total_price) AS total 
    FROM orders 
    WHERE YEARWEEK(placed_on, 1) = YEARWEEK(CURDATE(), 1)
")->fetchColumn();

$ventasMensuales = $connect->query("
    SELECT SUM(total_price) AS total 
    FROM orders 
    WHERE MONTH(placed_on) = MONTH(CURDATE()) AND YEAR(placed_on) = YEAR(CURDATE())
")->fetchColumn();

$ventasMesPasado = $connect->query("
    SELECT SUM(total_price) AS total 
    FROM orders 
    WHERE placed_on >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) 
      AND placed_on < DATE_FORMAT(CURDATE(), '%Y-%m-01')
")->fetchColumn();

$ventasAnuales = $connect->query("
    SELECT SUM(total_price) AS total 
    FROM orders 
    WHERE YEAR(placed_on) = YEAR(CURDATE())
")->fetchColumn();

$ventasGlobales = $connect->query("
    SELECT SUM(total_price) AS total 
    FROM orders 
    WHERE invoice_status = 'Cobrada'
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Consultorios</h1>
        </header>
        <div class="dashboard">
            <div class="card">
                <h2>Consultorio Disponible</h2>
                <p><?php echo $totalFacturas; ?></p>
            </div>
            <div class="card">
                <h2>Consultorio Ocupado</h2>
                <p><?php echo $facturasCobradas; ?></p>
            </div>
            <div class="card">
                <h2>Consultorio Pendiente</h2>
                <p><?php echo $facturasPendientes; ?></p>
            </div>
            <div class="card">
                <h2>Consultorio Hoy</h2>
                <p>LPS <?php echo number_format($ventasDiarias, 2); ?></p>
            </div>
        </div>

        <br>

        <header>
            <h1>Sala de Procedimientos</h1>
        </header>
        <div class="dashboard">
            <div class="card">
                <h2>Sala Disponible</h2>
                <p><?php echo $totalFacturas; ?></p>
            </div>
            <div class="card">
                <h2>Sala Ocupada</h2>
                <p><?php echo $facturasCobradas; ?></p>
            </div>
            <div class="card">
                <h2>Sala Pendiente</h2>
                <p><?php echo $facturasPendientes; ?></p>
            </div>
            <div class="card">
                <h2>Sala Hoy</h2>
                <p>LPS <?php echo number_format($ventasDiarias, 2); ?></p>
            </div>
        </div>

        <br>

        <header>
            <h1>Emergencia</h1>
        </header>
        <div class="dashboard">
            <div class="card">
                <h2>Emergencia Disponible</h2>
                <p><?php echo $totalFacturas; ?></p>
            </div>
            <div class="card">
                <h2>Emergencia Ocupada</h2>
                <p><?php echo $facturasCobradas; ?></p>
            </div>
            <div class="card">
                <h2>Emergencia Pendiente</h2>
                <p><?php echo $facturasPendientes; ?></p>
            </div>
            <div class="card">
                <h2>Emergencia Hoy</h2>
                <p>LPS <?php echo number_format($ventasDiarias, 2); ?></p>
            </div>
        </div>


</body>
</html>

<!-- Dashboard Caja End -->

<?php
// Obtener todos los médicos sin límite
$sentencia_medicos = $connect->prepare("SELECT * FROM doctor ORDER BY idodc DESC;");
$sentencia_medicos->execute();
$data_medicos = [];
while ($r = $sentencia_medicos->fetchObject()) {
    $data_medicos[] = $r;
}

// Obtener todos los pacientes sin límite
$sentencia_pacientes = $connect->prepare("SELECT * FROM patients ORDER BY idpa DESC;");
$sentencia_pacientes->execute();
$data_pacientes = [];
while ($r = $sentencia_pacientes->fetchObject()) {
    $data_pacientes[] = $r;
}
?>

<div class="data">
    <div class="content-data">
        <div class="table-responsive" style="overflow-x:auto;">
            <div class="search-container">
                <i class='bx bx-search icon'></i>
                <input type="text" id="searchInput_medicos" placeholder="Buscar Médicos...">
            </div>

            <table id="search_m" class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col" colspan="2">Médicos</th>
                    </tr>
                </thead>
                <tbody id="medicos_body">
                    <?php
                    // Mostrar solo 5 médicos inicialmente
                    $limit = 5;
                    for ($i = 0; $i < min($limit, count($data_medicos)); $i++) {
                        $d = $data_medicos[$i];
                    ?>
                        <tr>
                            <td data-title="Paciente"><?php echo htmlspecialchars($d->nodoc . ' ' . $d->apdoc); ?></td>
                            <td data-title="Especialidad"><?php echo htmlspecialchars($d->nomesp); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if (count($data_medicos) == 0): ?>
                <div class="alert">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <strong>Danger!</strong> No hay datos.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="content-data">
        <div class="table-responsive" style="overflow-x:auto;">
            <div class="search-container">
                <i class='bx bx-search icon'></i>
                <input type="text" id="searchInput_pacientes" placeholder="Buscar pacientes...">
            </div>

            <table id="search_p" class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Pacientes</th>
                    </tr>
                </thead>
                <tbody id="pacientes_body">
                    <?php
                    // Mostrar solo 5 pacientes inicialmente
                    for ($i = 0; $i < min($limit, count($data_pacientes)); $i++) {
                        $d = $data_pacientes[$i];
                    ?>
                        <tr>
                            <td data-title="Paciente"><?php echo htmlspecialchars($d->nompa . ' ' . $d->apepa); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <?php if (count($data_pacientes) == 0): ?>
                <div class="alert">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <strong>Danger!</strong> No hay datos.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInputMedicos = document.getElementById('searchInput_medicos');
    const medicosBody = document.getElementById('medicos_body');

    const searchInputPacientes = document.getElementById('searchInput_pacientes');
    const pacientesBody = document.getElementById('pacientes_body');

    const medicosData = <?php echo json_encode($data_medicos); ?>;
    const pacientesData = <?php echo json_encode($data_pacientes); ?>;

    function filterTable(input, tbody, data) {
        const searchTerm = input.value.trim().toLowerCase();
        const filteredData = data.filter(item => {
            return JSON.stringify(item).toLowerCase().includes(searchTerm);
        });

        tbody.innerHTML = '';

        if (searchTerm === '') {
            // Mostrar solo 5 registros si no hay término de búsqueda
            const limit = 5;
            for (let i = 0; i < Math.min(limit, data.length); i++) {
                const item = data[i];
                const row = document.createElement('tr');
                if (item.nodoc && item.apdoc && item.nomesp) {
                    row.innerHTML = `
                        <td data-title="Paciente">${item.nodoc} ${item.apdoc}</td>
                        <td data-title="Especialidad">${item.nomesp}</td>
                    `;
                } else if (item.nompa && item.apepa) {
                    row.innerHTML = `
                        <td data-title="Paciente">${item.nompa} ${item.apepa}</td>
                    `;
                }
                tbody.appendChild(row);
            }
        } else {
            // Mostrar todos los registros que coincidan con el término de búsqueda
            for (const item of filteredData) {
                const row = document.createElement('tr');
                if (item.nodoc && item.apdoc && item.nomesp) {
                    row.innerHTML = `
                        <td data-title="Paciente">${item.nodoc} ${item.apdoc}</td>
                        <td data-title="Especialidad">${item.nomesp}</td>
                    `;
                } else if (item.nompa && item.apepa) {
                    row.innerHTML = `
                        <td data-title="Paciente">${item.nompa} ${item.apepa}</td>
                    `;
                }
                tbody.appendChild(row);
            }
        }

        if (filteredData.length === 0 && searchTerm !== '') {
            tbody.innerHTML = '<tr><td colspan="2">No se encontraron resultados</td></tr>';
        }
    }

    searchInputMedicos.addEventListener('input', function() {
        filterTable(searchInputMedicos, medicosBody, medicosData);
    });

    searchInputPacientes.addEventListener('input', function() {
        filterTable(searchInputPacientes, pacientesBody, pacientesData);
    });
});
</script>

<!-- Calendiario Citas programadas -->

<div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Programación</h3>
                       
                    </div>
                    <div id="calendar-container">
                        <!-- Calendario -->
                        <div id="calendar" class="col-centered"></div>

                        <!-- Panel de Notificaciones -->
                        <div id="notification-panel">
                            <h4>Notificaciones</h4>
                            <div id="notifications">
                                <p>No hay eventos disponibles.</p>
                            </div>

                            <!-- Estado Semanal -->
                            <div id="weekly-status">
                                <h4>Estado Semanal</h4>
                                <div id="weekly-occupancy">
                                    <p>Cargando estado semanal...</p>
                                </div>
                            </div>

                            <!-- Eventos Futuros -->
                            <div id="future-events">
                                <h4>Eventos Proximos</h4>
                                <div id="future-occupancy">
                                    <p>Cargando eventos Proximos...</p>
                                </div>
                            </div>

                            <!-- Eventos Antiguos -->
                            <div id="past-events">
                                <h4>Eventos Antiguos</h4>
                                <div id="past-occupancy">
                                    <p>Cargando eventos antiguos...</p>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
        </div>


        </main>
        <!-- MAIN -->
    </section>
    <!-- NAVBAR -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <script src="/backend/vendor/apexcharts/apexcharts.min.js"></script>
    <script src="../../backend/js/script.js"></script>

    <!-- Data Tables -->
    <script src="../../backend/vendor/datatables/dataTables.min.js"></script>
    <script src="../../backend/vendor/datatables/dataTables.bootstrap.min.js"></script>


    <!-- Custom Data tables -->
    <script src="../../backend/vendor/datatables/custom/custom-datatables.js"></script>
    <script src="../../backend/vendor/datatables/custom/fixedHeader.js"></script>


    <!-- FullCalendar -->
    <script src='../../backend/js/moment.min.js'></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.min.js'></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.js'></script>
    <script src='../../backend/js/fullcalendar/locale/es.js'></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>
    
    <!-- Busquedas Tablas -->
    <script src="../../backend/js/search_m.js"></script>
    <script src="../../backend/js/search_p.js"></script>

    <script>

$(document).ready(function () {
  var date = new Date();
  var yyyy = date.getFullYear().toString();
  var mm = (date.getMonth() + 1).toString().padStart(2, '0');
  var dd = date.getDate().toString().padStart(2, '0');

  $('#calendar').fullCalendar({
    header: {
      language: 'es',
      left: 'prev,next today',
      center: 'title',
      right: 'month,basicWeek,basicDay',
    },
    defaultDate: `${yyyy}-${mm}-${dd}`,
    editable: true,
    eventLimit: true,
    selectable: true,
    selectHelper: true,
    events: [
      <?php foreach($events as $event): 
      $start = explode(" ", $event['start']);
      $end = explode(" ", $event['end']);
      if($start[1] == '00:00:00'){
        $start = $start[0];
      }else{
        $start = $event['start'];
      }
      if($end[1] == '00:00:00'){
        $end = $end[0];
      }else{
        $end = $event['end'];
      }
      ?>
    {
        id: '<?php echo $event['id']; ?>',
        title: '<?php echo $event['title']; ?>',
        start: '<?php echo $event['start']; ?>',
        end: '<?php echo $event['end']; ?>',
        color: '<?php echo $event['color']; ?>',
        patient: '<?php echo $event['patient_name'] . ' ' . $event['patient_surname']; ?>',
        doctor: '<?php echo $event['doctor_name'] . ' ' . $event['doctor_surname']; ?>',
        specialty: '<?php echo $event['specialty']; ?>',
        area: '<?php echo $event['area_name']; ?>',
        room_number: '<?php echo $event['room_number'] ?? "N/A"; ?>',
        insurer: '<?php echo $event['insurer'] ?? "N/A"; ?>',
        policy_number: '<?php echo $event['policy_number'] ?? "N/A"; ?>',
        certificate_number: '<?php echo $event['certificate_number'] ?? "N/A"; ?>',
        surgery: '<?php echo $event['surgery'] ?? "N/A"; ?>',
        hospitalization: '<?php echo $event['hospitalization'] ?? "N/A"; ?>',
        assistant: '<?php echo $event['assistant'] ?? "N/A"; ?>',
        anesthetist: '<?php echo $event['anesthetist'] ?? "N/A"; ?>',
        circulating: '<?php echo $event['circulating'] ?? "N/A"; ?>',
        technician: '<?php echo $event['technician'] ?? "N/A"; ?>',
        instrumentist: '<?php echo $event['instrumentist'] ?? "N/A"; ?>',
        evaluation: '<?php echo $event['evaluation'] ?? "N/A"; ?>'
    },
      <?php endforeach; ?>
    ],
    eventRender: function (event, element) {
      element.bind('click', function () {
        updateNotificationPanel(event);
      });
    },
    viewRender: function(view) {
      updateWeeklyOccupancy(view);
      updateFutureEvents(view);
      updatePastEvents(view);
    }
  });

  function updateNotificationPanel(event) {
    const notifications = $('#notifications');
    notifications.empty();

    // Capturar la hora actual en UTC
    const now = moment();

    // Fechas desde la base de datos, usadas directamente para la visualización
    const startDate = moment(event.start, 'YYYY-MM-DD HH:mm:ss'); // Hora exacta de la tabla
    const endDate = moment(event.end, 'YYYY-MM-DD HH:mm:ss');     // Hora exacta de la tabla

    // Fechas ajustadas para el cálculo del estado
    const startDateAdjusted = startDate.clone().add(6, 'hours');
    const endDateAdjusted = endDate.clone().add(6, 'hours');

    // Formatear las fechas para la visualización
    const formattedStart = startDate.format('dddd, MMMM D [de] YYYY, HH:mm');
    const formattedEnd = endDate.format('dddd, MMMM D [de] YYYY, HH:mm');

    // Calcular la duración del evento
    const duration = endDate.diff(startDate, 'minutes');
    const durationText = duration >= 60 
        ? `${Math.floor(duration / 60)} horas ${duration % 60} minutos` 
        : `${duration} minutos`;

    // Determinar el estado del evento usando las fechas ajustadas
    let eventStatus = '';
    if (now.isBefore(startDateAdjusted)) {
        // Evento aún no ha comenzado
        const timeUntil = moment.duration(startDateAdjusted.diff(now));
        const days = Math.floor(timeUntil.asDays());
        const hours = timeUntil.hours();
        const minutes = timeUntil.minutes();

        if (days > 0) {
            eventStatus = `Faltan ${days} días, ${hours} horas y ${minutes} minutos para que comience`;
        } else if (hours > 0) {
            eventStatus = `Faltan ${hours} horas y ${minutes} minutos para que comience`;
        } else {
            eventStatus = `Faltan ${minutes} minutos para que comience`;
        }
    } else if (now.isBetween(startDateAdjusted, endDateAdjusted)) {
        // Evento en curso
        const remainingTime = moment.duration(endDateAdjusted.diff(now));
        const hours = Math.floor(remainingTime.asHours());
        const minutes = remainingTime.minutes();

        eventStatus = `El evento está en curso y finaliza en ${hours} horas y ${minutes} minutos`;
    } else {
        // Evento finalizado
        eventStatus = 'El evento ya finalizó';
    }

  // Función para convertir códigos de color a nombres
  function getColorName(colorCode) {
    const colorMap = {
      '#0071c5': 'Azul oscuro',
      '#FF4500': 'Rojo',
      '#EE82EE': 'Violeta',
    };
    return colorMap[colorCode] || 'Desconocido';
  }

  const colorName = getColorName(event.color);

  // Agregar la notificación
  const notificationElement = $(`
        <div class="notification-item occupied" style="background-color: ${event.color}; color: #fff; border: none;">
            <strong>${event.title}</strong>
            <p>Paciente: ${event.patient}</p>
            <p>Médico: ${event.doctor}</p>
            <p>Especialidad: ${event.specialty}</p>
            <p>Área: ${event.area}</p>
            <p>Inicio: ${formattedStart}</p>
            <p>Fin: ${formattedEnd}</p>
            <p>Duración: ${durationText}</p>
            <p>${eventStatus}</p>
        </div>
    `);

    notificationElement.on('click', function () {
    // Llenar la tabla del modal con los campos nuevos
    $('#modal-title').text(event.title || 'N/A');
    $('#modal-patient').text(event.patient || 'N/A');
    $('#modal-doctor').text(event.doctor || 'N/A');
    $('#modal-specialty').text(event.specialty || 'N/A');
    $('#modal-area').text(event.area || 'N/A');
    $('#modal-start').text(formattedStart);
    $('#modal-end').text(formattedEnd);
    $('#modal-duration').text(durationText);
    $('#modal-status').text(eventStatus);

    // Campos adicionales
    $('#modal-room-number').text(event.room_number || 'N/A');
    $('#modal-insurer').text(event.insurer || 'N/A');
    $('#modal-policy-number').text(event.policy_number || 'N/A');
    $('#modal-certificate-number').text(event.certificate_number || 'N/A');

    // Nuevos campos
    $('#modal-surgery').text(event.surgery || 'N/A');
    $('#modal-hospitalization').text(event.hospitalization || 'N/A');
    $('#modal-assistant').text(event.assistant || 'N/A');
    $('#modal-anesthetist').text(event.anesthetist || 'N/A');
    $('#modal-circulating').text(event.circulating || 'N/A');
    $('#modal-technician').text(event.technician || 'N/A');
    $('#modal-instrumentist').text(event.instrumentist || 'N/A');
    $('#modal-evaluation').text(event.evaluation || 'N/A');

    $('#eventModal').fadeIn();
});

notifications.append(notificationElement);
}

// Lógica para cerrar el modal al hacer clic en la "X"
$(document).on('click', '.close', function () {
$('#eventModal').fadeOut();
});

// Lógica para cerrar el modal al hacer clic fuera del contenido
$(document).on('click', '#eventModal', function (e) {
if ($(e.target).is('#eventModal')) {
  $('#eventModal').fadeOut();
}
});

function updateWeeklyOccupancy(view) {
const weeklyOccupancy = $('#weekly-occupancy');
weeklyOccupancy.empty();

<?php
// Mapeo de días de la semana de inglés a español
$daysMapping = [
    'Monday' => 'Lunes',
    'Tuesday' => 'Martes',
    'Wednesday' => 'Miércoles',
    'Thursday' => 'Jueves',
    'Friday' => 'Viernes',
    'Saturday' => 'Sábado',
    'Sunday' => 'Domingo'
];

$weeklyEvents = array();

// Obtener el lunes y domingo de la semana actual
$now = new DateTime();
$weekStart = (clone $now)->modify(('Monday' === $now->format('l')) ? 'this Monday' : 'last Monday'); // Primer día (lunes)
$weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59); // Último día (domingo)

foreach ($events as $event) {
    $eventDate = new DateTime($event['start']);

    // Filtrar eventos dentro de la semana actual
    if ($eventDate >= $weekStart && $eventDate <= $weekEnd) {
        $day = $eventDate->format('l'); // Día en inglés
        $dayInSpanish = $daysMapping[$day] ?? $day; // Traducir al español
        if (!isset($weeklyEvents[$dayInSpanish])) {
            $weeklyEvents[$dayInSpanish] = 0;
        }
        $weeklyEvents[$dayInSpanish]++;
    }
}
?>
<?php foreach ($weeklyEvents as $day => $count): ?>
  weeklyOccupancy.append(`
    <div class="notification-item occupied">
      <strong><?php echo $day; ?></strong>
      <p><?php echo $count; ?> eventos programados</p>
    </div>
  `);
<?php endforeach; ?>
}


function updateFutureEvents(view) {
const futureOccupancy = $('#future-occupancy');
futureOccupancy.empty();

<?php 
// Configurar localización en español (asegúrate de que el idioma esté instalado en el sistema)
setlocale(LC_TIME, 'es_ES.UTF-8'); // Esto afecta funciones nativas de PHP, pero no es necesario para IntlDateFormatter

foreach ($events as $event): 
    $eventDate = new DateTime($event['start']);
    $now = new DateTime();
    if ($eventDate > $now):
        $diff = $now->diff($eventDate); // Diferencia de tiempo
        $days = $diff->days; // Diferencia en días
        $hours = $diff->h;   // Diferencia en horas
        $minutes = $diff->i; // Diferencia en minutos

        // Usar IntlDateFormatter para obtener el mes en español
        if (class_exists('IntlDateFormatter')) {
            $formatter = new IntlDateFormatter(
                'es_ES',
                IntlDateFormatter::LONG,
                IntlDateFormatter::NONE,
                null,
                null,
                'MMMM'
            );
            $month = $formatter->format($eventDate); // Mes en español
        } else {
            $month = 'Mes desconocido'; // Fallback en caso de que Intl no esté disponible
        }

        $day = $eventDate->format('d');  // Día numérico
        $formattedDate = $eventDate->format('Y-m-d H:i'); // Fecha completa

        // Calcular tiempo restante
        $timeUntil = $days > 0 
            ? "Faltan {$days} días, {$hours} horas y {$minutes} minutos" 
            : ($hours > 0 
                ? "Faltan {$hours} horas y {$minutes} minutos" 
                : "Faltan {$minutes} minutos");
?>
    futureOccupancy.append(`
      <div class="notification-item occupied" style="background-color: <?php echo $event['color']; ?>; color: #fff; border: none;">
        <strong><?php echo $event['title']; ?></strong>
        <p>Inicio: <?php echo $formattedDate; ?></p>
        <p>Mes: <?php echo ucfirst($month); ?></p>
        <p>Día: <?php echo $day; ?></p>
        <p><?php echo $timeUntil; ?></p>
      </div>
    `);
<?php 
    endif; 
  endforeach; 
?>
}

  function updatePastEvents(view) {
    const pastOccupancy = $('#past-occupancy');
    pastOccupancy.empty();
    <?php
    foreach($events as $event) {
      $eventDate = new DateTime($event['start']);
      $now = new DateTime();
      if ($eventDate < $now) {
        echo "pastOccupancy.append(`<div class='notification-item available'><strong>{$event['title']}</strong><p>{$event['start']}</p></div>`);";
      }
    }
    ?>
  }
});

</script>

<!-- Modal -->
<div id="eventModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h2>Detalles del Evento</h2>
    <table id="event-details" class="details-table">
      <tbody>
        <tr>
          <th>Título</th>
          <td id="modal-title"></td>
        </tr>
        <tr>
          <th>Paciente</th>
          <td id="modal-patient"></td>
        </tr>
        <tr>
          <th>Médico</th>
          <td id="modal-doctor"></td>
        </tr>
        <tr>
          <th>Área</th>
          <td id="modal-area"></td>
        </tr>
        <tr>
          <th>Inicio</th>
          <td id="modal-start"></td>
        </tr>
        <tr>
          <th>Fin</th>
          <td id="modal-end"></td>
        </tr>
        <tr>
          <th>Duración</th>
          <td id="modal-duration"></td>
        </tr>
        <tr>
          <th>Estado</th>
          <td id="modal-status"></td>
        </tr>
        <tr>
  <th>No. Habitación</th>
  <td id="modal-room-number"></td>
</tr>
<tr>
  <th>Aseguradora</th>
  <td id="modal-insurer"></td>
</tr>
<tr>
  <th>No. Póliza</th>
  <td id="modal-policy-number"></td>
</tr>
<tr>
  <th>No. Certificado</th>
  <td id="modal-certificate-number"></td>
</tr>
<tr>
  <th>Cirugía</th>
  <td id="modal-surgery"></td>
</tr>
<tr>
  <th>Hospitalización</th>
  <td id="modal-hospitalization"></td>
</tr>
<tr>
  <th>Ayudante</th>
  <td id="modal-assistant"></td>
</tr>
<tr>
  <th>Anestesiólogo/a</th>
  <td id="modal-anesthetist"></td>
</tr>
<tr>
  <th>Circulante</th>
  <td id="modal-circulating"></td>
</tr>
<tr>
  <th>Técnico</th>
  <td id="modal-technician"></td>
</tr>
<tr>
  <th>Instrumentista</th>
  <td id="modal-instrumentist"></td>
</tr>
<tr>
  <th>Valoración</th>
  <td id="modal-evaluation"></td>
</tr>
      </tbody>
    </table>
  </div>
</div>


<!-- Estilos para la Tabla en el Modal -->
<style>
.details-table {
  width: 100%;
  border-collapse: collapse;
}

.details-table th,
.details-table td {
  border: 1px solid #ddd;
  padding: 8px;
}

.details-table th {
  background-color: #06adbf;
  color: white;
  text-align: left;
}

.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.4);
}

.modal-content {
  background-color: #fff;
  position: absolute; /* Permite centrar manualmente */
  top: 50%; /* Mueve hacia abajo un 50% de la altura */
  left: 50%; /* Mueve hacia la derecha un 50% del ancho */
  transform: translate(-50%, -50%); /* Centra exactamente usando el punto medio */
  padding: 20px;
  border: 1px solid #888;
  border-radius: 8px;
  width: 80%;
  max-width: 800px; /* Limita el ancho del modal */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
  max-height: 90vh; /* Limita la altura */
  overflow-y: auto; /* Desplazamiento si el contenido es largo */
}

.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}
</style>

</body>
</html>

