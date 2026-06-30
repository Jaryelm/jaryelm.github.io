<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/funciones_cuadre_caja.php';

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
        <button class="button" onclick="cambiarColor(this, 'reporte_devoluciones_ventas.php')">Devoluciones</button>

        <br>

        <div class="catalog-container">
            <h2 class="catalog-title">Cuadre Caja</h2>

            <form class="filters-container" onsubmit="event.preventDefault(); aplicarFiltros();">
                <div class="filter-group">
                    <label for="fechaDesde">Desde:</label>
                    <input type="date" id="fechaDesde" class="filter-input" value="<?php echo htmlspecialchars($_GET['desde'] ?? ''); ?>">
                </div>
                <div class="filter-group">
                    <label for="fechaHasta">Hasta:</label>
                    <input type="date" id="fechaHasta" class="filter-input" value="<?php echo htmlspecialchars($_GET['hasta'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn-filter">Buscar</button>
                <button class="btn-filter btn-reset" onclick="limpiarFiltros()">Limpiar</button>
            </form>

            <div class="table-container">
                <div class="table-responsive">
                    <table id="tablaReporteCuadreCaja" class="display responsive-table dt-reporte-compras-unificado">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario Nombre</th>
                                <th>Efectivo</th>
                                <th>Tarjeta</th>
                                <th>Otros</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $desde = $_GET['desde'] ?? '';
                            $hasta = $_GET['hasta'] ?? '';
                            $filasCuadre = medidata_filas_cuadre_caja(
                                $connect,
                                $desde !== '' ? $desde : null,
                                $hasta !== '' ? $hasta : null
                            );

                            foreach ($filasCuadre as $row):
                                $fecha_raw = $row['fecha_orden'] ?? '';
                                $fecha = $row['fecha'] ? date('d-m-Y', strtotime($row['fecha'])) : '-';
                                $efectivo = medidata_cuadre_monto_metodo($row['metodos'], 'Efectivo');
                                $tarjeta = medidata_cuadre_monto_metodo($row['metodos'], 'Tarjeta');
                                $otros = medidata_cuadre_otros_metodos($row['metodos']);
                            ?>
                            <tr>
                                <td data-order="<?php echo htmlspecialchars($fecha_raw); ?>"><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($row['nombre'] ?? '-'); ?></td>
                                <td><?php echo fmt_lempiras_reporte($efectivo); ?></td>
                                <td><?php echo fmt_lempiras_reporte($tarjeta); ?></td>
                                <td><?php echo fmt_lempiras_reporte($otros); ?></td>
                            </tr>
                            <?php endforeach; ?>
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
    $('#tablaReporteCuadreCaja').DataTable({
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
    var url = 'reporte_cuadre_caja.php';
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}

function limpiarFiltros() {
    window.location.href = 'reporte_cuadre_caja.php';
}
</script>
</body>
</html>
