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
        border: 0 !important;
        box-shadow: none !important;
    }
    
    .partida-group-header td {
        padding: 12px !important;
        background-color: #e3f2fd !important;
        border-top: 0 !important;
        border-bottom: 0 !important;
    }

    #tablaDiarioGeneral tbody td,
    #tablaDiarioGeneral thead th {
        vertical-align: middle !important;
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
        vertical-align: middle !important;
    }

    .dt-acciones .acciones-wrap {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        width: 100%;
    }

    .btn_ver_detalles {
        background-color: #06adbf;
        border: none;
        color: #fff;
        padding: 6px 12px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 13px;
        line-height: 1.25;
    }

    .btn_ver_detalles:hover {
        background-color: #049aad;
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

    /* Modal detalle venta: mismos criterios que frontend/almacen/venta.php (evitar class "display" = conflict DataTables) */
    .diario-details-modal.modal {
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

    .diario-details-modal .modal-content {
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

    .diario-details-modal .modal-content > .close-btn {
        float: right;
    }

    .diario-details-modal .modal-content h2 {
        margin: 0 0 10px 0;
        padding-right: 36px;
        font-size: 1.25rem;
        color: #111;
        font-weight: bold;
    }

    /* Sobrescribe el .table-container del listado principal solo dentro del modal */
    .diario-details-modal .modal-content .table-container {
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

    .diario-details-modal .responsive-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        margin-bottom: 0;
    }

    .diario-details-modal .responsive-table thead {
        position: static;
        clip: auto;
        height: auto;
        width: auto;
        overflow: visible;
        padding: 0;
        border: 0;
    }

    .diario-details-modal .responsive-table thead tr,
    .diario-details-modal .responsive-table tbody tr {
        display: table-row;
        margin: 0;
        border: none;
    }

    /* venta.php (final): .responsive-table th, .responsive-table td */
    .diario-details-modal .responsive-table th,
    .diario-details-modal .responsive-table td {
        display: table-cell;
        padding: 10px;
        border: 1px solid #ddd;
        white-space: normal;
    }

    /* admin.css: .responsive-table thead th */
    .diario-details-modal .responsive-table thead th {
        background-color: #1e2c4b;
        border: 1px solid #1e2c4b;
        color: #fff;
        font-weight: normal;
        text-align: center;
    }

    .diario-details-modal .responsive-table thead th:first-of-type {
        text-align: left;
    }

    /* admin.css (min-width 52em): filas pares */
    .diario-details-modal .responsive-table tbody tr:nth-of-type(even) {
        background-color: rgba(94, 93, 82, 0.1);
    }

    /* admin.css (min-width 52em): tbody td */
    .diario-details-modal .responsive-table tbody td {
        text-align: center;
    }

    .diario-details-modal .close-btn {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        line-height: 1;
    }

    .diario-details-modal .close-btn:hover,
    .diario-details-modal .close-btn:focus {
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

    #diarioEditModal.modal {
        display: none;
        position: fixed;
        z-index: 14000;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.75);
        justify-content: center;
        align-items: center;
    }

    #diarioEditModal .modal-content {
        width: 96%;
        max-width: 480px;
        background: #fff;
        border-radius: 8px;
        border: 1px solid #d1d5db;
        padding: 0;
        text-align: left;
        position: relative;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
        overflow: hidden;
    }

    #diarioEditModal .diario-edit-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        background: #035c67;
        color: #fff;
    }

    #diarioEditModal .diario-edit-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
    }

    #diarioEditModal .diario-edit-header .close-btn {
        color: rgba(255, 255, 255, 0.85);
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        float: none;
        padding: 0 4px;
    }

    #diarioEditModal .diario-edit-header .close-btn:hover {
        color: #fff;
    }

    #diarioEditModal .diario-edit-form {
        padding: 16px 18px 18px;
    }

    #diarioEditModal .diario-edit-section {
        margin-bottom: 14px;
    }

    #diarioEditModal .diario-edit-section-title {
        margin: 0 0 10px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #035c67;
    }

    #diarioEditModal .diario-edit-readonly {
        padding: 12px;
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
    }

    /* Anula main .form-group (flex horizontal) dentro del modal */
    #diarioEditModal .diario-edit-form .form-group {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        width: 100% !important;
        gap: 6px !important;
        grid-gap: 6px !important;
        margin: 0 0 12px 0 !important;
    }

    #diarioEditModal .diario-edit-form .form-group:last-child {
        margin-bottom: 0 !important;
    }

    #diarioEditModal .diario-edit-form label {
        display: block;
        width: 100%;
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        color: #035c67;
        text-align: left;
        line-height: 1.25;
    }

    #diarioEditModal .diario-edit-form .filter-input {
        width: 100% !important;
        max-width: 100% !important;
    }

    #diarioEditModal .diario-edit-form input[type="text"],
    #diarioEditModal .diario-edit-form input[type="date"],
    #diarioEditModal .diario-edit-form textarea {
        flex-grow: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        box-sizing: border-box !important;
        padding: 8px 10px !important;
        border: 1px solid #ddd !important;
        border-radius: 4px !important;
        font-size: 14px !important;
        background: #fff !important;
        color: inherit !important;
        min-height: 38px;
        line-height: 1.2;
    }

    #diarioEditModal .diario-edit-form textarea {
        min-height: 72px;
        resize: vertical;
    }

    #diarioEditModal .diario-edit-form input:disabled {
        background: #eef1f3 !important;
        color: #495057;
        cursor: not-allowed;
    }

    #diarioEditModal .diario-edit-checkbox {
        display: flex !important;
        flex-direction: row !important;
        align-items: flex-start !important;
        gap: 8px !important;
        margin: 0 !important;
        font-weight: 400 !important;
        font-size: 13px !important;
        color: #374151 !important;
        cursor: pointer;
    }

    #diarioEditModal .diario-edit-checkbox input {
        width: auto !important;
        min-height: auto !important;
        margin: 3px 0 0 !important;
        flex-shrink: 0;
    }

    #diarioEditModal .diario-edit-actions {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid #e9ecef;
    }

    #diarioEditModal .diario-edit-actions .btn-filter {
        margin-top: 0;
        min-height: auto;
        padding: 6px 12px;
        font-size: 13px;
        line-height: 1.25;
    }
