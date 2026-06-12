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
    <!-- SweetAlert2 -->
    <link href='../../backend/vendor/sweetalert2/sweetalert2.min.css' rel='stylesheet' />
    <style>
        #calendar { min-height: 500px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .details-table th { background: #06adbf; color: white; padding: 10px; text-align: left; width: 35%; }
        .details-table td { border: 1px solid #ddd; padding: 10px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); }
        .modal-content { background: #fff; margin: 10% auto; padding: 25px; border-radius: 8px; width: 50%; max-width: 600px; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
        .close { float: right; font-size: 28px; cursor: pointer; color: #aaa; }
        .close:hover { color: #000; }
        .rrhh-btn-inline:hover { opacity: 0.9; }
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
                                <div id="future-occupancy">
                                    <?php
                                    $now = new DateTime();
                                    $futureCount = 0;
                                    $interviews = array_filter($events, fn($e) => $e['type'] === 'interview' && new DateTime($e['start']) > $now);
                                    usort($interviews, fn($a, $b) => strcmp($a['start'], $b['start']));
                                    foreach ($interviews as $event) {
                                        if ($futureCount >= 5) break;
                                        echo '<div class="notification-item" style="border-left: 5px solid '.$event['color'].';">';
                                        echo '<strong>'.htmlspecialchars($event['candidate_name'] ?? '').'</strong>';
                                        echo '<p>'.date('d/m H:i', strtotime($event['start'])).' - '.htmlspecialchars($event['position_name'] ?? '').'</p>';
                                        echo '</div>';
                                        $futureCount++;
                                    }
                                    if ($futureCount === 0) echo '<p>No hay entrevistas próximas.</p>';
                                    ?>
                                </div>
                            </div>

                            <div id="vacancy-deadlines">
                                <h5>Cierres de Vacantes</h5>
                                <div id="vacancy-occupancy">
                                    <?php
                                    $vacancyCount = 0;
                                    $vacancies = array_filter($events, fn($e) => $e['type'] === 'vacancy_end' && date('Y-m-d', strtotime($e['start'])) >= date('Y-m-d'));
                                    usort($vacancies, fn($a, $b) => strcmp($a['start'], $b['start']));
                                    foreach ($vacancies as $event) {
                                        if ($vacancyCount >= 3) break;
                                        echo '<div class="notification-item" style="border-left: 5px solid #f44336;">';
                                        echo '<strong>'.htmlspecialchars($event['position_name'] ?? '').'</strong>';
                                        echo '<p>Cierra: '.date('d/m/Y', strtotime($event['start'])).'</p>';
                                        echo '</div>';
                                        $vacancyCount++;
                                    }
                                    if ($vacancyCount === 0) echo '<p>No hay cierres de vacantes próximos.</p>';
                                    ?>
                                </div>
                            </div>
                            
                            <div id="past-events">
                                <h5>Actividad Reciente</h5>
                                <div id="past-occupancy">
                                    <?php
                                    $pastCount = 0;
                                    $pastEvents = array_filter($events, fn($e) => new DateTime($e['start']) < $now);
                                    usort($pastEvents, fn($a, $b) => strcmp($b['start'], $a['start']));
                                    foreach ($pastEvents as $event) {
                                        if ($pastCount >= 3) break;
                                        echo '<div class="notification-item" style="opacity: 0.7;">';
                                        echo '<strong>'.htmlspecialchars($event['title'] ?? '').'</strong>';
                                        echo '<p>'.date('d/m', strtotime($event['start'])).'</p>';
                                        echo '</div>';
                                        $pastCount++;
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </section>

    <!-- Scripts (Misma estructura que frontend/admin/escritorio.php) -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/moment.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

    <script src="../../backend/vendor/datatables/dataTables.min.js"></script>
    <script src="../../backend/vendor/datatables/dataTables.bootstrap.min.js"></script>
    
    <script src='../../backend/js/fullcalendar/fullcalendar.min.js'></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.js'></script>
    <script src='../../backend/js/fullcalendar/locale/es.js'></script>
    
    <script>
        $(document).ready(function () {
            // Establecer idioma de moment explicitly
            moment.locale('es');

            // Eventos de cierre de modal (Delegación)
            $(document).on('click', '.close', function () {
                $('#eventModal').fadeOut();
            });

            $(window).on('click', function(event) {
                if (event.target == document.getElementById('eventModal')) {
                    $('#eventModal').fadeOut();
                }
            });

            var date = new Date();
            var yyyy = date.getFullYear().toString();
            var mm = (date.getMonth() + 1).toString().padStart(2, '0');
            var dd = date.getDate().toString().padStart(2, '0');

            $('#calendar').fullCalendar({
                header: {
                    language: 'es',
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,basicWeek,basicDay'
                },
                locale: 'es',
                defaultDate: `${yyyy}-${mm}-${dd}`,
                editable: true,
                eventLimit: true,
                lazyFetching: false,
                selectable: true,
                selectHelper: true,
                select: function(start, end) {
                    $('#customEventId').val('');
                    $('#customEventTitle').val('');
                    $('#customEventStart').val(start.format('YYYY-MM-DDTHH:mm'));
                    $('#customEventEnd').val(end.format('YYYY-MM-DDTHH:mm'));
                    $('#customEventColor').val('#035c67');
                    $('#customEventModalTitle').text('Añadir Nuevo Evento');
                    $('#addCustomEventModal').fadeIn();
                    $('#calendar').fullCalendar('unselect');
                },
                events: [
                  <?php foreach($events as $event): 
                    $startStr = $event['start'] ?? '';
                    $endStr = $event['end'] ?? '';
                    if (empty($startStr) || strpos($startStr, '0000-00-00') !== false) continue;
                  ?>
                  {
                      id: '<?php echo $event['id']; ?>',
                      title: '<?php echo addslashes($event['title'] ?? ''); ?>',
                      start: '<?php echo $startStr; ?>',
                      end: '<?php echo $endStr; ?>',
                      color: '<?php echo $event['color'] ?? ''; ?>',
                      type: '<?php echo $event['type'] ?? ''; ?>',
                      candidate_id: '<?php echo $event['candidate_id'] ?? ""; ?>',
                      candidate_name: '<?php echo addslashes($event['candidate_name'] ?? ""); ?>',
                      candidate_dni: '<?php echo $event['candidate_dni'] ?? ""; ?>',
                      position_name: '<?php echo addslashes($event['position_name'] ?? ""); ?>',
                      interview_status: '<?php echo $event['interview_status'] ?? ""; ?>',
                      candidate_phone: '<?php echo $event['candidate_phone'] ?? ""; ?>',
                      candidate_email: '<?php echo addslashes($event['candidate_email'] ?? ""); ?>',
                      benefits: '<?php echo addslashes($event['benefits'] ?? ""); ?>'
                  },
                  <?php endforeach; ?>
                ],
                eventClick: function (event) {
                    showEventDetails(event);
                },
                eventDrop: function(event, delta, revertFunc) {
                    updateEventDate(event, revertFunc);
                },
                eventAfterAllRender: function() {
                    updateNotifications();
                }
            });

            setTimeout(function() {
                $(window).trigger('resize');
            }, 500);

            function updateEventDate(event, revertFunc) {
                if (!event || !event.start) { revertFunc(); return; }
                if (event.type !== 'interview' && event.type !== 'custom') {
                    Swal.fire({ icon: 'warning', title: 'Acción no permitida', text: 'Este tipo de evento no puede ser reprogramado mediante arrastre.' });
                    revertFunc(); return;
                }

                const eventName = event.type === 'interview' ? 'entrevista' : 'evento';
                const numericId = event.id.toString().split('_').pop();

                Swal.fire({
                    title: '¿Reprogramar ' + eventName + '?',
                    text: 'Desea reprogramar este ' + eventName + ' para el ' + event.start.format('DD/MM/YYYY HH:mm') + '?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#035c67',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, reprogramar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '../../backend/registros/rrhh_calendar_update.php',
                            type: 'POST',
                            data: { id: numericId, start: event.start.format(), type: event.type },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({ icon: 'success', title: 'Éxito', text: response.message, timer: 2000, showConfirmButton: false });
                                    updateNotifications();
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                                    revertFunc();
                                }
                            },
                            error: function() {
                                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar al servidor.' });
                                revertFunc();
                            }
                        });
                    } else {
                        revertFunc();
                    }
                });
            }

            function showEventDetails(event) {
                if (!event || !event.start) return;

                $('#modal-title').text(event.title || 'Detalles');
                $('#event-details tbody').empty();
                $('#modal-footer').empty();

                const formattedStart = event.start.format('YYYY-MM-DD HH:mm');

                if (event.type === 'interview') {
                    $('#event-details tbody').append(`
                        <tr><th>Candidato</th><td>${event.candidate_name || ''}</td></tr>
                        <tr><th>DNI</th><td>${event.candidate_dni || ''}</td></tr>
                        <tr><th>Puesto</th><td>${event.position_name || ''}</td></tr>
                        <tr><th>Estado</th><td>${event.interview_status || ''}</td></tr>
                        <tr><th>Inicio</th><td>${formattedStart}</td></tr>
                        <tr><th>Teléfono</th><td>${event.candidate_phone || ''}</td></tr>
                        <tr><th>Email</th><td>${event.candidate_email || ''}</td></tr>
                    `);

                    $('#modal-footer').append(`
                        <button onclick="deleteEvent('${event.id}', 'interview')" class="button rrhh-btn-inline" style="background: #FC3B56; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Cancelar Entrevista</button>
                        <a href="detalle_postulante_usr.php?id=${event.candidate_id}" class="button rrhh-btn-inline" style="background: #035c67; color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px;">Ver Perfil Candidato</a>
                    `);
                } else if (event.type === 'vacancy_end') {
                    $('#event-details tbody').append(`
                        <tr><th>Puesto</th><td>${event.position_name || ''}</td></tr>
                        <tr><th>Fecha Cierre</th><td>${event.start.format('YYYY-MM-DD')}</td></tr>
                        <tr><th>Beneficios</th><td>${event.benefits || 'N/A'}</td></tr>
                        <tr><th>Tipo</th><td>Cierre de Vacante</td></tr>
                    `);

                    $('#modal-footer').append(`
                        <a href="vacantes_trabajo_usr.php" class="button rrhh-btn-inline" style="background: #FC3B56; color: white; text-decoration: none; padding: 10px 15px; border-radius: 5px;">Gestionar Vacante</a>
                    `);
                } else if (event.type === 'custom') {
                    $('#event-details tbody').append(`
                        <tr><th>Tipo</th><td>Evento General</td></tr>
                        <tr><th>Inicio</th><td>${event.start ? event.start.format('YYYY-MM-DD HH:mm') : ''}</td></tr>
                        <tr><th>Fin</th><td>${event.end ? event.end.format('YYYY-MM-DD HH:mm') : ''}</td></tr>
                    `);

                    const eventData = { id: event.id, title: event.title, start: event.start, end: event.end, color: event.color };

                    $('#modal-footer').append(`
                        <button onclick="deleteEvent('${event.id}', 'custom')" class="button rrhh-btn-inline" style="background: #FC3B56; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Eliminar</button>
                        <button onclick='editCustomEvent(${JSON.stringify(eventData)})' class="button rrhh-btn-inline" style="background: #06adbf; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">Editar</button>
                    `);
                }
                
                $('#eventModal').fadeIn();
            }

            window.deleteEvent = function(prefixedId, type) {
                const title = type === 'interview' ? '¿Cancelar entrevista?' : '¿Eliminar evento?';
                const text = type === 'interview' ? 'Esta acción quitará la entrevista de la agenda.' : 'El evento será eliminado permanentemente.';
                const numericId = prefixedId.toString().split('_').pop();

                Swal.fire({
                    title: title, text: text, icon: 'warning', showCancelButton: true, confirmButtonColor: '#FC3B56', cancelButtonColor: '#888', confirmButtonText: 'Sí, eliminar', cancelButtonText: 'No, mantener'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('../../backend/registros/delete_rrhh_calendar_event.php', { id: numericId, type: type }, function(response) {
                            if (response.success) {
                                $('#calendar').fullCalendar('removeEvents', prefixedId);
                                $('#eventModal').fadeOut();
                                Swal.fire('Eliminado', response.message, 'success');
                                updateNotifications();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        }, 'json');
                    }
                });
            };

            window.editCustomEvent = function(event) {
                $('#eventModal').fadeOut();
                $('#customEventId').val(event.id); 
                $('#customEventTitle').val(event.title);
                $('#customEventStart').val(moment(event.start).format('YYYY-MM-DDTHH:mm'));
                $('#customEventEnd').val(moment(event.end).format('YYYY-MM-DDTHH:mm'));
                $('#customEventColor').val(event.color);
                $('#customEventModalTitle').text('Editar Evento');
                $('#addCustomEventModal').fadeIn();
            };

            function updateNotifications() {
                try {
                    const now = moment();
                    const calendar = $('#calendar');
                    if (!calendar.length || typeof calendar.fullCalendar !== 'function') return;

                    const events = calendar.fullCalendar('clientEvents');
                    
                    const futureOccupancy = $('#future-occupancy');
                    futureOccupancy.empty();
                    let futureCount = 0;
                    events.filter(e => e && e.start && e.type === 'interview' && moment(e.start).isAfter(now))
                          .sort((a,b) => moment(a.start) - moment(b.start))
                          .forEach(event => {
                        if (futureCount < 5) {
                            futureOccupancy.append(`
                                <div class="notification-item" style="border-left: 5px solid ${event.color || '#888'};">
                                    <strong>${event.candidate_name || 'N/A'}</strong>
                                    <p>${moment(event.start).format('DD/MM HH:mm')} - ${event.position_name || ''}</p>
                                </div>
                            `);
                            futureCount++;
                        }
                    });
                    if (futureCount === 0) futureOccupancy.append('<p>No hay entrevistas próximas.</p>');

                    const vacancyOccupancy = $('#vacancy-occupancy');
                    vacancyOccupancy.empty();
                    let vacancyCount = 0;
                    events.filter(e => e && e.start && e.type === 'vacancy_end' && moment(e.start).isSameOrAfter(now, 'day'))
                          .sort((a,b) => moment(a.start) - moment(b.start))
                          .forEach(event => {
                        if (vacancyCount < 3) {
                            vacancyOccupancy.append(`
                                <div class="notification-item" style="border-left: 5px solid #f44336;">
                                    <strong>${event.position_name || 'N/A'}</strong>
                                    <p>Cierra: ${moment(event.start).format('DD/MM/YYYY')}</p>
                                </div>
                            `);
                            vacancyCount++;
                        }
                    });
                    if (vacancyCount === 0) vacancyOccupancy.append('<p>No hay cierres de vacantes próximos.</p>');

                    const pastOccupancy = $('#past-occupancy');
                    pastOccupancy.empty();
                    let pastCount = 0;
                    events.filter(e => e && e.start && moment(e.start).isBefore(now))
                          .sort((a,b) => moment(b.start) - moment(a.start))
                          .forEach(event => {
                        if (pastCount < 3) {
                            pastOccupancy.append(`
                                <div class="notification-item" style="opacity: 0.7;">
                                    <strong>${event.title || 'N/A'}</strong>
                                    <p>${moment(event.start).format('DD/MM')}</p>
                                </div>
                            `);
                            pastCount++;
                        }
                    });
                } catch (err) { console.error("Error en updateNotifications:", err); }
            }
            window.updateNotifications = updateNotifications;

            $('#customEventForm').submit(function(e) {
                e.preventDefault();
                const prefixedId = $('#customEventId').val();
                const title = $('#customEventTitle').val();
                const start = $('#customEventStart').val();
                const end = $('#customEventEnd').val();
                const color = $('#customEventColor').val();

                const isUpdate = prefixedId !== '';
                const numericId = isUpdate ? prefixedId.toString().split('_').pop() : '';
                const url = isUpdate 
                    ? '../../backend/registros/upd_rrhh_calendar_event.php' 
                    : '../../backend/registros/add_rrhh_calendar_event.php';

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { id: numericId, title: title, start: start, end: end, color: color },
                    success: function(response) {
                        if (response.success) {
                            if (isUpdate) {
                                const event = $('#calendar').fullCalendar('clientEvents', prefixedId)[0];
                                if (event) {
                                    event.title = title;
                                    event.start = moment(start);
                                    event.end = moment(end);
                                    event.color = color;
                                    $('#calendar').fullCalendar('updateEvent', event);
                                }
                            } else {
                                $('#calendar').fullCalendar('renderEvent', {
                                    id: 'custom_' + response.id,
                                    title: title,
                                    start: start,
                                    end: end,
                                    color: color,
                                    type: 'custom'
                                }, true);
                            }
                            
                            $('#addCustomEventModal').fadeOut();
                            Swal.fire({ icon: 'success', title: isUpdate ? 'Evento actualizado' : 'Evento creado', text: response.message, timer: 2000, showConfirmButton: false });
                            updateNotifications();
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar al servidor.' });
                    }
                });
            });

            $(document).on('click', '.close-custom', function () { $('#addCustomEventModal').fadeOut(); });
            $(window).on('click', function(event) { if (event.target == document.getElementById('addCustomEventModal')) { $('#addCustomEventModal').fadeOut(); } });
        });
    </script>

    <!-- Modal Detalles -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modal-title" style="color: #035c67; margin-bottom: 15px;">Detalles</h2>
            <table id="event-details" class="details-table">
                <tbody></tbody>
            </table>
            <div id="modal-footer" style="margin-top: 20px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;"></div>
        </div>
    </div>

    <!-- Modal Formulario -->
    <div id="addCustomEventModal" class="modal">
        <div class="modal-content">
            <span class="close-custom close">&times;</span>
            <h2 id="customEventModalTitle" style="color: #035c67; margin-bottom: 15px;">Añadir Nuevo Evento</h2>
            <form id="customEventForm">
                <input type="hidden" id="customEventId" value="">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Título del Evento:</label>
                    <input type="text" id="customEventTitle" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Inicio:</label>
                    <input type="datetime-local" id="customEventStart" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Fin:</label>
                    <input type="datetime-local" id="customEventEnd" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Color:</label>
                    <input type="color" id="customEventColor" value="#035c67" style="width: 100%; height: 40px; padding: 0; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="text-align: right;">
                    <button type="submit" class="button" style="background: #035c67; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 JS -->
    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>