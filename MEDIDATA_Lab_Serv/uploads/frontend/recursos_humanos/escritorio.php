<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';
require_once '../../backend/registros/postulaciones_guard.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$events = medidata_rrhh_fetch_eventos_calendario();
$rrhhConteos = medidata_rrhh_fetch_conteos_dashboard();
$totalVacantesActivas = $rrhhConteos['vacantes_activas'];
$totalPostulantesWeb = medidata_postulaciones_count();
$entrevistasHoy = $rrhhConteos['entrevistas_hoy'];

$totalColaboradores = 0;
try {
    $totalColaboradores = (int) $connect->query("WITH colaboradores AS (
        SELECT COUNT(*) AS Colaborador FROM medic9ue_medi_data.doctor
        UNION ALL SELECT COUNT(*) FROM medic9ue_medi_data.nurse
        UNION ALL SELECT COUNT(*) FROM medic9ue_medi_data.staff_administrative
        UNION ALL SELECT COUNT(*) FROM medic9ue_medi_data.staff_general_services
        UNION ALL SELECT COUNT(*) FROM medic9ue_medi_data.users
        ) SELECT SUM(Colaborador) FROM colaboradores;")->fetchColumn();
} catch (Throwable $e) {
    error_log('escritorio colaboradores: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- DataTables -->
    <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4.css" />
    <link rel="stylesheet" href="../../backend/vendor/datatables/dataTables.bs4-custom.css" />
    <link href="../../backend/vendor/datatables/buttons.bs.css" rel="stylesheet" />

    <!-- FullCalendar -->
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <title>MEDIDATA</title>
</head>

<body>

    <?php
    include_once './menu.php';
    ?>

    <!-- NAVBAR -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once './perfil.php'; ?>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <?php
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

            <div class="dashboard-container">
                <header>
                    <h1 class="rrhh-page-title">Resumen de Recursos Humanos</h1>
                </header>
                
                <div class="rrhh-dashboard">
                    <div class="rrhh-kpi">
                        <h2>Colaboradores</h2>
                        <p><?php echo number_format($totalColaboradores); ?></p>
                    </div>
                    <div class="rrhh-kpi">
                        <h2>Vacantes Activas</h2>
                        <p><?php echo number_format($totalVacantesActivas); ?></p>
                    </div>
                    <div class="rrhh-kpi">
                        <h2>Postulantes Web</h2>
                        <p><?php echo number_format($totalPostulantesWeb); ?></p>
                    </div>
                    <div class="rrhh-kpi">
                        <h2>En entrevista</h2>
                        <p><?php echo number_format($entrevistasHoy); ?></p>
                    </div>
                </div>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Agenda</h3>
                    </div>
                    <div id="calendar-container" class="rrhh-calendar-layout">
                        <div id="calendar"></div>
                        <div id="notification-panel">
                            <h4>Notificaciones de Agenda</h4>
                            
                            <div id="future-events">
                                <h5>Próximas Entrevistas</h5>
                                <div id="future-occupancy"></div>
                            </div>

                            <div id="vacancy-deadlines">
                                <h5>Cierres de Vacantes</h5>
                                <div id="vacancy-occupancy"></div>
                            </div>
                            
                            <div id="past-events">
                                <h5>Actividad Reciente</h5>
                                <div id="past-occupancy"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </section>

    <!-- Scripts -->
    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

    <script src="../../backend/vendor/datatables/jquery.dataTables.js"></script>
    <script src="../../backend/vendor/datatables/dataTables.bootstrap4.js"></script>
    <script src="../../backend/js/moment.min.js"></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.min.js'></script>
    <script src='../../backend/js/fullcalendar/locale/es.js'></script>
    
    <script>
        $(document).ready(function () {
            var events = <?php echo json_encode($events); ?>;

            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,basicWeek,basicDay'
                },
                locale: 'es',
                editable: false,
                eventLimit: true,
                events: events,
                eventClick: function (event) {
                    showEventDetails(event);
                },
                viewRender: function (view) {
                    updateNotifications(view);
                }
            });

            function showEventDetails(event) {
                $('#modal-title').text(event.title);
                
                // Limpiar tabla
                $('#event-details tbody').empty();

                if (event.type === 'interview') {
                    $('#event-details tbody').append(`
                        <tr><th>Candidato</th><td>${event.candidate_name}</td></tr>
                        <tr><th>DNI</th><td>${event.candidate_dni}</td></tr>
                        <tr><th>Puesto</th><td>${event.position_name}</td></tr>
                        <tr><th>Estado</th><td>${event.interview_status}</td></tr>
                        <tr><th>Inicio</th><td>${moment(event.start).format('YYYY-MM-DD HH:mm')}</td></tr>
                        <tr><th>Teléfono</th><td>${event.candidate_phone}</td></tr>
                        <tr><th>Email</th><td>${event.candidate_email}</td></tr>
                    `);
                } else if (event.type === 'vacancy_end') {
                    $('#event-details tbody').append(`
                        <tr><th>Puesto</th><td>${event.position_name}</td></tr>
                        <tr><th>Fecha Cierre</th><td>${moment(event.start).format('YYYY-MM-DD')}</td></tr>
                        <tr><th>Beneficios</th><td>${event.benefits || 'N/A'}</td></tr>
                        <tr><th>Tipo</th><td>Cierre de Vacante</td></tr>
                    `);
                }
                
                $('#eventModal').fadeIn();
            }

            function updateNotifications(view) {
                const now = moment();
                
                // Próximas Entrevistas
                const futureOccupancy = $('#future-occupancy');
                futureOccupancy.empty();
                let futureCount = 0;
                events.filter(e => e.type === 'interview' && moment(e.start).isAfter(now))
                      .sort((a,b) => moment(a.start) - moment(b.start))
                      .forEach(event => {
                    if (futureCount < 5) {
                        futureOccupancy.append(`
                            <div class="notification-item" style="border-left: 5px solid ${event.color};">
                                <strong>${event.candidate_name}</strong>
                                <p>${moment(event.start).format('DD/MM HH:mm')} - ${event.position_name}</p>
                            </div>
                        `);
                        futureCount++;
                    }
                });
                if (futureCount === 0) futureOccupancy.append('<p>No hay entrevistas próximas.</p>');

                // Cierres de Vacantes
                const vacancyOccupancy = $('#vacancy-occupancy');
                vacancyOccupancy.empty();
                let vacancyCount = 0;
                events.filter(e => e.type === 'vacancy_end' && moment(e.start).isSameOrAfter(now, 'day'))
                      .sort((a,b) => moment(a.start) - moment(b.start))
                      .forEach(event => {
                    if (vacancyCount < 3) {
                        vacancyOccupancy.append(`
                            <div class="notification-item" style="border-left: 5px solid #f44336;">
                                <strong>${event.position_name}</strong>
                                <p>Cierra: ${moment(event.start).format('DD/MM/YYYY')}</p>
                            </div>
                        `);
                        vacancyCount++;
                    }
                });
                if (vacancyCount === 0) vacancyOccupancy.append('<p>No hay cierres de vacantes próximos.</p>');

                // Actividad Reciente (Pasados)
                const pastOccupancy = $('#past-occupancy');
                pastOccupancy.empty();
                let pastCount = 0;
                events.filter(e => moment(e.start).isBefore(now))
                      .sort((a,b) => moment(b.start) - moment(a.start))
                      .forEach(event => {
                    if (pastCount < 3) {
                        pastOccupancy.append(`
                            <div class="notification-item" style="opacity: 0.7;">
                                <strong>${event.title}</strong>
                                <p>${moment(event.start).format('DD/MM')}</p>
                            </div>
                        `);
                        pastCount++;
                    }
                });
            }

            $('.close').on('click', function () {
                $('#eventModal').fadeOut();
            });

            $(window).on('click', function(event) {
                if (event.target == document.getElementById('eventModal')) {
                    $('#eventModal').fadeOut();
                }
            });
        });
    </script>

    <!-- Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title" style="color: #035c67; margin-bottom: 15px;">Detalles</h2>
            <table id="event-details" class="details-table">
                <tbody>
                    <!-- Dinámico -->
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .details-table th { background: #06adbf; color: white; padding: 10px; text-align: left; width: 30%; }
        .details-table td { border: 1px solid #ddd; padding: 10px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: #fff; margin: 10% auto; padding: 25px; border-radius: 8px; width: 50%; max-width: 600px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .close { float: right; font-size: 28px; cursor: pointer; color: #aaa; }
        .close:hover { color: #000; }
    </style>

    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>