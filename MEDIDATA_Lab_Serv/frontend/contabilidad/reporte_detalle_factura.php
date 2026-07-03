<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/reporte_compras_datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
    <style>
        .acciones-wrap { display: inline-flex; gap: 6px; flex-wrap: wrap; }
        .btn-editar-ingreso, .btn-eliminar-ingreso {
            background-color: #035c67; color: #fff; border: none; padding: 5px 10px;
            border-radius: 5px; cursor: pointer; font-size: 12px;
        }
        .btn-eliminar-ingreso { background-color: #c0392b; }
        .btn-editar-ingreso:hover { background-color: #06adbf; }
        .btn-eliminar-ingreso:hover { background-color: #e74c3c; }
        #modalEditarIngreso .modal-content { max-width: 520px; width: 95%; }
    </style>
</head>
<body>
<div id="page-loading-overlay">
    <div class="spinner"></div>
    <p>Cargando...</p>
</div>
<script>setTimeout(function(){var o=document.getElementById('page-loading-overlay');if(o&&o.style.display!=='none')o.style.display='none';},8000);</script>

<?php include_once '../admin/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'reporte_cuadre_caja.php')">Cuadre Caja</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_detalle_pago.php')">Detalle Pago</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_detalle_factura.php')">Detalle Factura</button>
        <button class="button" onclick="cambiarColor(this, 'dashboard_ventas.php')">Dashboard Ventas</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_devoluciones_ventas.php')">Devoluciones</button>

        <br>

        <div class="catalog-container">
            <h2 class="catalog-title">Detalle Factura</h2>

            <form class="filters-container" onsubmit="event.preventDefault(); aplicarFiltros();">
                <div class="filter-group">
                    <label for="fechaDesde">Desde:</label>
                    <input type="date" id="fechaDesde" class="filter-input" value="<?php echo htmlspecialchars($_GET['desde'] ?? date('Y-m-01')); ?>">
                </div>
                <div class="filter-group">
                    <label for="fechaHasta">Hasta:</label>
                    <input type="date" id="fechaHasta" class="filter-input" value="<?php echo htmlspecialchars($_GET['hasta'] ?? date('Y-m-t')); ?>">
                </div>
                <button type="submit" class="btn-filter">Buscar</button>
                <button class="btn-filter btn-reset" onclick="limpiarFiltros()">Limpiar</button>
            </form>

            <div class="table-container">
                <div class="table-responsive">
                    <table id="tablaReporteDetalleFactura" class="display responsive-table dt-reporte-compras-unificado">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Num. Orden</th>
                                <th>Num. Factura</th>
                                <th>Forma de Pago</th>
                                <th>Usuario</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

<div id="modalEditarIngreso" class="modal" style="display:none;">
    <div class="modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="margin:0;">Corregir ingreso</h2>
            <span class="close-btn" onclick="cerrarModalEditarIngreso()" title="Cerrar">&times;</span>
        </div>
        <form id="formEditarIngreso">
            <input type="hidden" id="editIngresoId" name="idord">
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editIngresoFecha">Fecha</label>
                <input type="date" id="editIngresoFecha" name="placed_on" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editIngresoFactura">Número de factura</label>
                <input type="text" id="editIngresoFactura" name="invoice_number" class="filter-input" style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editIngresoMetodo">Forma de pago</label>
                <select id="editIngresoMetodo" name="method" class="filter-input" required style="width:100%;">
                    <option value="Efectivo">Efectivo</option>
                    <option value="Tarjeta">Tarjeta</option>
                    <option value="Pago Mixto">Pago Mixto</option>
                    <option value="Credito Colaborador">Credito Colaborador</option>
                    <option value="Crédito Colaborador">Crédito Colaborador</option>
                </select>
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editIngresoTotal">Total</label>
                <input type="number" step="0.01" min="0" id="editIngresoTotal" name="total_price" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editIngresoMotivo">Motivo de corrección</label>
                <textarea id="editIngresoMotivo" name="motivo" rows="3" maxlength="255" class="filter-input" required style="width:100%;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
                <button type="button" class="btn-filter btn-reset" onclick="cerrarModalEditarIngreso()">Cancelar</button>
                <button type="submit" class="btn-filter">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script type="text/javascript" src="../../backend/js/datatable.js"></script>
<script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
<script type="text/javascript" src="../../backend/js/jszip.js"></script>
<script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
<script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
<script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
<script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script src="../../backend/registros/script/reporte_compras_serverside.js"></script>
<script src="../../backend/registros/script/gestion_ingreso_reporte.js"></script>

<script>
$(document).ready(function() {
    medidataReporteComprasSS.initDetalleFactura();
    medidataIngresosReporte.init({ pagina: 'reporte_detalle_factura.php' });
});

function aplicarFiltros() {
    medidataReporteComprasSS.recargar();
}

function limpiarFiltros() {
    medidataIngresosReporte.limpiarFiltros();
}
</script>
</body>
</html>
