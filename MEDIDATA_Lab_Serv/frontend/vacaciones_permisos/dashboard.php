<?php
require_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <title>MEDIDATA - DASHBOARD VACACIONES Y PERMISOS</title>
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

        <div class="rrhh-dashboard" id="metrics-container">
            <!-- Metrics will be loaded here via AJAX -->
            <div class="rrhh-kpi"><p>Cargando...</p></div>
        </div>

        <div class="vp-panel">
            <h3>Calendario de Ausencias y Vacaciones</h3>
            <div id="calendar"></div>
        </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/moment.min.js"></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.min.js'></script>
    <script src='../../backend/js/fullcalendar/locale/es.js'></script>
    <script>
    $(document).ready(function() {
        // Load Metrics
        $.getJSON('../../backend/registros/vacaciones_permisos/dashboard_metrics.php', function(data) {
            if(data.error) {
                $('#metrics-container').html('<div class="rrhh-kpi vp-kpi-error"><h2>Error</h2><p>'+data.error+'</p></div>');
                return;
            }

            let html = `
                <div class="rrhh-kpi">
                    <h2>Vacaciones Activas</h2>
                    <p>${data.vacaciones_activas}</p>
                    <small class="vp-kpi-note">Días pendientes totales: ${data.dias_pendientes_empresa}</small>
                </div>
                <div class="rrhh-kpi">
                    <h2>Incapacidades Activas</h2>
                    <p>${data.incapacidades_activas}</p>
                </div>
                <div class="rrhh-kpi">
                    <h2>Permisos (Este Mes)</h2>
                    <p>${data.permisos_mes}</p>
                </div>
                <div class="rrhh-kpi">
                    <h2>Solicitudes Pendientes</h2>
                    <p>${data.solicitudes_pendientes}</p>
                </div>
                <div class="rrhh-kpi">
                    <h2>Solicitudes Aprobadas</h2>
                    <p>${data.solicitudes_aprobadas}</p>
                </div>
                <div class="rrhh-kpi">
                    <h2>Solicitudes Rechazadas</h2>
                    <p>${data.solicitudes_rechazadas}</p>
                </div>
            `;

            if(data.proximo_vacaciones) {
                const pv = data.proximo_vacaciones;
                const nota = pv.dentro_ventana
                    ? `Con derecho vigente (ventana ±2 meses) · ${pv.fecha}`
                    : `Cumple ciclo en: ${pv.fecha}`;
                html += `
                <div class="rrhh-kpi vp-kpi-highlight">
                    <h2>Próximo derecho a Vacaciones</h2>
                    <p>${pv.nombre}</p>
                    <small class="vp-kpi-note">${nota}</small>
                </div>
                `;
            }

            $('#metrics-container').html(html);
        });

        // Initialize Calendar
        moment.locale('es');
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            locale: 'es',
            events: '../../backend/registros/vacaciones_permisos/fetch_calendario.php',
            eventRender: function(event, element) {
                // Rule: Always use the eventRender callback to explicitly format and inject the event.title
                element.find('.fc-title').html("<strong>" + event.title + "</strong>");
            }
        });
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
