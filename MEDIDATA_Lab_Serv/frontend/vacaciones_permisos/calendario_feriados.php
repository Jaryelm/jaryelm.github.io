<?php
require_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    header('Location: mis_vacaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <title>MEDIDATA - CALENDARIO DE FERIADOS</title>
</head>
<body>
    <?php include 'menu_router.php'; ?>
    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>
            <div class="vp-panel">
                <div id="calendar"></div>
            </div>
        </main>
    </section>
    
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/moment.min.js"></script>
    <script src="../../backend/js/fullcalendar/fullcalendar.js"></script>
    <script src="../../backend/js/fullcalendar/locale/es.js"></script>

    <script>
    $(document).ready(function() {
        moment.locale('es');
        $('#calendar').fullCalendar({
            locale: 'es',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            editable: false,
            events: '../../backend/registros/vacaciones_permisos/fetch_calendario.php',
            eventRender: function(event, element) {
                // Formatting according to rule: Always use the eventRender callback to explicitly format and inject the event.title
                element.find('.fc-title').html("<strong>" + event.title + "</strong><br/><small>" + event.type + "</small>");
            },
            eventClick: function(event) {
                Swal.fire({
                    title: 'Detalles del Evento',
                    html: `
                        <table class="responsive-table" style="width:100%; text-align:left;">
                            <tr><th>Asunto:</th><td>${event.title}</td></tr>
                            <tr><th>Tipo:</th><td>${event.type}</td></tr>
                            <tr><th>Inicio:</th><td>${moment(event.start).format('DD/MM/YYYY HH:mm')}</td></tr>
                            <tr><th>Fin:</th><td>${event.end ? moment(event.end).format('DD/MM/YYYY HH:mm') : 'N/A'}</td></tr>
                        </table>
                    `,
                    icon: 'info'
                });
            }
        });
    });
    </script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
