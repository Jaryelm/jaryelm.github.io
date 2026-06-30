<?php
include_once '../../backend/registros/session_check.php';

/** Formato lempiras: miles con coma, decimales con punto (ej. L. 2,836.27) */
function fmt_lempiras_reporte($valor) {
    $n = (float)($valor ?? 0);
    return 'L. ' . number_format($n, 2, '.', ',');
}

/**
 * Determina el tipo predominante de descuento en una orden a partir de las
 * sumas por categoría calculadas con SQL. Si no hay descuento alguno, retorna '-'.
 */
function tipo_descuento_predominante($row) {
    $categorias = [
        'Edad 30%'  => (float)($row->desc_edad_30 ?? 0),
        'Edad 40%'  => (float)($row->desc_edad_40 ?? 0),
        'Promoción' => (float)($row->desc_promocion ?? 0),
        'Otros'     => (float)($row->desc_otros ?? 0),
        'Porcentaje'=> (float)($row->desc_porcentaje ?? 0),
    ];
    $max_nombre = '-';
    $max_valor  = 0.0;
    foreach ($categorias as $nombre => $valor) {
        if ($valor > $max_valor) {
            $max_valor  = $valor;
            $max_nombre = $nombre;
        }
    }
    return $max_valor > 0 ? $max_nombre : '-';
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
            <h2 class="catalog-title">Detalle Pago</h2>

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
                    <table id="tablaReporteDetallePago" class="display responsive-table dt-reporte-compras-unificado">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Forma de Pago</th>
                                <th>Estado</th>
                                <th>Num. Factura</th>
                                <th>Detalle Examen</th>
                                <th>Cliente</th>
                                <th>Cliente Celular</th>
                                <th>Tipo de Descuento</th>
                                <th>Sub Total</th>
                                <th>Descuento</th>
                                <th>Impuesto</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $desde = $_GET['desde'] ?? '';
                            $hasta = $_GET['hasta'] ?? '';

                            // Detectar columnas opcionales en orders (defensivo en producción)
                            $col_tel = false;
                            try {
                                $col_tel = $connect->query("SHOW COLUMNS FROM orders LIKE 'telefono_paciente'")->rowCount() > 0;
                            } catch (Exception $e) { /* ignorar */ }
                            $expr_tel = $col_tel ? "o.telefono_paciente" : "NULL";

                            $sql = "SELECT
                                        o.idord,
                                        o.invoice_number,
                                        o.placed_on,
                                        o.method,
                                        o.invoice_status,
                                        o.nomcl,
                                        o.dni_paciente,
                                        $expr_tel AS telefono_paciente,
                                        o.price_without_discount,
                                        o.discount_amount,
                                        o.total_price,
                                        COALESCE(o.tax_amount, 0) AS tax_amount,
                                        p.phon AS paciente_phon,
                                        det.detalle_examen,
                                        det.desc_edad_30,
                                        det.desc_edad_40,
                                        det.desc_promocion,
                                        det.desc_otros,
                                        det.desc_porcentaje
                                    FROM orders o
                                    LEFT JOIN patients p ON p.numhs = o.dni_paciente
                                    LEFT JOIN (
                                        SELECT
                                            order_id,
                                            GROUP_CONCAT(descripcion SEPARATOR ', ') AS detalle_examen,
                                            SUM(COALESCE(age_discount_30, 0))   AS desc_edad_30,
                                            SUM(COALESCE(age_discount_40, 0))   AS desc_edad_40,
                                            SUM(COALESCE(promotion_discount,0)) AS desc_promocion,
                                            SUM(COALESCE(other_discount, 0))    AS desc_otros,
                                            SUM(COALESCE(discount_percentage,0))AS desc_porcentaje
                                        FROM order_details
                                        GROUP BY order_id
                                    ) det ON det.order_id = o.idord";

                            $params = [];
                            if ($desde && $hasta) {
                                $sql .= " WHERE DATE(o.placed_on) BETWEEN :desde AND :hasta";
                                $params[':desde'] = $desde;
                                $params[':hasta'] = $hasta;
                            } elseif ($desde) {
                                $sql .= " WHERE DATE(o.placed_on) >= :desde";
                                $params[':desde'] = $desde;
                            } elseif ($hasta) {
                                $sql .= " WHERE DATE(o.placed_on) <= :hasta";
                                $params[':hasta'] = $hasta;
                            }
                            $sql .= " ORDER BY o.placed_on DESC";

                            $stmt = $connect->prepare($sql);
                            $stmt->execute($params);
                            while ($row = $stmt->fetchObject()):
                                $fh_raw  = $row->placed_on ?? '';
                                $fecha   = $fh_raw ? date('d-m-Y', strtotime($fh_raw)) : '-';
                                $hora    = $fh_raw ? date('H:i', strtotime($fh_raw)) : '-';
                                // Celular: priorizar el guardado en la orden (ambulatorio); si no, el de patients
                                $celular = $row->telefono_paciente ?: ($row->paciente_phon ?: '-');
                                $tipo_desc = tipo_descuento_predominante($row);
                            ?>
                            <tr>
                                <td data-order="<?php echo htmlspecialchars($fh_raw); ?>"><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($hora); ?></td>
                                <td><?php echo htmlspecialchars($row->method ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->invoice_status ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars(!empty($row->invoice_number) ? $row->invoice_number : '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->detalle_examen ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->nomcl ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($celular); ?></td>
                                <td><?php echo htmlspecialchars($tipo_desc); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->price_without_discount); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->discount_amount); ?></td>
                                <td><?php echo fmt_lempiras_reporte($row->tax_amount); ?></td>
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
    $('#tablaReporteDetallePago').DataTable({
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
    var url = 'reporte_detalle_pago_user.php';
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}

function limpiarFiltros() {
    window.location.href = 'reporte_detalle_pago_user.php';
}
</script>
</body>
</html>
