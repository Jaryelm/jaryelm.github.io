<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php');

// Consulta para obtener eventos de RRHH (Entrevistas y Cierres de Vacantes)
$events = [];

try {
    // 1. Obtener Entrevistas
    $stmt_interviews = $connect_rrhh->prepare("
        SELECT 
            i.id,
            CONCAT('Entrevista: ', p.fullname) AS title,
            CONCAT(i.date_interview, ' ', i.time_interview) AS start,
            DATE_ADD(CONCAT(i.date_interview, ' ', i.time_interview), INTERVAL 1 HOUR) AS end,
            CASE 
                WHEN i.status = 'Programada' THEN '#0071c5'
                WHEN i.status = 'En Proceso' THEN '#FF4500'
                WHEN i.status = 'Terminada' THEN '#06adbf'
                ELSE '#EE82EE'
            END AS color,
            p.fullname AS candidate_name,
            p.dni AS candidate_dni,
            p.email AS candidate_email,
            p.phonenumber AS candidate_phone,
            i.status AS interview_status,
            pt.name AS position_name,
            'interview' as type
        FROM interviews i
        INNER JOIN postulantes p ON i.id_candidate = p.id
        LEFT JOIN vacantes_trabajo v ON p.id_vacant_position = v.id
        LEFT JOIN puestos_trabajo pt ON v.id_position = pt.id
        WHERE i.deleted = 0
    ");
    $stmt_interviews->execute();
    $interviews = $stmt_interviews->fetchAll(PDO::FETCH_ASSOC);
    $events = array_merge($events, $interviews);

    // 2. Obtener Cierres de Vacantes
    $stmt_vacantes_end = $connect_rrhh->prepare("
        SELECT 
            v.id,
            CONCAT('Cierre Vacante: ', pt.name) AS title,
            v.end_date AS start,
            v.end_date AS end,
            '#f44336' AS color,
            pt.name AS position_name,
            v.benefits,
            'vacancy_end' as type
        FROM vacantes_trabajo v
        JOIN puestos_trabajo pt ON v.id_position = pt.id
        WHERE v.deleted = 0
    ");
    $stmt_vacantes_end->execute();
    $vacantes_end = $stmt_vacantes_end->fetchAll(PDO::FETCH_ASSOC);
    $events = array_merge($events, $vacantes_end);

} catch (Exception $e) {
    error_log("Error al cargar eventos de RRHH: " . $e->getMessage());
}

// Conteos para el Dashboard
try {
    $totalColaboradores = $connect->query("WITH colaboradores AS (
        SELECT COUNT(*) AS 'Colaborador' FROM medic9ue_medi_data.doctor
        UNION ALL
        SELECT COUNT(*) FROM medic9ue_medi_data.nurse
        UNION ALL
        SELECT COUNT(*) FROM medic9ue_medi_data.users
        )
        SELECT SUM(Colaborador) FROM colaboradores;")->fetchColumn();
    
    $totalVacantesActivas = $connect_rrhh->query("SELECT COUNT(*) FROM vacantes_trabajo WHERE deleted = 0 AND end_date >= CURDATE()")->fetchColumn();
    $totalPostulantes = $connect_rrhh->query("SELECT COUNT(*) FROM postulantes WHERE deleted = 0")->fetchColumn();
    $entrevistasHoy = $connect_rrhh->query("SELECT COUNT(*) FROM interviews WHERE deleted = 0 AND date_interview = CURDATE()")->fetchColumn();
} catch (Exception $e) {
    $totalColaboradores = 0;
    $totalVacantesActivas = 0;
    $totalPostulantes = 0;
    $entrevistasHoy = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
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
            color: #035c67;
        }

        #notification-panel h5 {
            margin-top: 15px;
            margin-bottom: 8px;
            color: #06adbf;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
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

        @media (max-width: 768px) {
            #calendar-container {
                flex-direction: column;
                gap: 15px;
            }

            #calendar,
            #notification-panel {
                max-width: 100%;
                flex: none;
            }
        }

        /* Estilo Dashboard */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: #06adbf;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #fff;
            transition: transform 0.3s ease;
            text-align: center;
        }

        .card h2 {
            margin-top: 0;
            color: #fff;
            font-size: 1.1em;
            text-transform: uppercase;
        }

        .card p {
            color: #fff;
            font-size: 2em;
            font-weight: bold;
            margin: 10px 0 0 0;
        }

        .card:hover {
            transform: translateY(-5px);
            background-color: #035c67;
        }
    </style>

    <title>MEDIDATA - RRHH</title>
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
            <div class="form-group"></div>
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
                    <h1 style="text-align: center; margin-bottom: 20px; color: #035c67;">Resumen de Recursos Humanos</h1>
                </header>
                
                <div class="dashboard">
                    <div class="card">
                        <h2>Colaboradores</h2>
                        <p><?php echo number_format($totalColaboradores); ?></p>
                    </div>
                    <div class="card" style="background-color: #28a745;">
                        <h2>Vacantes Activas</h2>
                        <p><?php echo number_format($totalVacantesActivas); ?></p>
                    </div>
                    <div class="card" style="background-color: #ffc107;">
                        <h2>Postulantes</h2>
                        <p><?php echo number_format($totalPostulantes); ?></p>
                    </div>
                    <div class="card" style="background-color: #fd7e14;">
                        <h2>Entrevistas Hoy</h2>
                        <p><?php echo number_format($entrevistasHoy); ?></p>
                    </div>
                </div>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Agenda de RRHH</h3>
                    </div>
                    <div id="calendar-container">
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