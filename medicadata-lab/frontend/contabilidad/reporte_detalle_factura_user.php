<?php
include_once '../../backend/registros/session_check.php';

/** Formato lempiras: miles con coma, decimales con punto (ej. L. 2,836.27) */
function fmt_lempiras_reporte($valor) {
    $n = (float)($valor ?? 0);
    return 'L. ' . number_format($n, 2, '.', ',');
}
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
    <title>MEDIDATA</title>
</head>
<body>
<div id="page-loading-overlay">
    <div class="spinner"></div>
    <p>Cargando...</p>
</div>
<script>setTimeout(function(){var o=document.getElementById('page-loading-overlay');if(o&&o.style.display!=='none')o.style.display='none';},8000);</script>

<?php require_once __DIR__ . '/include_menu_sidebar_por_rol.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php require_once __DIR__ . '/include_nav_perfil_por_rol.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'reporte_cuadre_caja_user.php')">Cuadre Caja</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_detalle_pago_user.php')">Detalle Pago</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_detalle_factura_user.php')">Detalle Factura</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_devoluciones_ventas_user.php')">Devoluciones</button>

        <br>

        <div class="catalog-container">
            <h2 class="catalog-title">Detalle Factura</h2>

            <div class="filters-container">
                <div class="filter-group">
                    <label for="fechaDesde">Desde:</label>
                    <input type="date" id="fechaDesde" class="filter-input" value="<?php echo htmlspecialchars($_GET['desde'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="fechaHasta">Hasta:</label>
                    <input type="date" id="fechaHasta" class="filter-input" value="<?php echo htmlspecialchars($_GET['hasta'] ?? ''); ?>">
                </div>
                <button class="btn-filter" onclick="aplicarFiltros()">Buscar</button>
                <button class="btn-filter btn-reset" onclick="limpiarFiltros()">Limpiar</button>
            </div>

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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $desde = $_GET['desde'] ?? '';
                            $hasta = $_GET['hasta'] ?? '';

                            $sql = "SELECT idord, placed_on, invoice_number, method, processed_by, total_price
                                    FROM orders
                                    WHERE invoice_status = 'Cobrada'
                                      AND invoice_number IS NOT NULL
                                      AND invoice_number <> ''";

                            $params = [];
                            if ($desde && $hasta) {
                                $sql .= " AND DATE(placed_on) BETWEEN :desde AND :hasta";
                                $params[':desde'] = $desde;
                                $params[':hasta'] = $hasta;
                            } elseif ($desde) {
                                $sql .= " AND DATE(placed_on) >= :desde";
                                $params[':desde'] = $desde;
                            } elseif ($hasta) {
                                $sql .= " AND DATE(placed_on) <= :hasta";
                                $params[':hasta'] = $hasta;
                            }
                            $sql .= " ORDER BY placed_on DESC";

                            $stmt = $connect->prepare($sql);
                            $stmt->execute($params);
                            while ($row = $stmt->fetchObject()):
                                $fecha_raw = $row->placed_on ?? '';
                                $fecha = $fecha_raw ? date('d-m-Y', strtotime($fecha_raw)) : '-';
                            ?>
                            <tr>
                                <td data-order="<?php echo htmlspecialchars($fecha_raw); ?>"><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($row->idord); ?></td>
                                <td><?php echo htmlspecialchars($row->invoice_number ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->method ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->processed_by ?? '-'); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->total_price); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</section>

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

<script>
$(document).ready(function() {
    $('#tablaReporteDetalleFactura').DataTable({
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        order: [[0, 'desc']],
        initComplete: function() {
            $('.dataTables_wrapper').addClass('dt-ready');
            $('#page-loading-overlay').hide();
        },
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sInfo: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            sInfoEmpty: "Mostrando 0 a 0 de 0 registros",
            sInfoFiltered: "(filtrado de _MAX_ registros totales)",
            sSearch: "Buscar:",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior"
            }
        }
    });
});

function aplicarFiltros() {
    var desde = document.getElementById('fechaDesde').value;
    var hasta = document.getElementById('fechaHasta').value;
    var params = [];
    if (desde) params.push('desde=' + encodeURIComponent(desde));
    if (hasta) params.push('hasta=' + encodeURIComponent(hasta));
    var url = 'reporte_detalle_factura_user.php';
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}

function limpiarFiltros() {
    window.location.href = 'reporte_detalle_factura_user.php';
}
</script>
</body>
</html>
