<?php
include_once '../../backend/registros/session_check.php';
require_once __DIR__ . '/../../backend/php/facturacion_cai_config.php';

$okMsg = null;
$errMsg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_rango_cai'])) {
    try {
        $payload = [
            'cai' => $_POST['cai'] ?? '',
            'prefijo_factura' => $_POST['prefijo_factura'] ?? '',
            'rango_inicial' => $_POST['rango_inicial'] ?? '',
            'rango_final' => $_POST['rango_final'] ?? '',
            'fecha_limite' => $_POST['fecha_limite'] ?? '',
            'resolucion_sar' => $_POST['resolucion_sar'] ?? '',
        ];
        medidata_factura_config_save($connect, $payload, (string) ($_SESSION['name'] ?? ''));
        $okMsg = 'Rango de facturación registrado y activado correctamente.';
    } catch (Throwable $e) {
        $errMsg = $e->getMessage();
    }
}

$active = medidata_factura_config_get_active($connect);
$historial = medidata_factura_config_list($connect, 20);
$preview = null;
$previewErr = null;
try {
    $preview = medidata_factura_next_invoice($connect);
} catch (Throwable $e) {
    $previewErr = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA</title>
    <style>
        .cai-card { background:#fff; border-radius:8px; padding:18px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        .cai-title { margin:0 0 12px; color:#035c67; }
        .cai-title-main { color:#000; }
        .cai-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
        .cai-field { display:flex; flex-direction:column; }
        .cai-field label { display:block; font-weight:600; margin-bottom:5px; color:#222; min-height:20px; }
        .cai-field input { width:100%; border:1px solid #d3d3d3; border-radius:6px; padding:10px 12px; font-size:14px; margin:0 !important; height:40px; background:#fff; }
        .cai-actions { margin-top:14px; display:flex; gap:10px; align-items:center; }
        .cai-btn { border:0; border-radius:6px; padding:10px 16px; background:#06adbf; color:#fff; cursor:pointer; font-weight:600; }
        .cai-btn:hover { background:#02899a; }
        .cai-alert { border-radius:6px; padding:11px 12px; margin-bottom:10px; }
        .cai-alert.ok { background:#e8f7f4; color:#0d4f44; border:1px solid #8ed8c9; }
        .cai-alert.err { background:#fde9e9; color:#7c1f1f; border:1px solid #efb1b1; }
        .cai-table { width:100%; border-collapse:collapse; }
        .cai-table th,.cai-table td { border:1px solid #ddd; padding:8px 10px; font-size:13px; }
        .cai-table th { background:#0a6b78; color:#fff; text-align:left; }
        .cai-badge { display:inline-block; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:700; }
        .cai-badge.on { background:#dff5ef; color:#0b6f5a; }
        .cai-badge.off { background:#f1f1f1; color:#666; }
        .cai-meta { margin:0; color:#555; font-size:13px; }
        #table_cai_historial_wrapper .dataTables_info,
        #table_cai_historial_wrapper .dataTables_paginate { margin-top:10px; }
    </style>
</head>
<body>
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
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? 'Buenos Días' : (($hora_actual < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? 'Usuario') . '</strong>'; ?></h1>

        <div class="cai-card">
            <h2 class="cai-title cai-title-main">Configuración de rango de facturación SAR (CAI)</h2>
            <p class="cai-meta">Registra un nuevo rango autorizado por SAR. Al guardar, queda activo inmediatamente para nuevas facturas.</p>

            <?php if ($okMsg !== null): ?><div class="cai-alert ok"><?php echo htmlspecialchars($okMsg); ?></div><?php endif; ?>
            <?php if ($errMsg !== null): ?><div class="cai-alert err"><?php echo htmlspecialchars($errMsg); ?></div><?php endif; ?>

            <form method="post" autocomplete="off">
                <div class="cai-grid">
                    <div class="cai-field">
                        <label for="cai">CAI</label>
                        <input id="cai" name="cai" required maxlength="64" placeholder="Ej: 123ABC-..." />
                    </div>
                    <div class="cai-field">
                        <label for="prefijo_factura">Prefijo factura</label>
                        <input id="prefijo_factura" name="prefijo_factura" required value="000-001-01" placeholder="000-001-01" />
                    </div>
                    <div class="cai-field">
                        <label for="rango_inicial">Rango inicial (correlativo)</label>
                        <input id="rango_inicial" name="rango_inicial" type="number" min="1" required placeholder="410000" />
                    </div>
                    <div class="cai-field">
                        <label for="rango_final">Rango final (correlativo)</label>
                        <input id="rango_final" name="rango_final" type="number" min="1" required placeholder="420000" />
                    </div>
                    <div class="cai-field">
                        <label for="fecha_limite">Fecha límite de emisión</label>
                        <input id="fecha_limite" name="fecha_limite" type="date" />
                    </div>
                    <div class="cai-field">
                        <label for="resolucion_sar">Resolución SAR (opcional)</label>
                        <input id="resolucion_sar" name="resolucion_sar" maxlength="120" placeholder="Ej: SAR-2026-..." />
                    </div>
                </div>
                <div class="cai-actions">
                    <button class="cai-btn" type="submit" name="guardar_rango_cai" value="1">Guardar y activar</button>
                </div>
            </form>
        </div>

        <div class="cai-card">
            <h3 class="cai-title">Rango activo</h3>
            <?php if ($active): ?>
                <p class="cai-meta"><strong>CAI:</strong> <?php echo htmlspecialchars((string) $active['cai']); ?></p>
                <p class="cai-meta"><strong>Prefijo:</strong> <?php echo htmlspecialchars((string) $active['prefijo_factura']); ?> |
                    <strong>Rango:</strong> <?php echo (int) $active['rango_inicial']; ?> - <?php echo (int) $active['rango_final']; ?> |
                    <strong>Fecha límite:</strong> <?php echo htmlspecialchars((string) ($active['fecha_limite'] ?? '')); ?></p>
            <?php else: ?>
                <p class="cai-meta">No hay rango activo. Se usará secuencia legacy hasta registrar uno.</p>
            <?php endif; ?>

            <?php if ($preview): ?>
                <p class="cai-meta"><strong>Próxima factura:</strong> <?php echo htmlspecialchars((string) $preview['invoice_number']); ?></p>
            <?php elseif ($previewErr !== null): ?>
                <div class="cai-alert err"><?php echo htmlspecialchars($previewErr); ?></div>
            <?php endif; ?>
        </div>

        <div class="cai-card">
            <h3 class="cai-title">Historial de rangos</h3>
            <div class="table-responsive sv-dt-expediente-wrap">
                <table id="table_cai_historial" class="cai-table responsive-table" style="width:100%;">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>CAI</th>
                        <th>Prefijo</th>
                        <th>Rango</th>
                        <th>Fecha límite</th>
                        <th>Resolución</th>
                        <th>Estado</th>
                        <th>Creado por</th>
                        <th>Creado</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$historial): ?>
                        <tr><td colspan="9">No hay registros.</td></tr>
                    <?php else: foreach ($historial as $h): ?>
                        <tr>
                            <td><?php echo (int) $h['id']; ?></td>
                            <td><?php echo htmlspecialchars((string) $h['cai']); ?></td>
                            <td><?php echo htmlspecialchars((string) $h['prefijo_factura']); ?></td>
                            <td><?php echo (int) $h['rango_inicial']; ?> - <?php echo (int) $h['rango_final']; ?></td>
                            <td><?php echo htmlspecialchars((string) ($h['fecha_limite'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($h['resolucion_sar'] ?? '')); ?></td>
                            <td>
                                <span class="cai-badge <?php echo ((int) $h['activa'] === 1) ? 'on' : 'off'; ?>">
                                    <?php echo ((int) $h['activa'] === 1) ? 'Activa' : 'Histórica'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($h['created_by'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars((string) ($h['created_at'] ?? '')); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/vendor/datatables/dataTables.min.js"></script>
<script src="../../backend/vendor/datatables/dataTables.bootstrap.min.js"></script>
<script src="../../backend/vendor/datatables/buttons.min.js"></script>
<script src="../../backend/vendor/datatables/jszip.min.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/vendor/datatables/html5.min.js"></script>
<script src="../../backend/vendor/datatables/buttons.print.min.js"></script>
<script type="text/javascript">
$(document).ready(function () {
    var $t = $('#table_cai_historial');
    if (!$t.length || $t.find('tbody tr td[colspan]').length > 0) {
        return;
    }
    if ($.fn.DataTable && $.fn.DataTable.isDataTable($t)) {
        $t.DataTable().destroy();
    }
    $t.DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50], [10, 25, 50]],
        order: [[0, 'desc']],
        responsive: false,
        autoWidth: false,
        dom: '<"sv-dt-toolbar-row"Bf>rtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        scrollX: true,
        language: {
            processing: 'Procesando...',
            lengthMenu: 'Mostrar _MENU_ registros',
            zeroRecords: 'Ningun dato disponible',
            emptyTable: 'Ningun dato disponible',
            info: 'Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros',
            infoEmpty: 'Mostrando registros del 0 al 0 de un total de 0 registros',
            infoFiltered: '(filtrado de un total de _MAX_ registros)',
            infoPostFix: '',
            search: 'Buscar:',
            infoThousands: ',',
            loadingRecords: 'Cargando...',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            },
            aria: {
                sortAscending: ': Activar para ordenar la columna de manera ascendente',
                sortDescending: ': Activar para ordenar la columna de manera descendente'
            },
            buttons: {
                copy: 'Copiar',
                colvis: 'Visibilidad'
            }
        },
        initComplete: function () {
            this.api().columns.adjust();
        }
    });
});
</script>
</body>
</html>
