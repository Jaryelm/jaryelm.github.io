<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">





    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../contabilidad/menu.php';
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
include_once '../contabilidad/perfil.php';
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

<button class="button" onclick="cambiarColor(this, 'catalogo_user.php')">Catálogo de Cuentas</button>
<button class="button" onclick="cambiarColor(this, 'diariogeneral_user.php')">Diario General</button>
<button class="button" onclick="cambiarColor(this, 'partida_manual_user.php')">Partida Manual</button>
<button class="button" onclick="cambiarColor(this, 'transacciones_user.php')">Transacciones Capturadas</button>

<br>
            
<div class="catalog-container">
    <h2 class="catalog-title">Diario General</h2>
    
    <!-- Filtros -->
    <div id="diario-filtros" class="filters-container">
        <div class="filter-group">
            <label for="fechaDesde">Desde:</label>
            <input type="date" id="fechaDesde" class="filter-input">
        </div>
        <div class="filter-group">
            <label for="fechaHasta">Hasta:</label>
            <input type="date" id="fechaHasta" class="filter-input">
        </div>
        <div class="filter-group">
            <label for="numeroPartida">Partida #:</label>
            <input type="text" id="numeroPartida" class="filter-input" placeholder="Ej: 20231201001">
        </div>
        <div class="filter-group">
            <label for="cuenta">Cuenta:</label>
            <input type="text" id="cuenta" class="filter-input" placeholder="Código de cuenta">
        </div>
        <div class="filter-group">
            <label for="filtroTipoTransaccion">Tipo de partida:</label>
            <select id="filtroTipoTransaccion" class="select2">
                <option value="">Todos</option>
                <option value="COMPRA_PROVEEDOR">Registro de compra</option>
                <option value="PARTIDA_MANUAL">Partida manual</option>
                <option value="CIERRE_VENTA">Cierre de venta</option>
                <option value="REVERSION_ANULACION">Reversión / anulación</option>
            </select>
        </div>
        <button class="btn-filter" onclick="aplicarFiltros()">Buscar</button>
        <button class="btn-filter btn-reset" onclick="limpiarFiltros()">Limpiar</button>
    </div>

    <div class="export-buttons" style="margin-bottom:15px;display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="dt-button buttons-copy" onclick="exportarDiario('copy')">Copiar</button>
        <button type="button" class="dt-button buttons-csv" onclick="exportarDiario('csv')">CSV</button>
        <button type="button" class="dt-button buttons-excel" onclick="exportarDiario('excel')">Excel</button>
        <button type="button" class="dt-button buttons-excel" onclick="exportarDiario('print')">Imprimir</button>
    </div>

    <div class="table-container">
        <table id="tablaDiarioGeneral" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Partida #</th>
                    <th>Fecha de Ocurrencia</th>
                    <th>Fecha de Registro</th>
                    <th>Referencia</th>
                    <th>Tipo</th>
                    <th>Unidad de Servicio</th>
                    <th>Cuenta</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Debe</th>
                    <th>Haber</th>
                    <th>Neto</th>
                    <th>Turno</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<style>
    .catalog-container {
        margin: 20px auto;
        max-width: 98%;
        text-align: center;
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .catalog-title {
        color: #06adbf;
        margin-bottom: 20px;
        font-size: 24px;
        font-weight: bold;
    }

    .table-container {
        margin: 0 auto;
        overflow-x: auto;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        background-color: white;
        border-radius: 8px;
        padding: 10px;
    }

    #tablaDiarioGeneral {
        width: 100% !important;
    }

    #tablaDiarioGeneral thead th {
        background-color: #035c67;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: bold;
    }

    #tablaDiarioGeneral tbody td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }

    #tablaDiarioGeneral tbody tr:nth-child(odd) {
        background-color: #f2f2f2;
    }

    #tablaDiarioGeneral tbody tr:hover {
        background-color: #e0f7fa;
    }

    .text-right {
        text-align: right;
        white-space: nowrap; /* Evitar que "L." y el número se separen */
    }
    
    .partida-group-header {
        background-color: #e3f2fd !important;
        font-weight: bold;
        border-top: 2px solid #035c67;
        border-bottom: 2px solid #035c67;
    }
    
    .partida-group-header td {
        padding: 12px !important;
        background-color: #e3f2fd !important;
    }
    
    .balance-ok {
        color: #28a745;
    }
    
    .balance-error {
        color: #dc3545;
    }
    
    #tablaDiarioGeneral tbody tr.partida-group-header:hover {
        background-color: #e3f2fd !important;
    }

    .dt-acciones {
        white-space: nowrap;
        text-align: center;
    }

    .btn_ver_detalles {
        background-color: #06adbf;
        border: none;
        color: #fff;
        padding: 6px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
    }

    .btn_ver_detalles:hover {
        background-color: #049aad;
    }

    /* Modal detalle venta: mismos criterios que frontend/almacen/venta.php (evitar class "display" = conflict DataTables) */
    #diarioDetailsModal.modal {
        display: none;
        position: fixed;
        z-index: 12000;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        justify-content: center;
        align-items: center;
        flex-direction: row;
    }

    #diarioDetailsModal .modal-content {
        background-color: #fefefe;
        border: 1px solid #888;
        padding: 20px;
        width: 80%;
        max-width: 1400px;
        max-height: 90%;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }

    #diarioDetailsModal .modal-content > .close-btn {
        float: right;
    }

    #diarioDetailsModal .modal-content h2 {
        margin: 0 0 10px 0;
        padding-right: 36px;
        font-size: 1.25rem;
        color: #111;
        font-weight: bold;
    }

    /* Sobrescribe el .table-container del listado principal solo dentro del modal */
    #diarioDetailsModal .modal-content .table-container {
        max-height: 60vh;
        overflow-y: auto;
        overflow-x: auto;
        margin-top: 10px;
        flex-grow: 1;
        box-shadow: none;
        border-radius: 0;
        padding: 0;
        background: transparent;
        margin-left: 0;
        margin-right: 0;
    }

    #diarioDetailsModal .responsive-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        margin-bottom: 0;
    }

    #diarioDetailsModal .responsive-table thead {
        position: static;
        clip: auto;
        height: auto;
        width: auto;
        overflow: visible;
        padding: 0;
        border: 0;
    }

    #diarioDetailsModal .responsive-table thead tr,
    #diarioDetailsModal .responsive-table tbody tr {
        display: table-row;
        margin: 0;
        border: none;
    }

    /* venta.php (final): .responsive-table th, .responsive-table td */
    #diarioDetailsModal .responsive-table th,
    #diarioDetailsModal .responsive-table td {
        display: table-cell;
        padding: 10px;
        border: 1px solid #ddd;
        white-space: normal;
    }

    /* admin.css: .responsive-table thead th */
    #diarioDetailsModal .responsive-table thead th {
        background-color: #1e2c4b;
        border: 1px solid #1e2c4b;
        color: #fff;
        font-weight: normal;
        text-align: center;
    }

    #diarioDetailsModal .responsive-table thead th:first-of-type {
        text-align: left;
    }

    /* admin.css (min-width 52em): filas pares */
    #diarioDetailsModal .responsive-table tbody tr:nth-of-type(even) {
        background-color: rgba(94, 93, 82, 0.1);
    }

    /* admin.css (min-width 52em): tbody td */
    #diarioDetailsModal .responsive-table tbody td {
        text-align: center;
    }

    #diarioDetailsModal .close-btn {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }

    #diarioDetailsModal .close-btn:hover,
    #diarioDetailsModal .close-btn:focus {
        color: #000;
    }

    #loadingDiarioDetalle {
        display: none;
        position: fixed;
        z-index: 13000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.35);
        align-items: center;
        justify-content: center;
        flex-direction: row;
    }

    #loadingDiarioDetalle .box {
        background: #fff;
        padding: 24px 32px;
        border-radius: 8px;
        font-weight: 600;
        color: #035c67;
    }
