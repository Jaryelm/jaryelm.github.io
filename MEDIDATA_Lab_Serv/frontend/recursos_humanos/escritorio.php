<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';
require_once '../../backend/registros/postulaciones_guard.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$events = medidata_rrhh_fetch_eventos_calendario();
$calendarEvents = [];
foreach ($events as $event) {
    $startStr = (string) ($event['start'] ?? '');
    $endStr = (string) ($event['end'] ?? '');
    if ($startStr === '' || strpos($startStr, '0000-00-00') !== false) {
        continue;
    }
    $type = (string) ($event['type'] ?? '');
    $entry = [
        'id' => (string) ($event['id'] ?? ''),
        'title' => (string) ($event['title'] ?? ''),
        'color' => (string) ($event['color'] ?? '#035c67'),
        'type' => $type,
        'candidate_id' => (string) ($event['candidate_id'] ?? ''),
        'candidate_name' => (string) ($event['candidate_name'] ?? ''),
        'candidate_dni' => (string) ($event['candidate_dni'] ?? ''),
        'position_name' => (string) ($event['position_name'] ?? ''),
        'interview_status' => (string) ($event['interview_status'] ?? ''),
        'candidate_phone' => (string) ($event['candidate_phone'] ?? ''),
        'candidate_email' => (string) ($event['candidate_email'] ?? ''),
        'benefits' => (string) ($event['benefits'] ?? ''),
        'raw_id' => (string) ($event['raw_id'] ?? ''),
        'id_event_type' => (string) ($event['id_event_type'] ?? ''),
        'description' => (string) ($event['description'] ?? ''),
        'event_type_name' => (string) ($event['event_type_name'] ?? ''),
        'is_public' => (string) ($event['is_public'] ?? '0'),
    ];
    if ($type === 'vacancy_end' || $type === 'birthday' || $type === 'anniversary' || !empty($event['allDay'])) {
        $day = substr($startStr, 0, 10);
        $entry['start'] = $day;
        $entry['end'] = date('Y-m-d', strtotime($day . ' +1 day'));
        $entry['allDay'] = true;
    } else {
        $entry['start'] = $startStr;
        $entry['end'] = ($endStr !== '' && $endStr !== $startStr) ? $endStr : date('Y-m-d H:i:s', strtotime($startStr) + 3600);
        $entry['allDay'] = false;
    }
    $calendarEvents[] = $entry;
}
$currentUserId = (int) ($_SESSION['id'] ?? 0);
$socketRooms = ['rrhh:calendario:global'];
if ($currentUserId > 0) {
    array_unshift($socketRooms, 'rrhh:empleado:' . $currentUserId);
}

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

$marcajesBiometricoHoy = 0;
try {
    require_once __DIR__ . '/../../backend/php/biometric_marcas_db.php';
    if ($connect instanceof PDO) {
        $biometricDash = medidata_biometric_fetch_dash_stats($connect);
        $marcajesBiometricoHoy = (int) ($biometricDash['hoy'] ?? 0);
    }
} catch (Throwable $e) {
    error_log('escritorio marcajes biometrico: ' . $e->getMessage());
}