</style>

<div id="loadingDiarioDetalle" style="display:none;"><div class="box">Cargando detalles…</div></div>

<div id="diarioDetailsModal" class="modal diario-details-modal" style="display: none !important;">
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

<div id="diarioDetailsCompraModal" class="modal diario-details-modal" style="display: none !important;">
    <div class="modal-content">
        <span class="close-btn" onclick="diarioCerrarModalCompra()" title="Cerrar">&times;</span>
        <h2>Detalles de la Compra</h2>
        <div class="table-container">
            <table id="diarioDetailsCompraTable" class="responsive-table">
                <thead>
                    <tr>
                        <th scope="col">Cuenta</th>
                        <th scope="col">Código Producto</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Cantidad</th>
                        <th scope="col">Unidad</th>
                        <th scope="col">Precio Unitario</th>
                        <th scope="col">ISV</th>
                        <th scope="col">Subtotal</th>
                        <th scope="col">Descuento %</th>
                        <th scope="col">Total</th>
                    </tr>
                </thead>
                <tbody id="diarioDetailsCompraBody"></tbody>
            </table>
        </div>
    </div>
</div>

<div id="diarioEditModal" class="modal">
    <div class="modal-content">
        <div class="diario-edit-header">
            <h3>Editar partida</h3>
            <span class="close-btn" onclick="cerrarModalEditarPartida()" title="Cerrar">&times;</span>
        </div>
        <form id="formEditarPartida" class="diario-edit-form">
            <input type="hidden" id="editNumeroPartida" name="numero_partida">
            <input type="hidden" id="editReferencia" name="referencia">
            <input type="hidden" id="editTipoTransaccion" name="tipo_transaccion">

            <div class="diario-edit-section diario-edit-readonly">
                <p class="diario-edit-section-title">Datos de la partida</p>
                <div class="form-group">
                    <label for="editNumeroPartidaLabel">Partida</label>
                    <input type="text" id="editNumeroPartidaLabel" class="filter-input" disabled>
                </div>
                <div class="form-group">
                    <label for="editReferenciaLabel">Referencia</label>
                    <input type="text" id="editReferenciaLabel" class="filter-input" disabled>
                </div>
                <div class="form-group">
                    <label for="editTipoLabel">Tipo</label>
                    <input type="text" id="editTipoLabel" class="filter-input" disabled>
                </div>
            </div>

            <div class="diario-edit-section">
                <p class="diario-edit-section-title">Corrección</p>
                <div class="form-group">
                    <label for="editFechaOcurrencia">Nueva fecha de ocurrencia</label>
                    <input type="date" id="editFechaOcurrencia" name="fecha_ocurrencia" class="filter-input" required>
                </div>
                <div class="form-group">
                    <label for="editMotivo">Motivo de corrección</label>
                    <textarea id="editMotivo" name="motivo" rows="3" maxlength="255" required></textarea>
                </div>
                <div class="form-group" id="grupoSyncCompra" style="display:none;">
                    <label class="diario-edit-checkbox" for="editSyncCompra">
                        <input type="checkbox" id="editSyncCompra" name="sync_compra_fecha_emision" value="1">
                        <span>Sincronizar también la fecha del documento en la compra (fecha de emisión).</span>
                    </label>
                </div>
            </div>

            <div class="diario-edit-actions">
                <button type="button" class="btn-filter btn-reset" onclick="cerrarModalEditarPartida()">Cancelar</button>
                <button type="submit" class="btn_ver_detalles">Guardar ajuste</button>
            </div>
        </form>
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
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    
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
                    var actions = [];
                    var modo = row.detalle_modo || '';
                    var id = row.detalle_id;
                    if (modo && id != null && id !== '') {
                        actions.push('<button type="button" class="btn_ver_detalles" data-modo="' + modo + '" data-id="' + id + '">Ver detalles</button>');
                    }
                    if (row.editable) {
                        actions.push('<button type="button" class="btn_ver_detalles" data-partida="' + (row.numero_partida || '') + '" data-referencia="' + (row.referencia || '') + '" data-tipo="' + (row.tipo_transaccion || '') + '" data-fecha="' + (row.fecha_ocurrencia_iso || '') + '">Editar</button>');
                    }
                    return actions.length ? '<div class="acciones-wrap">' + actions.join('') + '</div>' : '—';
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
        order: [[1, 'desc']], // Ordenar por fecha de ocurrencia descendente
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

    $('#tablaDiarioGeneral').on('click', '.btn_ver_detalles[data-partida]', function() {
        abrirModalEditarPartida({
            numero_partida: $(this).data('partida'),
            referencia: $(this).data('referencia'),
            tipo_transaccion: $(this).data('tipo'),
            fecha_ocurrencia: $(this).data('fecha')
        });
    });

    $('#formEditarPartida').on('submit', function(ev) {
        ev.preventDefault();
        guardarEdicionPartida();
    });
});

