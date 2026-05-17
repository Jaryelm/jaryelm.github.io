<?php
include_once '../../backend/registros/session_check.php';
require_once('../../backend/bd/Conexion.php');

// Consulta con JOIN para obtener datos relacionados para el calendario
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

        #weekly-status,
        #future-events,
        #past-events {
            margin-top: 20px;
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
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
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
        }

        .card h2 {
            margin-top: 0;
            color: #fff;
        }

        .card p {
            color: #f4f4f4;
            font-size: 1.5em;
            font-weight: bold;
        }

        .card:hover {
            transform: translateY(-5px);
        }
    </style>

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
                    <h1>Recursos Humanos</h1>
                </header>
                
                <div class="dashboard">
                    <?php
                    // Conteos rápidos
                    $totalColaboradores = $connect->query("WITH colaboradores AS (
                        SELECT COUNT(*) AS 'Colaborador' FROM medic9ue_medi_data.doctor
                        UNION ALL
                        SELECT COUNT(*) FROm medic9ue_medi_data.nurse
                        UNION ALL
                        SELECT COUNT(*) FROm medic9ue_medi_data.users
                        )
                        SELECT SUM(Colaborador) FROM colaboradores;")->fetchColumn();
                    $totalPacientes = $connect->query("SELECT COUNT(*) FROM patients")->fetchColumn();
                    ?>
                    <div class="card">
                        <h2>Colaboradores</h2>
                        <p><?php echo number_format($totalColaboradores); ?></p>
                    </div>
                    <div class="card">
                        <h2>Pacientes Registrados</h2>
                        <p><?php echo number_format($totalPacientes); ?></p>
                    </div>
                </div>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Programación de Eventos</h3>
                    </div>
                    <div id="calendar-container">
                        <div id="calendar"></div>
                        <div id="notification-panel">
                            <h4>Notificaciones</h4>
                            <div id="weekly-status">
                                <h5>Ocupación Semanal</h5>
                                <div id="weekly-occupancy"></div>
                            </div>
                            <div id="future-events">
                                <h5>Próximos Eventos</h5>
                                <div id="future-occupancy"></div>
                            </div>
                            <div id="past-events">
                                <h5>Eventos Pasados</h5>
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
    <script src='../../backend/js/fullcalendar/lib/main.js'></script>
    <script src='../../backend/js/fullcalendar/lib/locales/es.js'></script>
    
    <script>
        $(document).ready(function () {
            var events = <?php echo json_encode($events); ?>;

            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: events,
                eventClick: function (info) {
                    showEventDetails(info.event);
                },
                datesSet: function (view) {
                    updateNotifications(view);
                }
            });
            calendar.render();

            function showEventDetails(event) {
                const props = event.extendedProps;
                $('#modal-title').text(event.title);
                $('#modal-patient').text(props.patient_name + ' ' + props.patient_surname);
                $('#modal-doctor').text(props.doctor_name + ' ' + props.doctor_surname);
                $('#modal-area').text(props.area_name);
                $('#modal-start').text(moment(event.start).format('YYYY-MM-DD HH:mm'));
                $('#modal-end').text(event.end ? moment(event.end).format('YYYY-MM-DD HH:mm') : 'N/A');
                $('#modal-room-number').text(props.room_number || 'N/A');
                $('#modal-insurer').text(props.insurer || 'N/A');
                $('#modal-surgery').text(props.surgery || 'N/A');
                
                $('#eventModal').fadeIn();
            }

            function updateNotifications(view) {
                updateWeeklyOccupancy();
                updateFutureEvents();
                updatePastEvents();
            }

            function updateWeeklyOccupancy() {
                const weeklyOccupancy = $('#weekly-occupancy');
                weeklyOccupancy.empty();
                // Simulación de lógica (en una implementación real, filtrarías 'events')
                weeklyOccupancy.append('<div class="notification-item available"><p>Consultar calendario para detalles semanales.</p></div>');
            }

            function updateFutureEvents() {
                const futureOccupancy = $('#future-occupancy');
                futureOccupancy.empty();
                const now = moment();
                let count = 0;
                events.forEach(event => {
                    if (moment(event.start).isAfter(now) && count < 5) {
                        futureOccupancy.append(`
                            <div class="notification-item occupied" style="background-color: ${event.color}; color: #fff;">
                                <strong>${event.title}</strong>
                                <p>${moment(event.start).format('DD/MM HH:mm')}</p>
                            </div>
                        `);
                        count++;
                    }
                });
            }

            function updatePastEvents() {
                const pastOccupancy = $('#past-occupancy');
                pastOccupancy.empty();
                const now = moment();
                let count = 0;
                events.forEach(event => {
                    if (moment(event.start).isBefore(now) && count < 3) {
                        pastOccupancy.append(`
                            <div class="notification-item available">
                                <strong>${event.title}</strong>
                                <p>${moment(event.start).format('DD/MM')}</p>
                            </div>
                        `);
                        count++;
                    }
                });
            }

            $('.close').on('click', function () {
                $('#eventModal').fadeOut();
            });
        });
    </script>

    <!-- Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Detalles del Evento</h2>
            <table class="details-table">
                <tr><th>Título</th><td id="modal-title"></td></tr>
                <tr><th>Paciente</th><td id="modal-patient"></td></tr>
                <tr><th>Médico</th><td id="modal-doctor"></td></tr>
                <tr><th>Área</th><td id="modal-area"></td></tr>
                <tr><th>Inicio</th><td id="modal-start"></td></tr>
                <tr><th>Fin</th><td id="modal-end"></td></tr>
                <tr><th>Habitación</th><td id="modal-room-number"></td></tr>
                <tr><th>Aseguradora</th><td id="modal-insurer"></td></tr>
                <tr><th>Cirugía</th><td id="modal-surgery"></td></tr>
            </table>
        </div>
    </div>

    <style>
        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .details-table th { background: #06adbf; color: white; padding: 8px; text-align: left; }
        .details-table td { border: 1px solid #ddd; padding: 8px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: #fff; margin: 10% auto; padding: 20px; border-radius: 8px; width: 60%; max-height: 80vh; overflow-y: auto; }
        .close { float: right; font-size: 28px; cursor: pointer; }
    </style>

    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>