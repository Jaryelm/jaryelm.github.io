<?php
session_start();
if (!isset($_SESSION['id']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Vacaciones y Permisos</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/fullcalendar.min.css' rel='stylesheet' />
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Moment.js is required by FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/fullcalendar.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@3.10.2/dist/locale/es.js'></script>
    
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
            border-top: 4px solid var(--blue, #06adbf);
        }
        .metric-card h3 {
            margin: 0;
            font-size: 1.1em;
            color: #555;
        }
        .metric-card .value {
            font-size: 2em;
            font-weight: bold;
            color: var(--dark-blue, #035c67);
            margin-top: 10px;
        }
        .metric-card.proximo {
            grid-column: 1 / -1;
            background: #f8fbff;
            border-top-color: #f39c12;
        }
        .metric-card.proximo .value {
            font-size: 1.5em;
            color: #d35400;
        }
        #calendar-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        /* Custom calendar event styling via eventRender is handled in JS */
    </style>
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
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
        
        <div class="dashboard-grid" id="metrics-container">
            <!-- Metrics will be loaded here via AJAX -->
            <div class="metric-card"><div class="value">Cargando...</div></div>
        </div>

        <div id="calendar-container">
            <h3>Calendario de Ausencias y Vacaciones</h3>
            <br>
            <div id="calendar"></div>
        </div>
        </div>
        </main>
    </section>

    <script src="../../backend/js/script.js"></script>
    <script>
    $(document).ready(function() {
        // Load Metrics
        $.getJSON('../../backend/registros/vacaciones_permisos/dashboard_metrics.php', function(data) {
            if(data.error) {
                $('#metrics-container').html('<div class="metric-card"><h3 style="color:red">Error</h3><div class="value">'+data.error+'</div></div>');
                return;
            }
            
            let html = `
                <div class="metric-card">
                    <h3>Vacaciones Activas</h3>
                    <div class="value">${data.vacaciones_activas}</div>
                    <small style="color:#777">Días pendientes totales: ${data.dias_pendientes_empresa}</small>
                </div>
                <div class="metric-card">
                    <h3>Incapacidades Activas</h3>
                    <div class="value">${data.incapacidades_activas}</div>
                </div>
                <div class="metric-card">
                    <h3>Permisos (Este Mes)</h3>
                    <div class="value">${data.permisos_mes}</div>
                </div>
                <div class="metric-card">
                    <h3>Solicitudes Pendientes</h3>
                    <div class="value">${data.solicitudes_pendientes}</div>
                </div>
                <div class="metric-card">
                    <h3>Solicitudes Aprobadas</h3>
                    <div class="value">${data.solicitudes_aprobadas}</div>
                </div>
                <div class="metric-card">
                    <h3>Solicitudes Rechazadas</h3>
                    <div class="value">${data.solicitudes_rechazadas}</div>
                </div>
            `;
            
            if(data.proximo_vacaciones) {
                html += `
                <div class="metric-card proximo">
                    <h3>Próximo derecho a Vacaciones</h3>
                    <div class="value">${data.proximo_vacaciones.nombre}</div>
                    <small>Cumple ciclo en: ${data.proximo_vacaciones.fecha}</small>
                </div>
                `;
            }
            
            $('#metrics-container').html(html);
        });

        // Initialize Calendar
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