function abrirModalEditarPartida(row) {
    $('#editNumeroPartida').val(row.numero_partida || '');
    $('#editReferencia').val(row.referencia || '');
    $('#editTipoTransaccion').val(row.tipo_transaccion || '');
    $('#editNumeroPartidaLabel').val(row.numero_partida || '');
    $('#editReferenciaLabel').val(row.referencia || '');
    $('#editTipoLabel').val(row.tipo_transaccion || '');
    $('#editFechaOcurrencia').val((row.fecha_ocurrencia || '').slice(0, 10));
    $('#editMotivo').val('');
    $('#editSyncCompra').prop('checked', false);

    if ((row.tipo_transaccion || '').toUpperCase() === 'COMPRA_PROVEEDOR') {
        $('#grupoSyncCompra').show();
    } else {
        $('#grupoSyncCompra').hide();
    }
    $('#diarioEditModal').css('display', 'flex');
}

function cerrarModalEditarPartida() {
    $('#diarioEditModal').hide();
}

function guardarEdicionPartida() {
    var payload = $('#formEditarPartida').serialize();
    $.ajax({
        url: '../../backend/php/diario_actualizar_partida.php',
        method: 'POST',
        dataType: 'json',
        data: payload,
        success: function(resp) {
            if (resp && resp.ok) {
                Swal.fire('Actualizado', resp.message || 'Partida actualizada.', 'success');
                cerrarModalEditarPartida();
                tablaDiarioGeneral.ajax.reload(null, false);
                return;
            }
            Swal.fire('Error', (resp && resp.message) ? resp.message : 'No se pudo actualizar la partida.', 'error');
        },
        error: function(xhr) {
            var msg = 'No se pudo actualizar la partida.';
            try {
                var json = JSON.parse(xhr.responseText || '{}');
                if (json.message) msg = json.message;
            } catch (e) {}
            Swal.fire('Error', msg, 'error');
        }
    });
}

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
    fetch('diario_detalle_venta.php?order_id=' + encodeURIComponent(orderId), { cache: 'no-store' })
        .then(function(response) { return response.json(); })
        .then(function(payload) {
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
            if (!payload || payload.success === false) {
                Swal.fire('Error', (payload && payload.error) ? payload.error : 'No se pudo cargar el detalle.', 'error');
                return;
            }
            var data = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            if (data.length === 0) {
                Swal.fire('Sin datos', 'No hay líneas de productos/servicios para esta orden.', 'info');
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
            Swal.fire('Error', 'No se pudieron cargar los detalles de la venta.', 'error');
        });
}

