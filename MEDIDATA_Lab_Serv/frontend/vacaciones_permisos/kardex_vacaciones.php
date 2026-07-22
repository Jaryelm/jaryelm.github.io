<?php
require_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    header('Location: mis_vacaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <title>MEDIDATA - KARDEX VACACIONES</title>
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
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos D铆as" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>
            <div style="background:#fff; padding:20px; border-radius:8px;">
                <table id="tabla-kardex" class="responsive-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID Transacci贸n</th>
                            <th>Empleado</th>
                            <th>Tipo Transacci贸n</th>
                            <th>D铆as Afectados</th>
                            <th>Saldo Resultante</th>
                            <th>Referencia</th>
                            <th>Fecha</th>
                            <th>Comentarios</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>
    <script src="../../backend/js/script.js"></script>
    <script>
    $(document).ready(function() {
        $('#tabla-kardex').DataTable({
            ajax: {
                url: '../../backend/registros/vacaciones_permisos/fetch_kardex_global.php',
                type: 'GET'
            },
            columns: [
                { data: 'transaction_id' },
                { data: 'user_name' },
                { 
                    data: 'transaction_type',
                    render: function(data) {
                        if(data === 'Accrual') return `<span style="color:#28a745;font-weight:bold;">+ Acreditaci贸n</span>`;
                        if(data === 'Usage') return `<span style="color:#dc3545;font-weight:bold;">- Deducci贸n</span>`;
                        if(data === 'Adjustment') return `<span style="color:#ffc107;font-weight:bold;">卤 Ajuste</span>`;
                        return data;
                    }
                },
                { 
                    data: 'days_amount',
                    render: function(data, type, row) {
                        return row.transaction_type === 'Usage' ? `-${data}` : `+${data}`;
                    }
                },
                { data: 'balance_after' },
                { data: 'reference_type' },
                { data: 'created_at' },
                { data: 'comments' }
            ],
                            dom: 'Bfrtip',
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                buttons: [
                    { extend: 'copy', className: 'button' },
                    { extend: 'csv', className: 'button' },
                    { extend: 'excel', className: 'button' },
                    { extend: 'print', className: 'button' }
                ],
                language: {
                    processing: 'Cargando...',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    zeroRecords: 'No se encontraron resultados',
                    emptyTable: 'No hay datos disponibles.',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros totales)',
                    search: 'Buscar:',
                    paginate: { first: 'Primero', last: '趌timo', next: 'Siguiente', previous: 'Anterior' }
                },
            responsive: true,
            order: [[6, 'desc']] // Ordenar por fecha reciente
        });
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>