$eventTypes = [];
try {
    $pdoRRHH = medidata_rrhh_pdo();
    if ($pdoRRHH) {
        $eventTypes = $pdoRRHH->query('SELECT id, name, default_color FROM rrhh_calendar_event_types ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log('escritorio eventTypes: ' . $e->getMessage());
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
            <?php include_once __DIR__ . '/notifications.php'; ?>
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
                    <div class="rrhh-kpi">
                        <h2>Marcajes hoy</h2>
                        <p><?php echo number_format($marcajesBiometricoHoy); ?></p>
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
                                        $notifColor = htmlspecialchars($event['color'] ?? '#888', ENT_QUOTES, 'UTF-8');
                                        echo '<div class="notification-item notification-item--accent" style="--notif-accent:'.$notifColor.';">';
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
                                        echo '<div class="notification-item notification-item--vacancy">';
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
                                    $weekStartTs = strtotime('-7 days 00:00:00');
                                    $weekEndTs = strtotime('+7 days 23:59:59');
                                    $agendaTypes = ['custom', 'birthday', 'anniversary'];
                                    $recentEvents = array_filter($events, function ($e) use ($agendaTypes, $weekStartTs, $weekEndTs) {
                                        if (!in_array(($e['type'] ?? ''), $agendaTypes, true)) {
                                            return false;
                                        }
                                        $ts = strtotime((string) ($e['start'] ?? ''));
                                        return $ts !== false && $ts >= $weekStartTs && $ts <= $weekEndTs;
                                    });
                                    usort($recentEvents, fn($a, $b) => strcmp($b['start'], $a['start']));
                                    foreach ($recentEvents as $event) {
                                        if ($pastCount >= 50) break; // tope por rendimiento; el scroll muestra ~9 a la vez
                                        $ts = strtotime((string) $event['start']);
                                        $dateStr = date('d/m', $ts);
                                        $isAllDay = !empty($event['allDay'])
                                            || ($event['type'] ?? '') === 'birthday'
                                            || ($event['type'] ?? '') === 'anniversary';
                                        $when = $dateStr;
                                        if (!$isAllDay) {
                                            $endTs = !empty($event['end']) ? strtotime((string) $event['end']) : false;
                                            if ($endTs && $endTs < time()) {
                                                $when = $dateStr . ' · finalizó a las ' . date('g:i a', $endTs);
                                            } else {
                                                $when = $dateStr . ' · Comienza ' . date('g:i a', $ts);
                                            }
                                        }
                                        echo '<div class="notification-item notification-item--past">';
                                        echo '<strong>'.htmlspecialchars($event['title'] ?? '').'</strong>';
                                        echo '<p>'.htmlspecialchars($when).'</p>';
                                        echo '</div>';
                                        $pastCount++;
                                    }
                                    if ($pastCount === 0) {
                                        echo '<p>Sin actividad en los últimos 7 días.</p>';
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
    
    <script src='../../backend/js/fullcalendar/fullcalendar.js'></script>
    <script src='../../backend/js/fullcalendar/locale/es.js'></script>
    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
    
    <script>
        var rrhhCalendarEvents = <?php echo json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        $(document).ready(function () {
            var rrhhIsUsr = window.location.pathname.indexOf('_usr') !== -1;
            var detallePostulantePage = 'detalle_postulante_usr.php';
            var vacantesTrabajoPage = 'vacantes_trabajo_usr.php';

            // Establecer idioma de moment explicitly
            moment.locale('es');

            function updateRrhhBodyScrollLock() {
                if ($('#eventModal:visible, #addCustomEventModal:visible').length > 0) {
                    $('html, body').addClass('rrhh-modal-open');
                } else {
                    $('html, body').removeClass('rrhh-modal-open');
                }
            }

            function openRrhhModal(modalId) {
                var $modal = $('#' + modalId);
                if ($modal.length) {
                    $modal.stop(true, true).css({ display: 'flex', opacity: 0 })
                        .animate({ opacity: 1 }, 200, updateRrhhBodyScrollLock);
                }
            }

            function closeRrhhModal(modalId, doneCallback) {
                var $modal = $('#' + modalId);
                if ($modal.length) {
                    $modal.stop(true, true).animate({ opacity: 0 }, 200, function () {
                        $modal.css('display', 'none');
                        updateRrhhBodyScrollLock();
                        if (typeof doneCallback === 'function') {
                            doneCallback();
                        }
                    });
                }
            }

            $('.rrhh-close-event-modal').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                closeRrhhModal('eventModal');
            });

            $('.rrhh-close-custom-modal').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                closeRrhhModal('addCustomEventModal');
            });

            $(document).on('click', '#eventModal, #addCustomEventModal', function (e) {
                if (e.target === this) {
                    closeRrhhModal(this.id);
                }
            });

            $('#eventModal .modal-content, #addCustomEventModal .modal-content').on('click', function (e) {
                if ($(e.target).closest('.close-btn').length) {
                    return;
                }
                e.stopPropagation();
            });

            var date = new Date();
            var yyyy = date.getFullYear().toString();
            var mm = (date.getMonth() + 1).toString().padStart(2, '0');
            var dd = date.getDate().toString().padStart(2, '0');

            $('#calendar').fullCalendar({
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                defaultView: 'agendaWeek',
                locale: 'es',
                defaultDate: yyyy + '-' + mm + '-' + dd,
                editable: true,
                eventLimit: true,
                lazyFetching: false,
                selectable: true,
                selectHelper: true,
                displayEventTime: true,
                displayEventEnd: true,
                timeFormat: 'HH:mm',
                showNonCurrentDates: false,
                select: function(start, end) {
                    // En vistas Semana/Día el clic trae la hora de la franja
                    // (start.hasTime()); en vista Mes es un día completo sin hora.
                    var conHora = start.hasTime();
                    $('#customEventId').val('');
                    $('#customEventTitle').val('');
                    $('#customEventStartDate').val(start.format('YYYY-MM-DD'));
                    $('#customEventStartTime').val(conHora ? start.format('HH:mm') : '');
                    if (conHora && end) {
                        // El "end" de FullCalendar es exclusivo: precarga el fin
                        // de la franja seleccionada.
                        $('#customEventEndDate').val(end.format('YYYY-MM-DD'));
                        $('#customEventEndTime').val(end.format('HH:mm'));
                    } else {
                        $('#customEventEndDate').val('');
                        $('#customEventEndTime').val('');
                    }
                    $('#customEventColor').val('#035c67');
                    $('#customEventType').val('').trigger('change');
                    $('#customEventDescription').val('');
                    $('#customEventAllDay').prop('checked', false).trigger('change');
                    $('#customEventPublic').prop('checked', false);
                    $('#customEventRecurrence').val('none').trigger('change');
                    $('#customEventRecurrenceUntil').val('');
                    $('#customEventModalTitle').text('Añadir Nuevo Evento');
                    openRrhhModal('addCustomEventModal');
                    $('#calendar').fullCalendar('unselect');
                },
                events: rrhhCalendarEvents,
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

            // ============================================================
            // Socket.IO: notificaciones en tiempo real del calendario RRHH
            // Servidor: https://medidata.medicasa.hn  (path /socket.io/)
            // Salas: rrhh:empleado:{id_user} y rrhh:calendario:global
            // Evento: 'actualizacion' -> { tipo, datos }
            // ============================================================
            var rrhhSocket = null;
            var rrhhSocketRooms = <?php echo json_encode($socketRooms, JSON_UNESCAPED_UNICODE); ?>;

            function setRrhhSocketStatus(state) {
                var dot = document.getElementById('ws-status-indicator');
                var txt = document.getElementById('ws-status-text');
                var map = {
                    online: { cls: 'ws-online', text: 'Conexión establecida' },
                    reconnecting: { cls: 'ws-reconnecting', text: 'Reconectando...' },
                    failed: { cls: 'ws-failed', text: 'Sin conexión' }
                };
                var s = map[state] || map.reconnecting;
                if (dot) {
                    dot.classList.remove('ws-online', 'ws-reconnecting', 'ws-failed');
                    dot.classList.add(s.cls);
                }
                if (txt) { txt.textContent = s.text; }
            }

            function joinRrhhSocketRooms() {
                if (!rrhhSocket) return;
                (rrhhSocketRooms || []).forEach(function (sala) {
                    // 'join' es la convención estándar; 'unirse' como respaldo
                    // por si el servidor usa el nombre en español.
                    rrhhSocket.emit('join', sala);
                    rrhhSocket.emit('unirse', sala);
                });
            }

            function mapSocketCustomEvent(datos) {
                if (!datos) return null;
                var rawId = datos.raw_id || datos.id;
                var isAllDay = (datos.all_day == 1 || datos.all_day === true);
                var start = datos.start_date || '';
                var end = datos.end_date || '';
                if (!isAllDay) {
                    if (datos.start_time) { start = (datos.start_date || '') + ' ' + datos.start_time; }
                    if (datos.end_time && datos.end_date) { end = datos.end_date + ' ' + datos.end_time; }
                }
                return {
                    id: 'custom_' + rawId,
                    raw_id: rawId,
                    title: datos.title || '(sin título)',
                    start: start,
                    end: end || start,
                    color: datos.color || '#035c67',
                    allDay: isAllDay,
                    type: 'custom',
                    id_event_type: datos.id_event_type || '',
                    event_type_name: datos.event_type_name || '',
                    description: datos.description || '',
                    is_public: datos.is_public,
                    recurrence: datos.recurrence || 'none',
                    recurrence_until: datos.recurrence_until || null,
                    start_date: datos.start_date || '',
                    start_time: datos.start_time || null,
                    end_date: datos.end_date || '',
                    end_time: datos.end_time || null,
                    all_day: isAllDay ? 1 : 0
                };
            }

            // Expande un evento recurrente en ocurrencias (espejo del expansor PHP).
            // Ventana: hoy-31d .. hoy+366d, acotada por recurrence_until, tope 400.
            function rrhhExpandRecurring(mapped) {
                var rec = mapped.recurrence || 'none';
                var startDate = mapped.start_date || (mapped.start ? moment(mapped.start).format('YYYY-MM-DD') : '');
                if (!startDate) return [mapped];

                var isAllDay = !!mapped.allDay;
                var startTime = isAllDay ? null : (mapped.start_time || null);
                var startMoment = moment(mapped.start);
                var endMoment = mapped.end ? moment(mapped.end) : startMoment.clone();
                var durationMs = Math.max(0, endMoment.diff(startMoment));
                var spanDays = Math.max(1, Math.round(endMoment.clone().startOf('day').diff(startMoment.clone().startOf('day'), 'days')));

                var winStart = moment().subtract(31, 'days').startOf('day');
                var winEnd = moment().add(366, 'days').endOf('day');
                var until = winEnd;
                if (mapped.recurrence_until) {
                    var ru = moment(mapped.recurrence_until + ' 23:59:59');
                    if (ru.isValid() && ru.isBefore(winEnd)) until = ru;
                }

                function makeOcc(D) {
                    var occ = $.extend({}, mapped);
                    occ.id = 'custom_' + mapped.raw_id + '_occ_' + D.replace(/-/g, '');
                    occ.raw_id = mapped.raw_id;
                    if (isAllDay) {
                        occ.start = D;
                        occ.end = moment(D, 'YYYY-MM-DD').add(spanDays, 'days').format('YYYY-MM-DD');
                        occ.start_date = D;
                        occ.end_date = occ.end;
                    } else {
                        var s = D + ' ' + (startTime || '00:00:00');
                        occ.start = s;
                        occ.end = moment(s).add(durationMs, 'ms').format('YYYY-MM-DD HH:mm:ss');
                        occ.start_date = D;
                        occ.end_date = moment(occ.end).format('YYYY-MM-DD');
                    }
                    occ.allDay = isAllDay;
                    return occ;
                }

                var out = [];
                var anchor = moment(startDate, 'YYYY-MM-DD');
                var guard = 0;
                if (rec === 'daily' || rec === 'weekly' || rec === 'weekdays') {
                    var cur = anchor.clone();
                    var stepDays = rec === 'weekly' ? 7 : 1;
                    while (guard++ < 6000 && out.length < 400) {
                        if (cur.isAfter(until)) break;
                        if (!cur.isBefore(winStart)) {
                            var emit = true;
                            if (rec === 'weekdays') { var dow = cur.isoWeekday(); emit = dow <= 5; }
                            if (emit) out.push(makeOcc(cur.format('YYYY-MM-DD')));
                        }
                        cur.add(stepDays, 'days');
                    }
                } else if (rec === 'monthly') {
                    var day = anchor.date();
                    for (var i = 0; guard++ < 6000 && out.length < 400; i++) {
                        var cand = anchor.clone().add(i, 'months');
                        if (cand.date() !== day) continue; // mes sin ese día (clamp de moment)
                        if (cand.isAfter(until)) break;
                        if (!cand.isBefore(winStart)) out.push(makeOcc(cand.format('YYYY-MM-DD')));
                    }
                } else if (rec === 'yearly') {
                    var md = anchor.format('MM-DD');
                    for (var y = 0; guard++ < 6000 && out.length < 400; y++) {
                        var candY = anchor.clone().add(y, 'years');
                        if (candY.format('MM-DD') !== md) continue; // 29-feb no bisiesto
                        if (candY.isAfter(until)) break;
                        if (!candY.isBefore(winStart)) out.push(makeOcc(candY.format('YYYY-MM-DD')));
                    }
                } else {
                    return [mapped];
                }
                return out;
            }

            function upsertSocketCustomEvent(datos) {
                var mapped = mapSocketCustomEvent(datos);
                if (!mapped) return;
                var rawId = mapped.raw_id;
                var rec = mapped.recurrence || 'none';

                // Quitar todas las ocurrencias previas de esta serie.
                $('#calendar').fullCalendar('removeEvents', function (ev) {
                    return ev && ev.raw_id == rawId && ev.type === 'custom';
                });
                if (Array.isArray(rrhhCalendarEvents)) {
                    rrhhCalendarEvents = rrhhCalendarEvents.filter(function (e) {
                        return !(e && e.raw_id == rawId && e.type === 'custom');
                    });
                }

                var nuevos = (rec === 'none') ? [mapped] : rrhhExpandRecurring(mapped);
                nuevos.forEach(function (occ) {
                    $('#calendar').fullCalendar('renderEvent', occ, true);
                    if (Array.isArray(rrhhCalendarEvents)) rrhhCalendarEvents.push(occ);
                });
            }

            function removeSocketCustomEvent(datos) {
                if (!datos) return;
                var rawId = datos.raw_id || datos.id;
                $('#calendar').fullCalendar('removeEvents', function (ev) {
                    return ev && ev.raw_id == rawId && ev.type === 'custom';
                });
                if (Array.isArray(rrhhCalendarEvents)) {
                    rrhhCalendarEvents = rrhhCalendarEvents.filter(function (e) {
                        return !(e && e.raw_id == rawId && e.type === 'custom');
                    });
                }
            }

            // El servidor emite { tipo:'actualizacion', datos:'<json string>' } donde
            // ese string contiene { tipo:'creado'|'editado'|'eliminado', datos:{...} }.
            // Normalizamos a { tipo, datos(objeto) } tolerando también el formato directo.
            function normalizeRrhhSocketPayload(payload) {
                if (!payload) return null;
                var datos = payload.datos;
                if (typeof datos === 'string') {
                    try { datos = JSON.parse(datos); } catch (e) { return null; }
                }
                if (datos && typeof datos === 'object' && datos.tipo && datos.datos) {
                    return { tipo: datos.tipo, datos: datos.datos };
                }
                return { tipo: payload.tipo, datos: datos };
            }

            function handleRrhhSocketUpdate(rawPayload) {
                var payload = normalizeRrhhSocketPayload(rawPayload);
                if (!payload || !payload.datos) return;
                try {
                    switch (payload.tipo) {
                        case 'creado':
                        case 'editado':
                            upsertSocketCustomEvent(payload.datos);
                            break;
                        case 'eliminado':
                            removeSocketCustomEvent(payload.datos);
                            break;
                    }
                    updateNotifications();
                    if (window.rrhhNotif && typeof window.rrhhNotif.pushLiveUpdate === 'function') {
                        window.rrhhNotif.pushLiveUpdate(payload);
                    }
                } catch (err) {
                    console.error('handleRrhhSocketUpdate:', err);
                }
            }

            function initRrhhSocket() {
                if (typeof io === 'undefined') {
                    setRrhhSocketStatus('failed');
                    return;
                }
                try {
                    rrhhSocket = io('https://medidata.medicasa.hn', {
                        path: '/socket.io/',
                        transports: ['websocket', 'polling'],
                        reconnection: true,
                        reconnectionAttempts: Infinity,
                        reconnectionDelay: 2000
                    });

                    rrhhSocket.on('connect', function () {
                        setRrhhSocketStatus('online');
                        joinRrhhSocketRooms();
                    });
                    rrhhSocket.on('reconnect', function () {
                        setRrhhSocketStatus('online');
                        joinRrhhSocketRooms();
                    });
                    rrhhSocket.on('disconnect', function () { setRrhhSocketStatus('reconnecting'); });
                    rrhhSocket.on('reconnecting', function () { setRrhhSocketStatus('reconnecting'); });
                    rrhhSocket.on('reconnect_attempt', function () { setRrhhSocketStatus('reconnecting'); });
                    rrhhSocket.on('connect_error', function () { setRrhhSocketStatus('reconnecting'); });

                    rrhhSocket.on('actualizacion', function (payload) {
                        handleRrhhSocketUpdate(payload);
                    });
                } catch (err) {
                    console.error('initRrhhSocket:', err);
                    setRrhhSocketStatus('failed');
                }
            }

            initRrhhSocket();

            window.addEventListener('beforeunload', function () {
                if (rrhhSocket) {
                    try { rrhhSocket.disconnect(); } catch (e) {}
                }
            });

            function updateEventDate(event, revertFunc) {
                if (!event || !event.start) { revertFunc(); return; }
                if (event.type !== 'interview' && event.type !== 'custom') {
                    Swal.fire({ icon: 'warning', title: 'Acción no permitida', text: 'Este tipo de evento no puede ser reprogramado mediante arrastre.' });
                    revertFunc(); return;
                }
                if (event.type === 'custom' && event.recurrence && event.recurrence !== 'none') {
                    Swal.fire({ icon: 'info', title: 'Evento recurrente', text: 'Para reprogramar un evento que se repite, edítalo desde el detalle del evento.' });
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
                        <button onclick="deleteEvent('${event.id}', 'interview')" class="button rrhh-btn-inline rrhh-btn-inline--danger">Cancelar Entrevista</button>
                        <a href="${detallePostulantePage}?id=${event.candidate_id}" class="button rrhh-btn-inline rrhh-btn-inline--primary">Ver Perfil Candidato</a>
                    `);
                } else if (event.type === 'vacancy_end') {
                    $('#event-details tbody').append(`
                        <tr><th>Puesto</th><td>${event.position_name || ''}</td></tr>
                        <tr><th>Fecha Cierre</th><td>${event.start.format('YYYY-MM-DD')}</td></tr>
                        <tr><th>Beneficios</th><td>${event.benefits || 'N/A'}</td></tr>
                        <tr><th>Tipo</th><td>Cierre de Vacante</td></tr>
                    `);

                    $('#modal-footer').append(`
                        <a href="${vacantesTrabajoPage}" class="button rrhh-btn-inline rrhh-btn-inline--danger">Gestionar Vacante</a>
                    `);
                } else if (event.type === 'custom') {
                    const isAllDay = !!event.allDay;
                    const formatStr = isAllDay ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm';
                    let endStr = '';
                    if (event.end) {
                        endStr = isAllDay
                            ? moment(event.end).subtract(1, 'days').format(formatStr)
                            : event.end.format(formatStr);
                    }

                    const recLabels = { daily: 'Diario', weekly: 'Cada semana', monthly: 'Cada mes', yearly: 'Anual', weekdays: 'Días laborables (L-V)' };
                    let recRow = '';
                    if (event.recurrence && event.recurrence !== 'none' && recLabels[event.recurrence]) {
                        let recTxt = recLabels[event.recurrence];
                        if (event.recurrence_until) {
                            recTxt += ' (hasta ' + moment(event.recurrence_until).format('DD/MM/YYYY') + ')';
                        }
                        recRow = `<tr><th>Se repite</th><td>${recTxt}</td></tr>`;
                    }

                    $('#event-details tbody').append(`
                        <tr><th>Tipo</th><td>${event.event_type_name || 'Evento General'}</td></tr>
                        <tr><th>Inicio</th><td>${event.start ? event.start.format(formatStr) : ''}</td></tr>
                        <tr><th>Fin</th><td>${endStr}</td></tr>
                        ${event.description ? `<tr><th>Descripción</th><td>${event.description}</td></tr>` : ''}
                        <tr><th>Todo el día</th><td>${isAllDay ? 'Sí' : 'No'}</td></tr>
                        ${recRow}
                        <tr><th>Visibilidad</th><td>${event.is_public == 1 ? 'Público' : 'Privado'}</td></tr>
                    `);

                    $('#modal-footer').append(`
                        <button onclick="deleteEvent('${event.raw_id || event.id}', 'custom')" class="button rrhh-btn-inline rrhh-btn-inline--danger">Eliminar</button>
                        <button onclick="editCustomEvent('${event.id}')" class="button rrhh-btn-inline rrhh-btn-inline--accent">Editar</button>
                    `);
                } else if (event.type === 'birthday') {
                    $('#event-details tbody').append(`
                        <tr><th>Tipo</th><td>Cumpleaños de Personal</td></tr>
                        <tr><th>Fecha</th><td>${event.start.format('YYYY-MM-DD')}</td></tr>
                    `);
                    $('#modal-footer').append(`
                        <button type="button" class="button rrhh-btn-inline rrhh-btn-inline--primary rrhh-close-event-modal">Cerrar</button>
                    `);
                } else if (event.type === 'anniversary') {
                    $('#event-details tbody').append(`
                        <tr><th>Tipo</th><td>Aniversario laboral</td></tr>
                        <tr><th>Fecha</th><td>${event.start.format('YYYY-MM-DD')}</td></tr>
                    `);
                    $('#modal-footer').append(`
                        <button type="button" class="button rrhh-btn-inline rrhh-btn-inline--primary rrhh-close-event-modal">Cerrar</button>
                    `);
                }
                
                openRrhhModal('eventModal');
            }

            window.deleteEvent = function(prefixedId, type) {
                const title = type === 'interview' ? '¿Cancelar entrevista?' : '¿Eliminar evento?';
                const text = type === 'interview' ? 'Esta acción quitará la entrevista de la agenda.' : 'El evento será eliminado permanentemente.';
                const numericId = prefixedId.toString().split('_').pop();
                const showDeleteConfirm = function() {
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#FC3B56',
                        cancelButtonColor: '#888',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'No, mantener'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('../../backend/registros/delete_rrhh_calendar_event.php', { id: numericId, type: type }, function(response) {
                                if (response.success) {
                                    if (type === 'custom') {
                                        // Quitar toda la serie (todas las ocurrencias).
                                        $('#calendar').fullCalendar('removeEvents', function (ev) {
                                            return ev && ev.raw_id == numericId && ev.type === 'custom';
                                        });
                                        if (Array.isArray(rrhhCalendarEvents)) {
                                            rrhhCalendarEvents = rrhhCalendarEvents.filter(function (e) {
                                                return !(e && e.raw_id == numericId && e.type === 'custom');
                                            });
                                        }
                                    } else {
                                        $('#calendar').fullCalendar('removeEvents', prefixedId);
                                    }
                                    Swal.fire('Eliminado', response.message, 'success');
                                    updateNotifications();
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            }, 'json');
                        }
                    });
                };

                if ($('#eventModal').is(':visible')) {
                    closeRrhhModal('eventModal', showDeleteConfirm);
                } else {
                    showDeleteConfirm();
                }
            };

            window.editCustomEvent = function(eventId) {
                const event = $('#calendar').fullCalendar('clientEvents', eventId)[0];
                if (!event) return;
                closeRrhhModal('eventModal');
                $('#customEventId').val(event.raw_id || event.id);
                $('#customEventTitle').val(event.title);
                $('#customEventStartDate').val(event.start ? moment(event.start).format('YYYY-MM-DD') : '');
                $('#customEventStartTime').val(event.start && moment(event.start).format('HH:mm') !== '00:00' ? moment(event.start).format('HH:mm') : '');
                const endToUse = event.end ? moment(event.end) : moment(event.start);
                if (event.allDay && event.end) {
                    endToUse.subtract(1, 'days');
                }
                $('#customEventEndDate').val(endToUse.format('YYYY-MM-DD'));
                $('#customEventEndTime').val(!event.allDay && event.end && endToUse.format('HH:mm') !== '00:00' ? endToUse.format('HH:mm') : '');
                $('#customEventColor').val(event.color);
                $('#customEventType').val(event.id_event_type || '').trigger('change');
                $('#customEventDescription').val(event.description || '');
                $('#customEventAllDay').prop('checked', !!event.allDay).trigger('change');
                $('#customEventPublic').prop('checked', event.is_public == 1);
                $('#customEventRecurrence').val(event.recurrence || 'none').trigger('change');
                $('#customEventRecurrenceUntil').val(event.recurrence_until ? moment(event.recurrence_until).format('YYYY-MM-DD') : '');
                $('#customEventModalTitle').text('Editar Evento');
                openRrhhModal('addCustomEventModal');
            };

            function rrhhFmtAmPm(m) {
                let h = m.hours();
                const min = m.minutes();
                const suffix = h < 12 ? 'am' : 'pm';
                let h12 = h % 12;
                if (h12 === 0) h12 = 12;
                return h12 + ':' + (min < 10 ? '0' + min : min) + ' ' + suffix;
            }

            function rrhhActivityWhen(event) {
                const start = moment(event.start);
                const dateStr = start.format('DD/MM');
                if (event.allDay || event.type === 'birthday' || event.type === 'anniversary') {
                    return dateStr;
                }
                const ahora = moment();
                const end = event.end ? moment(event.end) : null;
                if (end && end.isBefore(ahora)) {
                    return dateStr + ' · finalizó a las ' + rrhhFmtAmPm(end);
                }
                return dateStr + ' · Comienza ' + rrhhFmtAmPm(start);
            }

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
                                <div class="notification-item notification-item--accent" style="--notif-accent: ${event.color || '#888'};">
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
                                <div class="notification-item notification-item--vacancy">
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
                    const weekStart = now.clone().subtract(7, 'days').startOf('day');
                    const weekEnd = now.clone().add(7, 'days').endOf('day');
                    const agendaTypes = ['custom', 'birthday', 'anniversary'];
                    events.filter(e => e && e.start
                                    && agendaTypes.indexOf(e.type) !== -1
                                    && moment(e.start).isBetween(weekStart, weekEnd, null, '[]'))
                      .sort((a,b) => moment(b.start) - moment(a.start))
                      .forEach(event => {
                        if (pastCount < 50) { // tope por rendimiento; el scroll muestra ~9 a la vez
                        pastOccupancy.append(`
                                <div class="notification-item notification-item--past">
                                    <strong>${event.title || 'N/A'}</strong>
                                    <p>${rrhhActivityWhen(event)}</p>
                            </div>
                        `);
                        pastCount++;
                    }
                });
                    if (pastCount === 0) pastOccupancy.append('<p>Sin actividad en los últimos 7 días.</p>');
                } catch (err) { console.error("Error en updateNotifications:", err); }
            }
            window.updateNotifications = updateNotifications;

            $('#customEventForm').submit(function(e) {
                e.preventDefault();
                const prefixedId = $('#customEventId').val();
                const title = $('#customEventTitle').val();
                const start_date = $('#customEventStartDate').val();
                const start_time = $('#customEventStartTime').val();
                let end_date = $('#customEventEndDate').val();
                const end_time = $('#customEventEndTime').val();
                const color = $('#customEventColor').val();
                const eventType = $('#customEventType').val();
                const description = $('#customEventDescription').val();
                const allDay = $('#customEventAllDay').is(':checked');
                const isPublic = $('#customEventPublic').is(':checked');
                const recurrence = $('#customEventRecurrence').val() || 'none';
                const recurrenceUntil = recurrence !== 'none' ? ($('#customEventRecurrenceUntil').val() || '') : '';

                if (!end_date) {
                    end_date = start_date;
                }

                const isUpdate = prefixedId !== '';
                const numericId = isUpdate ? prefixedId.toString().split('_').pop() : '';
                const url = isUpdate
                    ? '../../backend/registros/upd_rrhh_calendar_event.php'
                    : '../../backend/registros/add_rrhh_calendar_event.php';

                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        id: numericId,
                        title: title,
                        start_date: start_date,
                        start_time: start_time,
                        end_date: end_date,
                        end_time: end_time,
                        color: color,
                        id_event_type: eventType,
                        description: description,
                        all_day: allDay ? 'true' : 'false',
                        is_public: isPublic ? 'true' : 'false',
                        recurrence: recurrence,
                        recurrence_until: recurrenceUntil
                    },
                    success: function(response) {
                        if (response.success) {
                            // Reflejo optimista inmediato (sin recargar). El socket
                            // reconcilia este cliente y notifica a los demás en tiempo real.
                            try {
                                var optimisticEnd = end_date;
                                if (allDay) {
                                    optimisticEnd = moment(end_date, 'YYYY-MM-DD').add(1, 'days').format('YYYY-MM-DD');
                                }
                                upsertSocketCustomEvent({
                                    raw_id: response.id || numericId,
                                    title: title,
                                    start_date: start_date,
                                    start_time: allDay ? null : (start_time || null),
                                    end_date: optimisticEnd,
                                    end_time: allDay ? null : (end_time || null),
                                    color: color,
                                    id_event_type: eventType,
                                    event_type_name: $('#customEventType option:selected').text().trim(),
                                    description: description,
                                    all_day: allDay ? 1 : 0,
                                    is_public: isPublic ? 1 : 0,
                                    recurrence: recurrence,
                                    recurrence_until: recurrenceUntil || null
                                });
                                updateNotifications();
                            } catch (e) { console.error('upsert optimista:', e); }

                            closeRrhhModal('addCustomEventModal', function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: isUpdate ? 'Evento actualizado' : 'Evento creado',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message });
                        }
                    },
                    error: function() {
                        Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar al servidor.' });
                    }
                });
            });

            $('#customEventAllDay').change(function() {
                const isAllDay = $(this).is(':checked');
                if (isAllDay) {
                    $('#customEventStartTime, #customEventEndTime').hide().val('');
                } else {
                    $('#customEventStartTime, #customEventEndTime').show();
                }
            });

            $('#customEventType').change(function() {
                const selected = $(this).find('option:selected');
                const defaultColor = selected.data('color');
                if (defaultColor) {
                    $('#customEventColor').val(defaultColor);
                }
            });

            $('#customEventRecurrence').change(function() {
                const repite = $(this).val() !== 'none';
                $('#customEventRecurrenceUntilGroup').toggle(repite);
                if (!repite) $('#customEventRecurrenceUntil').val('');
            });

        });
    </script>

    <!-- Modal Detalles -->
    <div id="eventModal" class="modal modal--rrhh">
        <div class="modal-content modal-content--rrhh">
            <button type="button" class="close-btn rrhh-close-event-modal" aria-label="Cerrar">&times;</button>
            <h2 id="modal-title" class="rrhh-modal-title">Detalles</h2>
            <table id="event-details" class="rrhh-details-table">
                <tbody></tbody>
            </table>
            <div id="modal-footer" class="rrhh-modal-footer"></div>
        </div>
    </div>

    <!-- Modal Formulario -->
    <div id="addCustomEventModal" class="modal modal--rrhh">
        <div class="modal-content modal-content--rrhh">
            <button type="button" class="close-btn rrhh-close-custom-modal" aria-label="Cerrar">&times;</button>
            <h2 id="customEventModalTitle" class="rrhh-modal-title">Añadir Nuevo Evento</h2>
            <form id="customEventForm" class="rrhh-calendar-event-form">
                <input type="hidden" id="customEventId" value="">
                <div class="form-group rrhh-form-group">
                    <label for="customEventTitle">Título del Evento:</label>
                    <input type="text" id="customEventTitle" class="rrhh-form-control" required>
                </div>
                <div class="form-group rrhh-form-group">
                    <label for="customEventType">Tipo de Evento:</label>
                    <select id="customEventType" class="rrhh-form-control">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($eventTypes as $et): ?>
                            <option value="<?php echo (int) $et['id']; ?>" data-color="<?php echo htmlspecialchars((string) ($et['default_color'] ?? '#035c67')); ?>">
                                <?php echo htmlspecialchars((string) $et['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group rrhh-form-group">
                    <label for="customEventDescription">Descripción (opcional):</label>
                    <textarea id="customEventDescription" class="rrhh-form-control" rows="2"></textarea>
                </div>
                <div class="form-group rrhh-form-group rrhh-form-group--inline">
                    <label for="customEventAllDay">Todo el día:</label>
                    <label class="switch">
                        <input type="checkbox" id="customEventAllDay">
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="form-group rrhh-form-group">
                    <label>Inicio:</label>
                    <div class="rrhh-form-row">
                        <input type="date" id="customEventStartDate" class="rrhh-form-control" required>
                        <input type="time" id="customEventStartTime" class="rrhh-form-control">
                    </div>
                </div>
                <div class="form-group rrhh-form-group">
                    <label>Fin:</label>
                    <div class="rrhh-form-row">
                        <input type="date" id="customEventEndDate" class="rrhh-form-control">
                        <input type="time" id="customEventEndTime" class="rrhh-form-control">
                    </div>
                </div>
                <div class="form-group rrhh-form-group">
                    <label for="customEventRecurrence">Repetir:</label>
                    <select id="customEventRecurrence" class="rrhh-form-control">
                        <option value="none">No se repite</option>
                        <option value="daily">Diario</option>
                        <option value="weekly">Cada semana</option>
                        <option value="monthly">Cada mes</option>
                        <option value="yearly">Anual</option>
                        <option value="weekdays">Días laborables (L-V)</option>
                    </select>
                </div>
                <div class="form-group rrhh-form-group" id="customEventRecurrenceUntilGroup" style="display:none;">
                    <label for="customEventRecurrenceUntil">Repetir hasta (opcional):</label>
                    <input type="date" id="customEventRecurrenceUntil" class="rrhh-form-control">
                </div>
                <div class="form-group rrhh-form-group rrhh-form-group--inline">
                    <label for="customEventPublic">Hacer público:</label>
                    <label class="switch">
                        <input type="checkbox" id="customEventPublic">
                        <span class="slider round"></span>
                    </label>
                </div>
                <div class="form-group rrhh-form-group">
                    <label for="customEventColor">Color:</label>
                    <input type="color" id="customEventColor" class="rrhh-form-control rrhh-form-control--color" value="#035c67">
                </div>
                <div class="rrhh-form-actions">
                    <button type="submit" class="button rrhh-btn-submit">Guardar Evento</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 ya cargado arriba -->
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>