</style>

<div id="loadingDiarioDetalle" style="display:none;"><div class="box">Cargando detalles…</div></div>

<div id="diarioDetailsModal" class="modal" style="display: none !important;">
    <div class="modal-content">
        <span class="close-btn" onclick="diarioCerrarModalVenta()" title="Cerrar">&times;</span>
        <h2>Detalles de Productos y Servicios</h2>
        <div class="table-container">
            <table id="diarioDetailsVentaTable" class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Impuesto</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Total</th>
                        <th scope="col">Descuento General</th>
                        <th scope="col">Descuento 3ra Edad</th>
                        <th scope="col">Descuento 4ta Edad</th>
                        <th scope="col">Promoción</th>
                        <th scope="col">Otros Descuentos</th>
                        <th scope="col">Descuentos Aplicados</th>
                        <th scope="col">Total a Pagar Sin I.S.V</th>
                    </tr>
                </thead>
                <tbody id="diarioDetailsVentaBody"></tbody>
            </table>
        </div>
    </div>
</div>

        </main>
        <!-- MAIN -->
    </section>
    
    <!-- NAVBAR -->
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

<script>
let tablaDiarioGeneral;

$(function () {
    var $dp = $('#content').length ? $('#content') : $(document.body);
    var $fdTipo = $('#filtroTipoTransaccion');
    if ($fdTipo.length && typeof $.fn.select2 === 'function') {
        $fdTipo.select2({
            width: '220px',
            dropdownParent: $dp,
            minimumResultsForSearch: 0
        });
        var qTipo = new URLSearchParams(window.location.search).get('tipo');
        if (qTipo) {
            $fdTipo.val(qTipo).trigger('change');
        }
    }
    tablaDiarioGeneral = $('#tablaDiarioGeneral').DataTable({
        processing: true,
        serverSide: true,
        dom: 'frtip',
        ajax: {
            url: 'get_diariogeneral_transacciones.php',
            type: 'GET',
            data: function(d) {
                d.fechaDesde = $('#fechaDesde').val();
                d.fechaHasta = $('#fechaHasta').val();
                d.numeroPartida = $('#numeroPartida').val();
                d.cuenta = $('#cuenta').val();
                d.tipoTransaccion = $('#filtroTipoTransaccion').val();
            }
        },
        columns: [
            { data: 'numero_partida' },
            { data: 'fecha_ocurrencia' },
            { data: 'fecha_registro' },
            { data: 'referencia' },
            { data: 'tipo_etiqueta' },
            { data: 'unidad_servicio' },
            { data: 'cuenta' },
            { data: 'nombre_cuenta' },
            { data: 'descripcion' },
            { 
                data: 'debe',
                className: 'text-right',
                render: function(data, type, row) {
                    if (type === 'display') {
                        var valor = parseFloat(String(data).replace(/,/g, '')) || 0;
                        if (valor > 0) {
                            return 'L. ' + valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        }
                        return 'L. 0.00';
                    }
                    return data || '0.00';
                }
            },
            { 
                data: 'haber',
                className: 'text-right',
                render: function(data, type, row) {
                    if (type === 'display') {
                        var valor = parseFloat(String(data).replace(/,/g, '')) || 0;
                        if (valor > 0) {
                            return 'L. ' + valor.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                        }
                        return 'L. 0.00';
                    }
                    return data || '0.00';
                }
            },
            { 
                data: 'neto',
                className: 'text-right',
                render: function(data, type, row) {
                    if (type === 'display') {
                        var valor = parseFloat(String(data).replace(/,/g, '')) || 0;
                        var signo = valor >= 0 ? '' : '-';
                        return signo + 'L. ' + Math.abs(valor).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                    }
                    return data || '0.00';
                }
            },
            { data: 'turno' },
            { data: 'usuario' },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'dt-acciones',
                render: function(data, type, row) {
                    if (type !== 'display' && type !== 'filter') {
                        return '';
                    }
                    var modo = row.detalle_modo || '';
                    var id = row.detalle_id;
                    if (!modo || id == null || id === '') {
                        return '—';
                    }
                    return '<button type="button" class="btn_ver_detalles" data-modo="' + modo + '" data-id="' + id + '">Ver detalles</button>';
                }
            }
        ],
        rowGroup: {
            dataSrc: 'numero_partida',
            startRender: function(rows, group) {
                var allData = rows.data().toArray();
                var first = allData[0] || {};
                var referencia = '';
                allData.forEach(function(row) {
                    if (!referencia && row.referencia) {
                        referencia = row.referencia;
                    }
                });
                // Totales de la partida completa (servidor). Con serverSide + paginación, rows solo
                // incluía renglones de la página actual → falsos DESBALANCEADA.
                var totalDebe, totalHaber;
                if (first.partida_total_debe !== undefined && first.partida_total_debe !== null && String(first.partida_total_debe) !== '') {
                    totalDebe = parseFloat(String(first.partida_total_debe).replace(/,/g, '')) || 0;
                    totalHaber = parseFloat(String(first.partida_total_haber).replace(/,/g, '')) || 0;
                } else {
                    totalDebe = 0;
                    totalHaber = 0;
                    allData.forEach(function(row) {
                        totalDebe += parseFloat(String(row.debe || '0').replace(/,/g, '')) || 0;
                        totalHaber += parseFloat(String(row.haber || '0').replace(/,/g, '')) || 0;
                    });
                }

                var diferencia = totalDebe - totalHaber;
                // Redondear a 2 decimales para evitar errores de punto flotante (ej: 0.01 centavo)
                var diffCentavos = Math.round(diferencia * 100) / 100;
                var balanceado = diffCentavos === 0;
                var balanceClass = balanceado ? 'balance-ok' : 'balance-error';
                var balanceIcon = balanceado ? '✓' : '✗';
                var balanceText = balanceado ? 'BALANCEADA' : 'DESBALANCEADA';
                
                var referenciaText = referencia ? ' | Ref: ' + referencia : '';
                
                return $('<tr class="partida-group-header">')
                    .append('<td colspan="5"><strong>Partida: ' + group + referenciaText + '</strong></td>')
                    .append('<td colspan="4" class="text-right"><strong>Totales:</strong></td>')
                    .append('<td class="text-right"><strong>L. ' + totalDebe.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong></td>')
                    .append('<td class="text-right"><strong>L. ' + totalHaber.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong></td>')
                    .append('<td class="text-right"><strong>L. ' + diferencia.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '</strong></td>')
                    .append('<td colspan="3" class="' + balanceClass + '"><strong>' + balanceIcon + ' ' + balanceText + '</strong></td>');
            }
        },
        order: [[0, 'desc'], [1, 'desc']], // Ordenar por número de partida y fecha de ocurrencia descendente
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, 100, 200, 250], [5, 10, 25, 50, 100, 200, 250]],
        language: {
            processing: '<div class="dt-medidata-processing"><div class="dt-medidata-spinner" aria-hidden="true"></div><p>Cargando...</p></div>',
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron registros",
            emptyTable: "No hay datos disponibles en la tabla",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            paginate: {
                first: "Primero",
                previous: "Anterior",
                next: "Siguiente",
                last: "Último"
            }
        },
        scrollX: true,
        responsive: true,
        drawCallback: function(settings) {
            // Resaltar filas de agrupación
            $('.partida-group-header').css('font-weight', 'bold');
        }
    });

    $('#tablaDiarioGeneral').on('click', '.btn_ver_detalles', function() {
        var modo = $(this).data('modo');
        var id = parseInt($(this).data('id'), 10);
        if (!modo || !id) {
            return;
        }
        if (modo === 'compra') {
            diarioVerDetalleCompra(id);
        } else if (modo === 'venta') {
            diarioVerDetalleVenta(id);
        }
    });
});

