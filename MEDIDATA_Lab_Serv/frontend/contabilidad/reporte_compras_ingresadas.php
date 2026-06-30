<?php
include_once '../../backend/registros/session_check.php';

$medidataComprasTieneNpc = false;
try {
    $___chkNpc = $connect->query("SHOW COLUMNS FROM compras LIKE 'numero_partida_contable'");
    $medidataComprasTieneNpc = (bool) ($___chkNpc && $___chkNpc->fetch(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    $medidataComprasTieneNpc = false;
}
$medidataSqlNpc = $medidataComprasTieneNpc ? 'numero_partida_contable' : 'NULL AS numero_partida_contable';
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
            <h2 class="catalog-title">Compras Ingresadas</h2>

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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $desde = $_GET['desde'] ?? '';
                            $hasta = $_GET['hasta'] ?? '';
                            $sql = "SELECT id_compra, fecha_emision, prov_datos, dato_fac, isv_global, sub_total, total, {$medidataSqlNpc} FROM compras";
                            $params = [];
                            if ($desde && $hasta) {
                                $sql .= " WHERE DATE(fecha_emision) BETWEEN :desde AND :hasta";
                                $params[':desde'] = $desde;
                                $params[':hasta'] = $hasta;
                            } elseif ($desde) {
                                $sql .= " WHERE DATE(fecha_emision) >= :desde";
                                $params[':desde'] = $desde;
                            } elseif ($hasta) {
                                $sql .= " WHERE DATE(fecha_emision) <= :hasta";
                                $params[':hasta'] = $hasta;
                            }
                            $sql .= " ORDER BY fecha_registro DESC";
                            $stmt = $connect->prepare($sql);
                            $stmt->execute($params);
                            while ($row = $stmt->fetchObject()):
                                $fecha = $row->fecha_emision ? date('d-m-Y', strtotime($row->fecha_emision)) : '-';
                                $impuesto = number_format((float)($row->isv_global ?? 0), 2, '.', ',');
                                $subtotal = number_format((float)($row->sub_total ?? 0), 2, '.', ',');
                                $total = number_format((float)($row->total ?? 0), 2, '.', ',');
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row->id_compra); ?></td>
                                <td><?php echo htmlspecialchars($fecha); ?></td>
                                <td><?php echo htmlspecialchars($row->prov_datos ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row->dato_fac ?? '-'); ?></td>
                                <td>L. <?php echo $impuesto; ?></td>
                                <td>L. <?php echo $subtotal; ?></td>
                                <td>L. <?php echo $total; ?></td>
                                <td><?php echo htmlspecialchars($row->numero_partida_contable ?? ''); ?></td>
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
        order: [[1, 'desc']],
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
    var url = 'reporte_compras_ingresadas.php';
    if (params.length) url += '?' + params.join('&');
    window.location.href = url;
}

function limpiarFiltros() {
    window.location.href = 'reporte_compras_ingresadas.php';
}
</script>
</body>
</html>
