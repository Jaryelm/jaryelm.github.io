<?php
/**
 * Agenda institucional (eventos públicos / compartidos desde RRHH).
 */
include_once '../../backend/registros/session_check.php';
include_once '../../backend/registros/it_guard.php';
require_once '../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/medidata_calendar_institucional_lib.php';

$events = medidata_calendar_institucional_as_programacion_events((int) ($_SESSION['id'] ?? 0));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='../../backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet'>
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA — Programación</title>
</head>
<body>
<?php include_once '../it/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../it/perfil.php'; ?>
    </nav>

    <main>
        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Programación</h3>
                    <p style="margin:4px 0 0;color:#555;font-size:13px;">Eventos institucionales públicos o compartidos con usted desde Recursos Humanos.</p>
                </div>
                <div id="calendar-container" class="rrhh-calendar-layout">
                    <div id="calendar" class="col-centered"></div>
                    <div id="notification-panel">
                        <h4>Notificaciones</h4>
                        <div id="notifications"><p>No hay eventos disponibles.</p></div>
                        <div id="future-events">
                            <h4>Eventos próximos</h4>
                            <div id="future-occupancy"><p>Cargando…</p></div>
                        </div>
                        <div id="past-events">
                            <h4>Eventos antiguos</h4>
                            <div id="past-occupancy"><p>Cargando…</p></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/moment.min.js"></script>
<script src="../../backend/js/fullcalendar/fullcalendar.min.js"></script>
<script src="../../backend/js/fullcalendar/fullcalendar.js"></script>
<script src="../../backend/js/fullcalendar/locale/es.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script>
$(function () {
    moment.locale('es');
    var date = new Date();
    var yyyy = date.getFullYear().toString();
    var mm = (date.getMonth() + 1).toString().padStart(2, '0');
    var dd = date.getDate().toString().padStart(2, '0');
    var events = <?php echo json_encode(array_values(array_map(static function ($e) {
        return [
            'id' => (string) ($e['id'] ?? ''),
            'title' => (string) ($e['title'] ?? ''),
            'start' => (string) ($e['start'] ?? ''),
            'end' => (string) ($e['end'] ?? ''),
            'color' => (string) ($e['color'] ?? '#035c67'),
            'description' => (string) ($e['evaluation'] ?? ''),
            'area' => (string) ($e['area_name'] ?? 'Agenda institucional'),
        ];
    }, $events)), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    $('#calendar').fullCalendar({
        header: {
            language: 'es',
            left: 'prev,next today',
            center: 'title',
            right: 'month,basicWeek,basicDay'
        },
        defaultDate: yyyy + '-' + mm + '-' + dd,
        editable: false,
        eventLimit: true,
        events: events,
        eventClick: function (event) {
            var msg = (event.title || '') + '\n' + (event.start ? moment(event.start).format('DD/MM/YYYY HH:mm') : '');
            if (event.description) {
                msg += '\n\n' + event.description;
            }
            alert(msg);
        }
    });

    function refreshLists() {
        var now = moment();
        var future = $('#future-occupancy').empty();
        var past = $('#past-occupancy').empty();
        var all = $('#calendar').fullCalendar('clientEvents') || [];
        var upcoming = all.filter(function (e) { return e.start && moment(e.start).isAfter(now); })
            .sort(function (a, b) { return moment(a.start) - moment(b.start); });
        var older = all.filter(function (e) { return e.start && moment(e.start).isBefore(now); })
            .sort(function (a, b) { return moment(b.start) - moment(a.start); });

        if (!upcoming.length) {
            future.append('<p>No hay eventos próximos.</p>');
        } else {
            upcoming.slice(0, 8).forEach(function (e) {
                future.append(
                    '<div class="notification-item notification-item--accent" style="--notif-accent:' + (e.color || '#035c67') + ';">' +
                    '<strong>' + $('<div>').text(e.title || '').html() + '</strong>' +
                    '<p>' + moment(e.start).format('DD/MM/YYYY HH:mm') + '</p></div>'
                );
            });
        }

        if (!older.length) {
            past.append('<p>Sin eventos anteriores.</p>');
        } else {
            older.slice(0, 12).forEach(function (e) {
                past.append(
                    '<div class="notification-item notification-item--past">' +
                    '<strong>' + $('<div>').text(e.title || '').html() + '</strong>' +
                    '<p>' + moment(e.start).format('DD/MM/YYYY') + '</p></div>'
                );
            });
        }

        $('#notifications').html(
            all.length
                ? '<p>' + all.length + ' evento(s) institucional(es).</p>'
                : '<p>No hay eventos disponibles.</p>'
        );
    }

    refreshLists();
});
</script>
</body>
</html>
