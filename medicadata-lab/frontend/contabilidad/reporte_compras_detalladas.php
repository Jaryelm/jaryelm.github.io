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
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
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

        <button class="button" onclick="cambiarColor(this, 'reporte_compras_detalladas.php')">Compras Detalladas</button>
        <button class="button" onclick="cambiarColor(this, 'reporte_compras_ingresadas.php')">Compras Ingresadas</button>

        <br>

        <div class="catalog-container">
            <h2 class="catalog-title">Compras Detalladas</h2>

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
                    <table id="tablaReporteCompras" class="display responsive-table dt-reporte-compras-unificado">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Num. Factura</th>
                                <th>Impuesto</th>
                                <th>Retención</th>
                                <th>Exenta</th>
                                <th>Gravada</th>
                                <th>SubTotal</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $desde = $_GET['desde'] ?? '';
                            $hasta = $_GET['hasta'] ?? '';

                            $sql = "SELECT c.fecha_emision, c.prov_datos, c.dato_fac, c.isv_global, c.sub_total, c.total,
                                COALESCE(d.sum_exenta, 0) AS sum_exenta,
                                COALESCE(d.sum_gravada, 0) AS sum_gravada
                                FROM compras c
                                LEFT JOIN (
                                    SELECT id_compra,
                                        SUM(CASE WHEN COALESCE(exento,0) = 1 THEN COALESCE(subtotal,0) ELSE 0 END) AS sum_exenta,
                                        SUM(CASE WHEN COALESCE(gravado,0) = 1
                                            OR (COALESCE(exento,0) = 0 AND COALESCE(gravado,0) = 0)
                                            THEN COALESCE(subtotal,0) ELSE 0 END) AS sum_gravada
                                    FROM detalle_compras
                                    GROUP BY id_compra
                                ) d ON d.id_compra = c.id_compra";

                            $params = [];
                            if ($desde && $hasta) {
                                $sql .= " WHERE DATE(c.fecha_emision) BETWEEN :desde AND :hasta";
                                $params[':desde'] = $desde;
                                $params[':hasta'] = $hasta;
                            } elseif ($desde) {
                                $sql .= " WHERE DATE(c.fecha_emision) >= :desde";
                                $params[':desde'] = $desde;
                            } elseif ($hasta) {
                                $sql .= " WHERE DATE(c.fecha_emision) <= :hasta";
                                $params[':hasta'] = $hasta;
                            }
                            $sql .= " ORDER BY c.fecha_registro DESC";

                            $stmt = $connect->prepare($sql);
                            $stmt->execute($params);
                            while ($row = $stmt->fetchObject()):
                                $fecha_raw = $row->fecha_emision ?? '';
                                $fecha = $fecha_raw ? date('d-m-Y', strtotime($fecha_raw)) : '-';
                                $retencion_txt = 'L. -';
                            ?>
                            <tr>
                                <td data-order="<?php echo htmlspecialchars($fecha_raw); ?>"><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($row->prov_datos ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->dato_fac ?? '-'); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->isv_global); ?></td>
                                <td><?php echo $retencion_txt; ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->sum_exenta); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->sum_gravada); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->sub_total); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->total); ?></td>
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
    $('#tablaReporteCompras').DataTable({
        pageLength: 10,
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        order: [[0, 'desc']],
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
    var url = 'reporte_compras_detalladas.php';
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}

function limpiarFiltros() {
    window.location.href = 'reporte_compras_detalladas.php';
}
</script>
</body>
</html>