function diarioShowLoadingDetalle() {
    var el = document.getElementById('loadingDiarioDetalle');
    if (el) {
        el.style.display = 'flex';
    }
}

function diarioHideLoadingDetalle() {
    var el = document.getElementById('loadingDiarioDetalle');
    if (el) {
        el.style.display = 'none';
    }
}

function diarioCerrarModalVenta() {
    var modal = document.getElementById('diarioDetailsModal');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
    }
}

function diarioVerDetalleVenta(orderId) {
    diarioShowLoadingDetalle();
    fetch('../../backend/registros/obtener_detalles_checkout.php?order_id=' + encodeURIComponent(orderId))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            diarioHideLoadingDetalle();
            var body = document.getElementById('diarioDetailsVentaBody');
            if (!body) {
                return;
            }
            body.innerHTML = '';
            var formatCurrency = function(value) {
                var numValue = parseFloat(value) || 0;
                return 'LPS. ' + numValue.toLocaleString('es-HN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            };
            if (!Array.isArray(data) || data.length === 0) {
                swal('Sin datos', 'No hay líneas de productos/servicios para esta orden.', 'info');
                return;
            }
            data.forEach(function(item) {
                var tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + (item.codigo || 'N/A') + '</td>' +
                    '<td>' + (item.impuesto !== null && item.impuesto !== '' ? item.impuesto : 'N/A') + '</td>' +
                    '<td>' + (item.nombre || 'N/A') + '</td>' +
                    '<td>' + (item.cantidad || 0) + '</td>' +
                    '<td>' + formatCurrency(item.total_original) + '</td>' +
                    '<td>' + formatCurrency(item.discount_percentage) + '</td>' +
                    '<td>' + formatCurrency(item.age_discount_30) + '</td>' +
                    '<td>' + formatCurrency(item.age_discount_40) + '</td>' +
                    '<td>' + formatCurrency(item.promotion_discount) + '</td>' +
                    '<td>' + formatCurrency(item.other_discount) + '</td>' +
                    '<td>' + formatCurrency(item.total_discount) + '</td>' +
                    '<td>' + formatCurrency(item.total_after_discount) + '</td>';
                body.appendChild(tr);
            });
            var modal = document.getElementById('diarioDetailsModal');
            if (modal) {
                modal.style.setProperty('display', 'flex', 'important');
            }
        })
        .catch(function(err) {
            diarioHideLoadingDetalle();
            console.error(err);
            swal('Error', 'No se pudieron cargar los detalles de la venta.', 'error');
        });
}

