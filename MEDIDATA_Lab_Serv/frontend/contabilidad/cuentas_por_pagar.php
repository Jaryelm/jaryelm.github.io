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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA - Cuentas por Pagar</title>
</head>
<body>
    
<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar' ></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>

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

        <button class="button" onclick="cambiarColor(this, 'catalogo.php')">Catálogo de Cuentas</button>
        <button class="button" onclick="cambiarColor(this, 'diariogeneral.php')">Diario General</button>
        <button class="button" onclick="cambiarColor(this, 'partida_manual.php')">Partida Manual</button>
        <button class="button" onclick="cambiarColor(this, 'cuentas_por_pagar.php')">Cuentas por Pagar</button>

        <br>
                    
        <div class="catalog-container">
            <h2 class="catalog-title">Cuentas por Pagar (Proveedores)</h2>
            
            <!-- Filtros -->
            <div id="diario-filtros" class="filters-container">
                <div class="filter-group">
                    <label for="tipoProveedor">Tipo de Proveedor:</label>
                    <select id="tipoProveedor" class="select2">
                        <option value="comercial" selected>Comerciales</option>
                        <option value="medico">Médicos (Honorarios)</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="fechaDesde" title="Filtra por fecha de inicio">Desde:</label>
                    <input type="date" id="fechaDesde" class="filter-input" value="2020-01-01">
                </div>
                <div class="filter-group">
                    <label for="fechaHasta" title="Filtra por fecha final">Hasta:</label>
                    <input type="date" id="fechaHasta" class="filter-input" value="<?php echo date('Y-m-t'); ?>">
                </div>
                <button class="btn-filter" onclick="aplicarFiltros()">Buscar</button>
            </div>

            <div class="export-buttons" style="margin-bottom:15px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="button" class="dt-button buttons-copy" onclick="exportarTabla('copy')">Copiar</button>
                <button type="button" class="dt-button buttons-csv" onclick="exportarTabla('csv')">CSV</button>
                <button type="button" class="dt-button buttons-excel" onclick="exportarTabla('excel')">Excel</button>
                <button type="button" class="dt-button buttons-excel" onclick="exportarTabla('print')">Imprimir</button>
            </div>

            <!-- Tabla Comerciales -->
            <div id="containerComerciales" class="table-container">
                <table id="tablaComerciales" class="responsive-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Total Saldado</th>
                            <th>Total Facturado</th>
                            <th>Saldo Neto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                            <th style="display:none;">RawSaldado</th>
                            <th style="display:none;">RawDebe</th>
                            <th style="display:none;">RawNeto</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th style="text-align:right">Totales Globales:</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th style="display:none;"></th>
                            <th style="display:none;"></th>
                            <th style="display:none;"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Tabla Medicos -->
            <div id="containerMedicos" class="table-container" style="display: none;">
                <table id="tablaMedicos" class="responsive-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Total Saldado</th>
                            <th>Total Facturado</th>
                            <th>Saldo Neto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                            <th style="display:none;">RawSaldado</th>
                            <th style="display:none;">RawDebe</th>
                            <th style="display:none;">RawNeto</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr>
                            <th style="text-align:right">Totales Globales:</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th style="display:none;"></th>
                            <th style="display:none;"></th>
                            <th style="display:none;"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Modal de Facturas por Proveedor -->
        <div id="facturasModal" class="modal" style="display: none;">
            <div class="modal-content" style="max-width: 90%; width: 1000px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 id="modalFacturasTitle" style="margin: 0;">Facturas</h2>
                    <span class="close-btn" onclick="cerrarModalFacturas()" title="Cerrar">&times;</span>
                </div>
                <div class="export-buttons" style="margin-bottom:15px;display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="button buttons-copy" onclick="exportarModal('copy')">Copiar</button>
                    <button type="button" class="button buttons-csv" onclick="exportarModal('csv')">CSV</button>
                    <button type="button" class="button buttons-excel" onclick="exportarModal('excel')">Excel</button>
                    <button type="button" class="button buttons-excel" onclick="exportarModal('print')">Imprimir</button>
                </div>
                <div class="table-container">
                    <table id="tablaFacturasDetalle" class="responsive-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>No. Factura</th>
                                <th id="thDetalleCol3">Vencimiento / Paciente</th>
                                <th>Valor</th>
                                <th>Saldado</th>
                                <th>Saldo Neto</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de Historial de Pagos (Partidas) -->
        <div id="partidasModal" class="modal" style="display: none; z-index: 1000;">
            <div class="modal-content" style="max-width: 90%; width: 800px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h2 id="modalPartidasTitle" style="margin: 0;">Historial de Pagos</h2>
                    <span class="close-btn" onclick="cerrarModalPartidas()" title="Cerrar">&times;</span>
                </div>
                <div class="table-container">
                    <table id="tablaPartidasDetalle" class="responsive-table" style="width:100%">
                        <thead id="theadPartidas">
                            <!-- Injected dynamically -->
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
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
        
            table.display {
                width: 100% !important;
            }
        
            table.display thead th {
                background-color: #035c67 !important;
                color: white !important;
                padding: 12px !important;
                text-align: left !important;
                font-weight: bold !important;
            }
        
            table.display tbody td {
                padding: 10px;
                border-bottom: 1px solid #ddd;
                vertical-align: middle !important;
            }
        
            table.display tbody tr:nth-child(odd) {
                background-color: #f2f2f2;
            }
        
            table.display tbody tr:hover {
                background-color: #e0f7fa;
            }
            
            .dt-buttons {
                display: none !important;
            }

            .text-right {
                text-align: right;
                white-space: nowrap;
            }
            
            .partida-group-header {
                background-color: #e3f2fd !important;
                font-weight: bold;
                border: 0 !important;
                box-shadow: none !important;
            }
            
            .partida-group-header td {
                padding: 12px !important;
                background-color: #e3f2fd !important;
                border-top: 0 !important;
                border-bottom: 0 !important;
            }
            
            table.display tbody tr.partida-group-header:hover {
                background-color: #e3f2fd !important;
            }
            
            .balance-ok {
                color: #28a745;
            }
            
            .balance-error {
                color: #dc3545;
            }
        </style>

        </main>
    </section>
    
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
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    
    <script src="../../backend/js/script.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <script>
    let dtComerciales, dtMedicos;

    $(document).ready(function () {
        var $dp = $('#content').length ? $('#content') : $(document.body);
        $('#tipoProveedor').select2({ minimumResultsForSearch: Infinity, dropdownParent: $dp, width: '220px' });

        dtComerciales = $('#tablaComerciales').DataTable({
            serverSide: false,
            processing: true,
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'print'
            ],
            columnDefs: [
                { targets: 1, className: 'text-right' },
                { targets: 2, className: 'text-right' },
                { targets: 3, className: 'text-right' },
                { targets: 6, visible: false },
                { targets: 7, visible: false },
                { targets: 8, visible: false }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var intVal = function (i) {
                    return typeof i === 'string' ? i.replace(/[\L,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                };

                var totalSaldado = api.column(6).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                var totalDebe = api.column(7).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                var totalNeto = api.column(8).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var fmt = function(n) { return 'L. ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

                $(api.column(1).footer()).html(fmt(totalSaldado));
                $(api.column(2).footer()).html(fmt(totalDebe));
                $(api.column(3).footer()).html(fmt(totalNeto));
                
                var balanceado = Math.round(totalNeto * 100) / 100 <= 0;
                $(api.column(4).footer()).html('<span class="' + (balanceado ? 'balance-ok' : 'balance-error') + '">' + (balanceado ? '✓ BALANCEADO' : '✗ DESBALANCEADO') + '</span>');
            },
            ajax: {
                url: '../../backend/registros/get_cuentas_por_pagar.php',
                type: 'POST',
                data: function (d) {
                    d.tipo = 'comercial';
                    d.fechaDesde = $('#fechaDesde').val();
                    d.fechaHasta = $('#fechaHasta').val();
                }
            },
            language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" }
        });

        dtMedicos = $('#tablaMedicos').DataTable({
            serverSide: false,
            processing: true,
            dom: 'Bfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'print'
            ],
            columnDefs: [
                { targets: 1, className: 'text-right' },
                { targets: 2, className: 'text-right' },
                { targets: 3, className: 'text-right' },
                { targets: 6, visible: false },
                { targets: 7, visible: false },
                { targets: 8, visible: false }
            ],
            footerCallback: function (row, data, start, end, display) {
                var api = this.api();
                var intVal = function (i) {
                    return typeof i === 'string' ? i.replace(/[\L,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                };

                var totalSaldado = api.column(6).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                var totalDebe = api.column(7).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);
                var totalNeto = api.column(8).data().reduce(function (a, b) { return intVal(a) + intVal(b); }, 0);

                var fmt = function(n) { return 'L. ' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','); };

                $(api.column(1).footer()).html(fmt(totalSaldado));
                $(api.column(2).footer()).html(fmt(totalDebe));
                $(api.column(3).footer()).html(fmt(totalNeto));
                
                var balanceado = Math.round(totalNeto * 100) / 100 <= 0;
                $(api.column(4).footer()).html('<span class="' + (balanceado ? 'balance-ok' : 'balance-error') + '">' + (balanceado ? '✓ BALANCEADO' : '✗ DESBALANCEADO') + '</span>');
            },
            ajax: {
                url: '../../backend/registros/get_cuentas_por_pagar.php',
                type: 'POST',
                data: function (d) {
                    d.tipo = 'medico';
                    d.fechaDesde = $('#fechaDesde').val();
                    d.fechaHasta = $('#fechaHasta').val();
                }
            },
            language: { url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json" }
        });



        $('#tipoProveedor').on('change', function() {
            if ($(this).val() === 'comercial') {
                $('#containerMedicos').hide();
                $('#containerComerciales').fadeIn();
                dtComerciales.ajax.reload();
            } else {
                $('#containerComerciales').hide();
                $('#containerMedicos').fadeIn();
                dtMedicos.ajax.reload();
            }
        });
    });

    function aplicarFiltros() {
        if ($('#tipoProveedor').val() === 'comercial') {
            dtComerciales.ajax.reload();
        } else {
            dtMedicos.ajax.reload();
        }
    }

    function exportarTabla(tipo) {
        let tablaActiva = ($('#tipoProveedor').val() === 'comercial') ? '#tablaComerciales' : '#tablaMedicos';
        let botonClase = '';
        
        switch(tipo) {
            case 'copy': botonClase = '.buttons-copy'; break;
            case 'csv': botonClase = '.buttons-csv'; break;
            case 'excel': botonClase = '.buttons-excel'; break;
            case 'print': botonClase = '.buttons-print'; break;
        }
        
        $(tablaActiva + '_wrapper ' + botonClase).trigger('click');
    }
    
    function exportarModal(tipo) {
        let botonClase = '';
        switch(tipo) {
            case 'copy': botonClase = '.buttons-copy'; break;
            case 'csv': botonClase = '.buttons-csv'; break;
            case 'excel': botonClase = '.buttons-excel'; break;
            case 'print': botonClase = '.buttons-print'; break;
        }
        $('#tablaFacturasDetalle_wrapper ' + botonClase).trigger('click');
    }
            
            let dtFacturasDetalle = null;

            $(document).on('click', '#tablaComerciales .btn_ver_detalles, #tablaMedicos .btn_ver_detalles', function(e) {
                // Prevent interfering with buttons inside the modal that happen to have the same class for styling
                var proveedor = $(this).data('prov');
                var modo = $(this).data('modo');
                var fechaInicio = $('#fechaDesde').val();
                var fechaFin = $('#fechaHasta').val();
                
                $('#modalFacturasTitle').text('Facturas de: ' + proveedor);
                $('#thDetalleCol3').text(modo === 'comercial' ? 'Vencimiento' : 'Paciente - Estudio');
                
                if (dtFacturasDetalle) {
                    dtFacturasDetalle.destroy();
                    $('#tablaFacturasDetalle').empty(); // Clear DOM for re-init
                    $('#tablaFacturasDetalle').html('<thead><tr><th>Fecha</th><th>No. Factura</th><th id="thDetalleCol3">' + (modo === 'comercial' ? 'Vencimiento' : 'Paciente - Estudio') + '</th><th>Valor</th><th>Saldado</th><th>Saldo Neto</th><th>Estado</th><th>Acciones</th></tr></thead><tbody></tbody>');
                }
                
                dtFacturasDetalle = $('#tablaFacturasDetalle').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    pageLength: 10,
                    destroy: true,
                    dom: 'Bfrtip',
                    buttons: ['copy', 'csv', 'excel', 'print'],
                    ajax: {
                        url: '../../backend/registros/get_cuentas_por_pagar.php',
                        type: 'POST',
                        data: function (d) {
                            d.tipo = modo;
                            d.fechaDesde = fechaInicio;
                            d.fechaHasta = fechaFin;
                            d.proveedor = proveedor;
                        }
                    },
                    columnDefs: [
                        { targets: [3, 4, 5], className: 'text-right' }
                    ]
                });
                
                $('#facturasModal').css('display', 'flex');
            });

            window.cerrarModalFacturas = function() {
                $('#facturasModal').css('display', 'none');
            };

            $(document).on('click', '.btn_pagar', function() {
                var id = $(this).data('id');
                var saldo = $(this).data('saldo');
                var modo = $(this).data('modo');
                alert("Módulo de Pago en construcción.\nPróximamente podrás abonar al " + modo + " con ID: " + id + "\nSaldo a pagar: L. " + saldo);
            });

            let dtPartidasDetalle = null;

            $(document).on('click', '.btn_ver_partidas', function(e) {
                e.stopPropagation();
                var id_ref = $(this).data('id');
                var modo = $(this).data('modo');
                
                $('#modalPartidasTitle').text(modo === 'comercial' ? 'Abonos a Factura' : 'Historial de Pago Médico');
                
                if (dtPartidasDetalle) {
                    dtPartidasDetalle.destroy();
                    $('#tablaPartidasDetalle').empty();
                }
                
                let thead = modo === 'comercial' 
                    ? '<tr><th>No. Partida</th><th>Fecha</th><th>Descripción</th><th>Monto Abonado</th></tr>'
                    : '<tr><th>No. Orden</th><th>Fecha Efectiva</th><th>Pagado Por</th><th>Total Honorario</th></tr>';
                $('#tablaPartidasDetalle').html('<thead>' + thead + '</thead><tbody></tbody>');
                
                dtPartidasDetalle = $('#tablaPartidasDetalle').DataTable({
                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' },
                    pageLength: 5,
                    destroy: true,
                    ajax: {
                        url: '../../backend/registros/get_cuentas_por_pagar.php',
                        type: 'POST',
                        data: function (d) {
                            d.tipo = modo;
                            d.accion = 'ver_partidas';
                            d.id_referencia = id_ref;
                        }
                    }
                });
                
                $('#partidasModal').css('display', 'flex');
            });

            window.cerrarModalPartidas = function() {
                $('#partidasModal').css('display', 'none');
            };

    </script>
</body>
</html>
