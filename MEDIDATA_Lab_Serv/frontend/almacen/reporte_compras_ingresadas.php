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
        .btn-editar-compra, .btn-eliminar-compra {
            background-color: #035c67; color: #fff; border: none; padding: 5px 10px;
            border-radius: 5px; cursor: pointer; font-size: 12px;
        }
        .btn-eliminar-compra { background-color: #c0392b; }
        .btn-editar-compra:hover { background-color: #06adbf; }
        .btn-eliminar-compra:hover { background-color: #e74c3c; }
        #modalEditarCompra .modal-content { max-width: 520px; width: 95%; }
        #avisoCompraPagos { display: none; color: #856404; background: #fff3cd; padding: 8px 10px; border-radius: 6px; margin-bottom: 10px; font-size: 13px; }
    </style>
</head>
<body>
<div id="page-loading-overlay">
    <div class="spinner"></div>
    <p>Cargando...</p>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){var o=document.getElementById('page-loading-overlay');if(o)o.style.display='none';});</script>

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

        <button class="button" onclick="cambiarColor(this, 'reporte_compras_ingresadas.php')">Compras Ingresadas</button>

        <br>

        <div class="catalog-container">
            <h2 class="catalog-title">Compras Ingresadas</h2>

            <div class="filters-container">
                <div class="filter-group">
                    <label for="fechaDesde">Desde:</label>
                    <input type="date" id="fechaDesde" class="filter-input" value="<?php echo htmlspecialchars($_GET['desde'] ?? date('Y-m-01')); ?>">
                </div>
                <div class="filter-group">
                    <label for="fechaHasta">Hasta:</label>
                    <input type="date" id="fechaHasta" class="filter-input" value="<?php echo htmlspecialchars($_GET['hasta'] ?? date('Y-m-t')); ?>">
                </div>
                <button type="button" class="btn-filter" onclick="aplicarFiltros()">Buscar</button>
                <button class="btn-filter btn-reset" onclick="limpiarFiltros()">Limpiar</button>
            </div>

            <div class="table-container">
                <div class="table-responsive">
                    <table id="tablaReporteCompras" class="display responsive-table dt-reporte-compras-unificado">
                        <thead>
                            <tr>
                                <th>Numero de Orden</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Num. Factura</th>
                                <th>Impuesto</th>
                                <th>SubTotal</th>
                                <th>Total</th>
                                <th>Partida contable</th>
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

<div id="modalEditarCompra" class="modal" style="display:none;">
    <div class="modal-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h2 style="margin:0;">Corregir compra</h2>
            <span class="close-btn" onclick="cerrarModalEditarCompra()" title="Cerrar">&times;</span>
        </div>
        <form id="formEditarCompra">
            <input type="hidden" id="editCompraId" name="id_compra">
            <div id="avisoCompraPagos">Esta compra ya tiene pagos. Solo puede corregir fecha, proveedor o número de factura.</div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraFecha">Fecha de emisión</label>
                <input type="date" id="editCompraFecha" name="fecha_emision" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraProveedor">Proveedor</label>
                <input type="text" id="editCompraProveedor" name="prov_datos" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraFactura">Número de factura</label>
                <input type="text" id="editCompraFactura" name="dato_fac" class="filter-input" style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraSubtotal">Subtotal</label>
                <input type="number" step="0.01" min="0" id="editCompraSubtotal" name="sub_total" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraIsv">Impuesto (ISV)</label>
                <input type="number" step="0.01" min="0" id="editCompraIsv" name="isv_global" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraTotal">Total</label>
                <input type="number" step="0.01" min="0" id="editCompraTotal" name="total" class="filter-input" required style="width:100%;">
            </div>
            <div class="filter-group" style="margin-bottom:10px;">
                <label for="editCompraMotivo">Motivo de corrección</label>
                <textarea id="editCompraMotivo" name="motivo" rows="3" maxlength="255" class="filter-input" required style="width:100%;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:12px;">
                <button type="button" class="btn-filter btn-reset" onclick="cerrarModalEditarCompra()">Cancelar</button>
                <button type="submit" class="btn-filter">Guardar cambios</button>
            </div>
        </div>
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
<script src="../../backend/registros/script/gestion_compra_reporte.js"></script>

<script>
$(document).ready(function() {
    medidataReporteComprasSS.initIngresadas();
    medidataComprasReporte.init({ pagina: 'reporte_compras_ingresadas.php' });
});

function aplicarFiltros() {
    medidataReporteComprasSS.recargar();
}

function limpiarFiltros() {
    medidataComprasReporte.limpiarFiltros();
}
</script>
</body>
</html>
