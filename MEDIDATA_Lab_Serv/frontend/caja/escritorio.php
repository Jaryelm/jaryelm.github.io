<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<?php
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
        <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css" />
        <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css" />

    <!-- FullCalendar -->
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    
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
include_once '../caja/menu.php';
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
include_once '../caja/perfil.php';
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


<!-- Dashboard Start -->

<style>
    /* Estilo para el botón de cierre de caja */
.btn-cierre {
    background-color: #035c67; /* Color principal */
    color: #fff; /* Texto blanco */
    padding: 10px 20px; /* Espaciado interno */
    border: none; /* Sin borde */
    border-radius: 5px; /* Bordes redondeados */
    cursor: pointer; /* Indicador de clic */
    font-size: 16px; /* Tamaño del texto */
    font-weight: bold; /* Texto en negrita */
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Sombra */
    margin-top: 10px; /* Separación superior */
    display: block;
    width: 100%; /* Botón ancho */
    text-align: center; /* Centrado del texto */
}

/* Efecto hover */
.btn-cierre:hover {
    background-color: #06adbf; /* Color de hover */
    transform: translateY(-2px); /* Movimiento ligero hacia arriba */
    box-shadow: 0 6px 8px rgba(0, 0, 0, 0.2); /* Intensificar sombra */
}

/* Efecto clic */
.btn-cierre:active {
    background-color: #024653; /* Color más oscuro al hacer clic */
    transform: translateY(1px); /* Ligeramente hacia abajo */
}
</style>

    <!-- 
<div class="dashboard">
    <div class="card">
        <button id="cierreCaja" class="btn-cierre">Cierre de Caja</button>
    </div>
</div>
 -->

<style>
    /* styles.css */

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

@media (max-width: 768px) {
    .dashboard {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}
</style>

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

// Las tablas de facturas se cargan server-side via get_facturas.php (DataTables, 10/pagina).



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

    <div class="dashboard-container">
        <header>
            <h1>Caja</h1>
        </header>
        <div class="dashboard">
            <div class="card">
                <h2>Total Facturas</h2>
                <p><?php echo $totalFacturas; ?></p>
            </div>
            <div class="card">
                <h2>Facturas Cobradas</h2>
                <p><?php echo $facturasCobradas; ?></p>
            </div>
            <div class="card">
                <h2>Facturas Pendientes</h2>
                <p><?php echo $facturasPendientes; ?></p>
            </div>
        </div>
        <!-- desactivar mover a finanzas dash 
            <div class="card">
                <h2>Ventas Esta Semana</h2>
                <p>LPS <?php echo number_format($ventasSemanales, 2); ?></p>
            </div>
            <div class="card">
                <h2>Ventas Este Mes</h2>
                <p>LPS <?php echo number_format($ventasMensuales, 2); ?></p>
            </div>
            <div class="card">
                <h2>Ventas Mes Pasado</h2>
                <p>LPS <?php echo number_format($ventasMesPasado, 2); ?></p>
            </div>
            <div class="card">
                <h2>Ventas Este Año 2025</h2>
                <p>LPS <?php echo number_format($ventasAnuales, 2); ?></p>
            </div>
            <div class="card">
                <h2>Ventas Globales</h2>
                <p>LPS <?php echo number_format($ventasGlobales, 2); ?></p>
            </div>
        
        -->

        <!-- Últimas Facturas (DataTables server-side) -->
        <br>
        <header style="text-align: center; margin-bottom: 10px;">
            <h1 style="margin: 0;">Últimas Facturas Pagadas</h1>
        </header>
        <div class="table-responsive" style="overflow-x:auto;">
            <table id="tabla_pagadas" class="responsive-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Número de Factura</th>
                        <th>Nombre Completo</th>
                        <th>Motivo Ingreso</th>
                        <th>Procesado Por</th>
                        <th>Fecha</th>
                        <th>Total a Pagar</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <br>
        <header style="text-align: center; margin-bottom: 10px;">
            <h1 style="margin: 0;">Últimas Facturas No Pagadas</h1>
        </header>
        <div class="table-responsive" style="overflow-x:auto;">
            <table id="tabla_nopagadas" class="responsive-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Número de Factura</th>
                        <th>Nombre Completo</th>
                        <th>Motivo Ingreso</th>
                        <th>Procesado Por</th>
                        <th>Fecha</th>
                        <th>Total a Pagar</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <br>
        <header style="text-align: center; margin-bottom: 10px;">
            <h1 style="margin: 0;">Facturas Abiertas por Hospitalización</h1>
        </header>
        <div class="table-responsive" style="overflow-x:auto;">
            <table id="tabla_hosp" class="responsive-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Número de Factura</th>
                        <th>Nombre Completo</th>
                        <th>Motivo Ingreso</th>
                        <th>Procesado Por</th>
                        <th>Fecha</th>
                        <th>Total a Pagar</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

<!-- Estilos para búsqueda y paginación (copiados del ejemplo) -->
<style>
    /* Estilos para la sección de filtros y búsqueda */
    .table-filters input[type="text"] {
        padding: 8px;
        width: 250px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
    /* Estilos para los botones de paginación */
    #pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
    }
    #pagination button {
        padding: 8px 15px;
        background-color: #035c67;
        color: #fff;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin: 0 5px;
        font-size: 1rem;
        text-transform: uppercase;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s ease, transform 0.2s ease;
    }
    #pagination button:hover {
        background-color: #06adbf;
        transform: scale(1.05);
    }
    #pagination button:disabled {
        background-color: #ccc;
        color: #666;
        cursor: not-allowed;
    }
    #pagination span {
        font-size: 1rem;
        font-weight: bold;
        margin: 0 10px;
        align-self: center;
        color: #035c67;
    }