function diarioCerrarModalCompra() {
    var modal = document.getElementById('diarioDetailsCompraModal');
    if (modal) {
        modal.style.setProperty('display', 'none', 'important');
    }
}

function diarioVerDetalleCompra(idCompra) {
    diarioShowLoadingDetalle();
    fetch('diario_detalle_compra.php?id_compra=' + encodeURIComponent(idCompra), { cache: 'no-store' })
        .then(function(response) { return response.json(); })
        .then(function(payload) {
            diarioHideLoadingDetalle();
            try {
                if (!payload || payload.success === false) {
                    Swal.fire('Error', (payload && payload.error) ? payload.error : 'No se pudo cargar el detalle.', 'error');
                    return;
                }
                var detalles = Array.isArray(payload.data) ? payload.data : [];
                if (detalles.length === 0) {
                    Swal.fire('No se encontraron detalles', 'No hay detalles disponibles para esta compra.', 'info');
                    return;
                }
                var body = document.getElementById('diarioDetailsCompraBody');
                if (!body) {
                    return;
                }
                body.innerHTML = '';
                detalles.forEach(function(item) {
                    var tr = document.createElement('tr');
                    tr.innerHTML =
                        '<td>' + (item.cat_cuenta || '') + '</td>' +
                        '<td>' + (item.codigo_producto || '') + '</td>' +
                        '<td>' + (item.descripcion || '') + '</td>' +
                        '<td>' + (item.cantidad || '') + '</td>' +
                        '<td>' + (item.unidad || '') + '</td>' +
                        '<td>' + parseFloat(item.precio_unitario || 0).toFixed(2) + '</td>' +
                        '<td>' + parseFloat(item.isv || 0).toFixed(2) + '</td>' +
                        '<td>' + (item.subtotal || '') + '</td>' +
                        '<td>' + parseFloat(item.descuento_porcentaje || 0).toFixed(2) + '</td>' +
                        '<td>' + (item.total_item || '') + '</td>';
                    body.appendChild(tr);
                });
                var modal = document.getElementById('diarioDetailsCompraModal');
                if (modal) {
                    modal.style.setProperty('display', 'flex', 'important');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'Hubo un problema al cargar los detalles.', 'error');
            }
        })
        .catch(function() {
            diarioHideLoadingDetalle();
            Swal.fire('Error', 'No se pudo cargar los detalles de la compra.', 'error');
        });
}

document.addEventListener('click', function(ev) {
    if (ev.target && ev.target.id === 'diarioDetailsModal') {
        diarioCerrarModalVenta();
    }
    if (ev.target && ev.target.id === 'diarioDetailsCompraModal') {
        diarioCerrarModalCompra();
    }
    if (ev.target && ev.target.id === 'diarioEditModal') {
        cerrarModalEditarPartida();
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
</script>
<script src="../../backend/registros/script/diario_general_export.js?v=20260530e"></script>

</body>
</html>


