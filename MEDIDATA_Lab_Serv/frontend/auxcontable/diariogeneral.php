<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
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
include_once '../admin/menu.php';
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
include_once '../admin/perfil.php';
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

<button class="button" onclick="cambiarColor(this, 'catalogo.php')">Catálogo de Cuentas</button>
<button class="button" onclick="cambiarColor(this, 'diariogeneral.php')">Diario General</button>
<button class="button" onclick="cambiarColor(this, 'partida_manual.php')">Partida Manual</button>
<button class="button" onclick="cambiarColor(this, 'transacciones.php')">Transacciones Capturadas</button>
<button class="button" onclick="cambiarColor(this, '../contabilidad/cuentas_por_pagar.php')">Cuentas por Pagar</button>

<br>
            
<div class="catalog-container">
    <h2 class="catalog-title">Diario General</h2>
    
    <!-- Filtros -->
    <div id="diario-filtros" class="filters-container">
        <div class="filter-group">
            <label for="fechaDesde" title="Filtra por fecha de ocurrencia">Desde:</label>
            <input type="date" id="fechaDesde" class="filter-input" title="Fecha de ocurrencia">
        </div>
        <div class="filter-group">
            <label for="fechaHasta" title="Filtra por fecha de ocurrencia">Hasta:</label>
            <input type="date" id="fechaHasta" class="filter-input" title="Fecha de ocurrencia">
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
                <option value="PAGO_PROVEEDOR">Pago a proveedor</option>
                <option value="PAGO_HONORARIO_MEDICO">Pago a honorario médico</option>
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

    /* Evita el "pantallazo blanco" de DataTables al procesar */
    #tablaDiarioGeneral_wrapper {
        position: relative;
    }

    #tablaDiarioGeneral_wrapper .dataTables_processing {
        /* Mantener centrado (como admin.css) pero SIN capa blanca */
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        min-height: 100vh !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
        background: transparent !important;
        pointer-events: none !important;
        z-index: 9998 !important;
    }

    #tablaDiarioGeneral_wrapper .dt-medidata-processing {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
        padding: 0;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
        color: #035c67;
    }

    #tablaDiarioGeneral_wrapper .dt-medidata-processing p {
        margin: 0;
        color: #fff;
        font-weight: 600;
    }
</style>

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
            { data: 'usuario' }
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
                    .append('<td colspan="2" class="' + balanceClass + '"><strong>' + balanceIcon + ' ' + balanceText + '</strong></td>');
            }
        },
        order: [[1, 'desc']], // Ordenar por fecha de ocurrencia descendente
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "Todos"]],
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
</script>
<script src="../../backend/registros/script/diario_general_export.js?v=20260530e"></script>

</body>
</html>