</style>

<!-- Paginacion server-side via DataTables (ver init al final del archivo) -->

<!-- Dashboard Caja End -->

<!-- Dashboard Cierre Caja Start -->

<?php // Cierres de caja: se cargan server-side via get_cierres_caja.php (DataTables, 10/pagina). ?>

<div class="dashboard-container">
<!-- Historial de Cierres de Caja -->
<header style="position: relative; text-align: center; margin-bottom: 20px;">
    <h1 style="margin: 0;">Historial de Cierres de Caja</h1>
</header>

<!-- Controles de Generación de Informe -->
<div class="report-controls" style="background-color: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ddd;">
    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; justify-content: center;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="fechaDesde" style="font-weight: bold; color: #035c67;">Fecha Desde:</label>
            <input type="date" id="fechaDesde" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
        </div>
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <label for="fechaHasta" style="font-weight: bold; color: #035c67;">Fecha Hasta:</label>
            <input type="date" id="fechaHasta" style="padding: 8px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
        </div>
        
        <button onclick="generarInformeCierreCaja()" style="background-color: #06adbf; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold; transition: background-color 0.3s ease;">
            <i class="bx bx-file-blank" style="margin-right: 5px;"></i>
            Generar Informe
        </button>
    </div>
</div>

<h3 style="margin: 0 0 15px; color: #035c67;">Registros de Cierres</h3>
<div class="table-responsive" style="overflow-x:auto;">
    <table id="tabla_cierres" class="cierre-caja-table" style="width:100%;">
        <thead>
            <tr>
                <th>Fecha de Cierre</th>
                <th>Total Ventas</th>
                <th>Total Facturas</th>
                <th>Facturas Cobradas</th>
                <th>Facturas Pendientes</th>
                <th>Métodos de Pago</th>
                <th>Usuario</th>
                <th>Nombre Cajero/a</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
</div>

<style>
    .cierre-caja-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
    color: #000;
    text-align: left;
    font-size: 14px;
}

.cierre-caja-table th, .cierre-caja-table td {
    padding: 10px;
    border: 1px solid #ddd;
}

.cierre-caja-table th {
    background-color: #06adbf;
    color: white;
    font-weight: bold;
}

.cierre-caja-table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.cierre-caja-table tr:hover {
    background-color: #f1f1f1;
}
</style>

<script>
// Función para generar informe de cierre de caja
function generarInformeCierreCaja() {
    const fechaDesde = document.getElementById('fechaDesde').value;
    const fechaHasta = document.getElementById('fechaHasta').value;
    
    // Validar que se hayan seleccionado ambas fechas
    if (!fechaDesde || !fechaHasta) {
        alert('Por favor, seleccione ambas fechas para generar el informe.');
        return;
    }
    
    // Validar que la fecha desde no sea mayor que la fecha hasta
    if (fechaDesde > fechaHasta) {
        alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta".');
        return;
    }
    
    // Construir la URL del PDF (apunta al PDF específico de caja)
    const url = `../../backend/registros/cierre_caja_pdf_caja.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`;
    
    // Abrir el PDF en una nueva ventana
    window.open(url, '_blank');
}

