<?php
include_once '../../backend/registros/session_check.php';

/** Formato lempiras: miles con coma, decimales con punto (ej. L. 2,836.27) */
function fmt_lempiras_reporte($valor) {
    $n = (float)($valor ?? 0);
    return 'L. ' . number_format($n, 2, '.', ',');
}

/** Capitaliza palabras separadas por '_' (ej. error_pedido => Error Pedido) */
function fmt_motivo($motivo) {
    if ($motivo === null || $motivo === '') return '-';
    if (strpos($motivo, '_') !== false) {
        return ucwords(str_replace('_', ' ', $motivo));
    }
    return $motivo;
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
            <h2 class="catalog-title">Devoluciones de Ventas</h2>

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
                    <table id="tablaReporteDevoluciones" class="display responsive-table dt-reporte-compras-unificado">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Num. Orden</th>
                                <th>Num. Factura</th>
                                <th>Cliente</th>
                                <th>Tipo Item</th>
                                <th>Descripción</th>
                                <th>Cantidad</th>
                                <th>Motivo</th>
                                <th>Procesado por</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $desde = $_GET['desde'] ?? '';
                            $hasta = $_GET['hasta'] ?? '';

                            // ---------------------------------------------------------------
                            // FUENTE 1: returns (devoluciones por ítem)
                            // ---------------------------------------------------------------
                            $sql_returns = "SELECT
                                    r.return_date AS fecha,
                                    'Devolución' AS tipo_evento,
                                    o.idord,
                                    o.invoice_number,
                                    o.nomcl,
                                    r.product_id,
                                    r.quantity_returned AS cantidad,
                                    r.return_reason AS motivo,
                                    r.processed_by AS procesado_por,
                                    od.descripcion AS detalle,
                                    od.item_type,
                                    od.total_after_discount AS valor_item
                                FROM returns r
                                LEFT JOIN orders o ON o.idord = r.order_id
                                LEFT JOIN order_details od
                                    ON od.order_id = r.order_id
                                    AND (
                                        od.codpro COLLATE utf8_general_ci = r.product_id COLLATE utf8_general_ci
                                        OR CAST(od.id AS CHAR) COLLATE utf8_general_ci = r.product_id COLLATE utf8_general_ci
                                    )";

                            $params_r = [];
                            $where_r = [];
                            if ($desde) { $where_r[] = "DATE(r.return_date) >= :desde_r"; $params_r[':desde_r'] = $desde; }
                            if ($hasta) { $where_r[] = "DATE(r.return_date) <= :hasta_r"; $params_r[':hasta_r'] = $hasta; }
                            if ($where_r) $sql_returns .= " WHERE " . implode(' AND ', $where_r);

                            $stmt_r = $connect->prepare($sql_returns);
                            $stmt_r->execute($params_r);
                            $rows_returns = $stmt_r->fetchAll(PDO::FETCH_ASSOC);

                            // ---------------------------------------------------------------
                            // FUENTE 2: orders con invoice_status = 'Anulada' (factura completa)
                            // ---------------------------------------------------------------
                            // Verificamos columnas opcionales (anulada_at, anulada_por, observacion_anulacion)
                            $col_anulada_at = false; $col_anulada_por = false; $col_obs = false;
                            try {
                                $col_anulada_at  = $connect->query("SHOW COLUMNS FROM orders LIKE 'anulada_at'")->rowCount() > 0;
                                $col_anulada_por = $connect->query("SHOW COLUMNS FROM orders LIKE 'anulada_por'")->rowCount() > 0;
                                $col_obs         = $connect->query("SHOW COLUMNS FROM orders LIKE 'observacion_anulacion'")->rowCount() > 0;
                            } catch (Exception $e) { /* ignorar */ }

                            $expr_fecha    = $col_anulada_at  ? "COALESCE(o.anulada_at, o.placed_on)" : "o.placed_on";
                            $expr_anulpor  = $col_anulada_por ? "o.anulada_por" : "NULL";
                            $expr_obs      = $col_obs         ? "o.observacion_anulacion" : "NULL";

                            $sql_anuladas = "SELECT
                                    $expr_fecha AS fecha,
                                    'Anulación' AS tipo_evento,
                                    o.idord,
                                    o.invoice_number,
                                    o.nomcl,
                                    NULL AS product_id,
                                    NULL AS cantidad,
                                    $expr_obs AS motivo,
                                    $expr_anulpor AS procesado_por,
                                    NULL AS detalle,
                                    NULL AS item_type,
                                    o.total_price AS valor_item
                                FROM orders o
                                WHERE o.invoice_status = 'Anulada'";

                            $params_a = [];
                            if ($desde) { $sql_anuladas .= " AND DATE($expr_fecha) >= :desde_a"; $params_a[':desde_a'] = $desde; }
                            if ($hasta) { $sql_anuladas .= " AND DATE($expr_fecha) <= :hasta_a"; $params_a[':hasta_a'] = $hasta; }

                            $stmt_a = $connect->prepare($sql_anuladas);
                            $stmt_a->execute($params_a);
                            $rows_anul = $stmt_a->fetchAll(PDO::FETCH_ASSOC);

                            // ---------------------------------------------------------------
                            // Unificar y ordenar por fecha DESC
                            // ---------------------------------------------------------------
                            $rows = array_merge($rows_returns, $rows_anul);
                            usort($rows, function ($a, $b) {
                                return strcmp($b['fecha'] ?? '', $a['fecha'] ?? '');
                            });

                            foreach ($rows as $row):
                                $fecha_raw = $row['fecha'] ?? '';
                                $fecha     = $fecha_raw ? date('d-m-Y H:i', strtotime($fecha_raw)) : '-';

                                $tipo_evento = $row['tipo_evento'] ?? '-';
                                $idord       = $row['idord'] ?? '-';
                                $factura     = $row['invoice_number'] ?? '-';
                                $cliente     = $row['nomcl'] ?? '-';

                                if ($tipo_evento === 'Anulación') {
                                    $tipo_item   = 'Toda la factura';
                                    $descripcion = 'Factura completa anulada';
                                    $cantidad    = '-';
                                } else {
                                    // item_type viene como 'producto'/'servicio'
                                    $it = strtolower((string)($row['item_type'] ?? ''));
                                    if ($it === 'producto')      $tipo_item = 'Producto';
                                    elseif ($it === 'servicio')  $tipo_item = 'Servicio';
                                    else                          $tipo_item = '-';
                                    $descripcion = $row['detalle'] ?? ($row['product_id'] ?? '-');
                                    $cantidad    = $row['cantidad'] ?? '-';
                                }

                                $motivo = fmt_motivo($row['motivo'] ?? null);
                                $procpor = $row['procesado_por'] ?? '-';
                                $valor   = $row['valor_item'] ?? 0;
                            ?>
                            <tr>
                                <td data-order="<?php echo htmlspecialchars($fecha_raw); ?>"><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($tipo_evento); ?></td>
                                <td><?php echo htmlspecialchars($idord); ?></td>
                                <td><?php echo htmlspecialchars($factura); ?></td>
                                <td><?php echo htmlspecialchars($cliente); ?></td>
                                <td><?php echo htmlspecialchars($tipo_item); ?></td>
                                <td><?php echo htmlspecialchars($descripcion); ?></td>
                                <td><?php echo htmlspecialchars($cantidad); ?></td>
                                <td><?php echo htmlspecialchars($motivo); ?></td>
                                <td><?php echo htmlspecialchars($procpor); ?></td>
                                <td><?php echo fmt_lempiras_reporte($valor); ?></td>
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
    $('#tablaReporteDevoluciones').DataTable({
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
    var url = 'reporte_devoluciones_ventas_user.php';
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}

function limpiarFiltros() {
    window.location.href = 'reporte_devoluciones_ventas_user.php';
}
</script>
</body>
</html>
