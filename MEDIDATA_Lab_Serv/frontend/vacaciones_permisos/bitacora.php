<?php
require_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    header('Location: mis_vacaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA - BITÁCORA DE AUDITORÍA</title>
    <style>
        .vp-json { font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-word; max-width: 320px; color:#444; }
    </style>
</head>
<body>
    <?php include 'menu_router.php'; ?>
    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>
            <div class="vp-panel">
                <p class="vp-muted">
                    Registro inmutable de acciones sobre información sensible (solicitudes, saldos y configuración).
                    Este historial no puede modificarse ni eliminarse y solo es visible para RRHH / Administración.
                </p>
                <table id="tabla-bitacora" class="responsive-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>Registro</th>
                            <th>Valor anterior</th>
                            <th>Valor nuevo</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script>
    function vpEsc(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    // Muestra JSON de forma legible; si no es JSON, lo deja tal cual.
    function fmtVal(v) {
        if (v === null || v === undefined || v === '') return '—';
        try {
            var o = JSON.parse(v);
            return '<div class="vp-json">' + vpEsc(JSON.stringify(o, null, 1)) + '</div>';
        } catch (e) {
            return '<div class="vp-json">' + vpEsc(v) + '</div>';
        }
    }

    $(document).ready(function() {
        $('#tabla-bitacora').DataTable({
            ajax: { url: '../../backend/registros/vacaciones_permisos/fetch_bitacora.php', dataSrc: 'data' },
            columns: [
                { data: 'fecha_hora' },
                { data: 'usuario' },
                { data: 'accion' },
                { data: 'tabla' },
                { data: 'record_id', className: 'vp-col-center', defaultContent: '—' },
                { data: 'old_value', render: function(d) { return fmtVal(d); } },
                { data: 'new_value', render: function(d) { return fmtVal(d); } }
            ],
            order: [[0, 'desc']],
            dom: 'Bfrtip',
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            buttons: [
                { extend: 'copy',  className: 'button', text: 'Copiar' },
                { extend: 'csv',   className: 'button', text: 'CSV' },
                { extend: 'excel', className: 'button', text: 'Excel' },
                { extend: 'pdf',   className: 'button', text: 'PDF', orientation: 'landscape', pageSize: 'LETTER' },
                { extend: 'print', className: 'button', text: 'Imprimir' }
            ],
            language: {
                processing: 'Cargando...', lengthMenu: 'Mostrar _MENU_ registros',
                zeroRecords: 'No se encontraron resultados', emptyTable: 'No hay registros de auditoría.',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros totales)', search: 'Buscar:',
                paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
            }
        });
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