// Establecer fechas por defecto (último mes)
document.addEventListener('DOMContentLoaded', function() {
    const hoy = new Date();
    const fechaHasta = hoy.toISOString().split('T')[0];
    
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    const fechaDesde = primerDiaMes.toISOString().split('T')[0];
    
    document.getElementById('fechaDesde').value = fechaDesde;
    document.getElementById('fechaHasta').value = fechaHasta;
});
</script>

<!-- Dashboard Cierre Caja End -->

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

    <!-- Data Tables (stack estandar MEDIDATA: server-side + exportacion) -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <!-- Inicializacion DataTables server-side (facturas + cierres de caja) -->
    <script>
    (function () {
        function esc(text) {
            if (text === null || text === undefined || text === '') { return ''; }
            return $('<div>').text(text).html();
        }
        function lps(v) {
            var n = parseFloat(v);
            if (isNaN(n)) { n = 0; }
            return 'LPS ' + n.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        var idiomaDataTable = {
            lengthMenu: 'Mostrar _MENU_ registros',
            zeroRecords: 'No se encontraron resultados',
            emptyTable: 'No hay registros disponibles.',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros totales)',
            search: 'Buscar:',
            processing: 'Cargando...',
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
        };

        var columnasFactura = [
            { data: 'invoice_number', render: function (d) { return esc(d) || '—'; } },
            { data: 'nomcl', render: function (d) { return esc(d) || '—'; } },
            { data: 'tipo', render: function (d) { return esc(d) || '—'; } },
            { data: 'processed_by', render: function (d) { return esc(d) || '—'; } },
            { data: 'placed_on', render: function (d) { return esc(d) || '—'; } },
            { data: 'total_price', className: 'dt-right', render: function (d) { return lps(d); } }
        ];

        function initFacturas(selector, estado, tipo, exportName) {
            $(selector).DataTable({
                processing: true,
                serverSide: true,
                dom: 'Bfrtip',
                scrollX: true,
                order: [[4, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ajax: {
                    url: '../../backend/php/get_facturas.php',
                    type: 'GET',
                    data: function (d) { d.estado = estado; if (tipo) { d.tipo = tipo; } }
                },
                columns: columnasFactura,
                buttons: [
                    { extend: 'copy', className: 'button' },
                    { extend: 'csv', className: 'button', title: exportName },
                    { extend: 'excel', className: 'button', title: exportName },
                    { extend: 'print', className: 'button' }
                ],
                language: idiomaDataTable
            });
        }

        $(function () {
            initFacturas('#tabla_pagadas', 'Cobrada', '', 'facturas_pagadas');
            initFacturas('#tabla_nopagadas', 'Pendiente', '', 'facturas_no_pagadas');
            initFacturas('#tabla_hosp', 'Pendiente', 'Hospitalizado', 'facturas_hospitalizacion');

            $('#tabla_cierres').DataTable({
                processing: true,
                serverSide: true,
                dom: 'Bfrtip',
                scrollX: true,
                order: [[0, 'desc']],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ajax: {
                    url: '../../backend/php/get_cierres_caja.php',
                    type: 'GET'
                },
                columns: [
                    { data: 'fecha_cierre', render: function (d) { return esc(d) || '—'; } },
                    { data: 'total_ventas', className: 'dt-right', render: function (d) { return lps(d); } },
                    { data: 'total_facturas', render: function (d) { return esc(d) || '0'; } },
                    { data: 'facturas_cobradas', render: function (d) { return esc(d) || '0'; } },
                    { data: 'facturas_pendientes', render: function (d) { return esc(d) || '0'; } },
                    {
                        data: 'total_por_metodo',
                        orderable: false,
                        render: function (d) {
                            if (!d) { return '—'; }
                            var obj;
                            try { obj = JSON.parse(d); } catch (e) { return esc(d); }
                            if (!obj || typeof obj !== 'object') { return '—'; }
                            return Object.keys(obj).map(function (k) {
                                return esc(k) + ': ' + lps(obj[k]);
                            }).join('<br>') || '—';
                        }
                    },
                    { data: 'usuario_cierre', render: function (d) { return esc(d) || '—'; } },
                    { data: 'nombre_completo', render: function (d) { return esc(d) || '—'; } }
                ],
                buttons: [
                    { extend: 'copy', className: 'button' },
                    { extend: 'csv', className: 'button', title: 'cierres_de_caja' },
                    { extend: 'excel', className: 'button', title: 'cierres_de_caja' },
                    { extend: 'print', className: 'button' }
                ],
                language: idiomaDataTable
            });
        });
    })();
    </script>


    <!-- FullCalendar -->
    <script src='../../backend/js/moment.min.js'></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.min.js'></script>
    <script src='../../backend/js/fullcalendar/fullcalendar.js'></script>
    <script src='../../backend/js/fullcalendar/locale/es.js'></script>

    <!-- SweetAlert - Cargar ANTES de perfil.php y cierre_caja.js -->
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Función Cierre de Caja -->
    <script src='../../backend/registros/script/cierre_caja.js'></script>

    <!-- Busquedas Tablas -->
    <script src="../../backend/js/search_m.js"></script>
    <script src="../../backend/js/search_p.js"></script>

    <script>

$(document).ready(function () {
  // Verificar estado del turno y mostrar alertas
  verificarTurnoYMostrarAlerta();
  
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

  moment.locale('es');

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

<!-- Script para inicializar botones de turno después de que todos los scripts estén cargados -->
<script>
// Función para verificar turno y mostrar alertas
function verificarTurnoYMostrarAlerta() {
    fetch('../../backend/registros/verificar_turno.php')
        .then(response => response.json())
        .then(data => {
            if (!data.turno_iniciado) {
                // No hay turno activo - alerta para iniciar turno
                setTimeout(() => {
                    Swal.fire({
                        title: "RECORDATORIO",
                        text: "Debe iniciar su turno antes de comenzar a trabajar.",
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonText: "Iniciar Turno",
                        cancelButtonText: "Más tarde",
                        confirmButtonColor: "#035c67",
                    }).then((result) => {
                        if (result.isConfirmed && typeof iniciarTurno === 'function') {
                            iniciarTurno();
                        }
                    });
                }, 1000); // Esperar 1 segundo después de cargar la página
            } else {
                // Hay turno activo - recordatorio de cierre de caja
                setTimeout(() => {
                    Swal.fire({
                        title: "RECORDATORIO",
                        text: "Tiene un turno activo. Recuerde realizar el cierre de caja al finalizar su jornada.",
                        icon: "info",
                        showCancelButton: true,
                        confirmButtonText: "Cerrar Caja",
                        cancelButtonText: "Entendido",
                        confirmButtonColor: "#035c67",
                    }).then((result) => {
                        if (result.isConfirmed && typeof cierreCaja === 'function') {
                            cierreCaja();
                        }
                    });
                }, 1000); // Esperar 1 segundo después de cargar la página
            }
        })
        .catch(error => {
            console.error('Error al verificar turno:', error);
        });
}

// Forzar ejecución de verificarEstadoTurno después de que todos los scripts estén cargados
(function() {
    function inicializarBotonesTurno() {
        if (typeof verificarEstadoTurno === 'function') {
            const btnIniciar = document.getElementById('iniciarTurno');
            const btnCierre = document.getElementById('cierreCaja');
            
            if (btnIniciar && btnCierre) {
                console.log('Inicializando botones de turno desde escritorio.php');
                verificarEstadoTurno();
            } else {
                console.warn('Botones de turno no encontrados, reintentando...');
                setTimeout(inicializarBotonesTurno, 300);
            }
        } else {
            console.warn('verificarEstadoTurno no está definida aún, reintentando...');
            setTimeout(inicializarBotonesTurno, 300);
        }
    }
    
    // Ejecutar después de que todo esté cargado
    if (document.readyState === 'complete') {
        setTimeout(inicializarBotonesTurno, 500);
    } else {
        window.addEventListener('load', function() {
            setTimeout(inicializarBotonesTurno, 500);
        });
    }
    
    // Fallback adicional
    setTimeout(inicializarBotonesTurno, 1000);
})();
</script>

</body>
</html>