function diarioVerDetalleCompra(idCompra) {
    diarioShowLoadingDetalle();
    $.ajax({
        url: '../../backend/registros/obtener_detalle_compras.php',
        type: 'POST',
        data: { id_compra: idCompra },
        success: function(data) {
            diarioHideLoadingDetalle();
            try {
                var detalles = typeof data === 'string' ? JSON.parse(data) : data;
                if (detalles && detalles.error) {
                    swal('Error', String(detalles.error), 'error');
                    return;
                }
                if (!Array.isArray(detalles)) {
                    swal('Error', 'Respuesta inválida del servidor.', 'error');
                    return;
                }
                if (detalles.length > 0) {
                    var detallesHTML = '<div style="overflow-x:auto;max-width:1200px;">' +
                        '<h3 style="margin-top:0;">ID compra: ' + idCompra + '</h3>' +
                        '<table style="width:100%;border-collapse:collapse;"><thead><tr>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Cuenta</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Código Producto</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Descripción</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Cantidad</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Unidad</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Precio Unitario</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">ISV</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Subtotal</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Descuento %</th>' +
                        '<th style="border:1px solid #e0e0e0;padding:8px;background-color:#06adbf;color:#fff;">Total</th>' +
                        '</tr></thead><tbody>';
                    detalles.forEach(function(item) {
                        detallesHTML += '<tr>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.cat_cuenta || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.codigo_producto || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.descripcion || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.cantidad || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.unidad || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + parseFloat(item.precio_unitario || 0).toFixed(2) + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + parseFloat(item.isv || 0).toFixed(2) + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.subtotal || '') + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + parseFloat(item.descuento_porcentaje || 0).toFixed(2) + '</td>' +
                            '<td style="border:1px solid #e0e0e0;padding:8px;">' + (item.total_item || '') + '</td>' +
                            '</tr>';
                    });
                    detallesHTML += '</tbody></table></div>';
                    swal({
                        title: 'Detalles de la Compra',
                        content: $(detallesHTML)[0],
                        buttons: {
                            confirm: {
                                text: 'Cerrar',
                                value: true,
                                visible: true,
                                className: 'btn_ver_detalles',
                                closeModal: true
                            }
                        }
                    });
                    $('.swal-modal').css({
                        width: '80%',
                        maxWidth: '1200px',
                        overflowX: 'auto'
                    });
                } else {
                    swal('No se encontraron detalles', 'No hay detalles disponibles para esta compra.', 'info');
                }
            } catch (e) {
                console.error(e);
                swal('Error', 'Hubo un problema al cargar los detalles.', 'error');
            }
        },
        error: function() {
            diarioHideLoadingDetalle();
            swal('Error', 'No se pudo cargar los detalles de la compra.', 'error');
        }
    });
}

document.addEventListener('click', function(ev) {
    if (ev.target && ev.target.id === 'diarioDetailsModal') {
        diarioCerrarModalVenta();
    }
});

function aplicarFiltros() {
    tablaDiarioGeneral.ajax.reload();
}

function limpiarFiltros() {
    $('#fechaDesde').val('');
    $('#fechaHasta').val('');
    $('#numeroPartida').val('');
    $('#cuenta').val('');
    $('#filtroTipoTransaccion').val('').trigger('change');
    tablaDiarioGeneral.ajax.reload();
}

function exportarDiario(format) {
    var params = new URLSearchParams();
    params.set('format', format);
    var fechaDesde = $('#fechaDesde').val();
    var fechaHasta = $('#fechaHasta').val();
    var numeroPartida = $('#numeroPartida').val();
    var cuenta = $('#cuenta').val();
    if (fechaDesde) params.set('fechaDesde', fechaDesde);
    if (fechaHasta) params.set('fechaHasta', fechaHasta);
    if (numeroPartida) params.set('numeroPartida', numeroPartida);
    if (cuenta) params.set('cuenta', cuenta);
    var tipoTx = $('#filtroTipoTransaccion').val();
    if (tipoTx) params.set('tipoTransaccion', tipoTx);
    var url = 'get_diariogeneral_export.php?' + params.toString();
    if (format === 'print') {
        window.open(url, '_blank', 'width=1200,height=800');
    } else if (format === 'copy') {
        fetch(url).then(function(r) { return r.text(); }).then(function(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Datos copiados al portapapeles (' + text.split('\n').length + ' filas)');
            }).catch(function() { alert('No se pudo copiar. Use CSV para descargar.'); });
        }).catch(function() { alert('Error al obtener los datos.'); });
    } else if (format === 'csv' || format === 'excel') {
        var ext = 'csv';
        var filename = 'diario_general_' + new Date().toISOString().slice(0,10) + '.' + ext;
        fetch(url).then(function(r) { return r.blob(); }).then(function(blob) {
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(a.href);
        }).catch(function() { alert('Error al descargar.'); });
    } else {
        window.location.href = url;
    }
}
</script>

</body>
</html>


