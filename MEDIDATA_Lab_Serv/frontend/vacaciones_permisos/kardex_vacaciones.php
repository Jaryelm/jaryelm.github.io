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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
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
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>
            <div style="background:#fff; padding:20px; border-radius:8px;">
                <table id="tabla-kardex" class="responsive-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID Transacción</th>
                            <th>Empleado</th>
                            <th>Tipo Transacción</th>
                            <th>Días Afectados</th>
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
                        if(data === 'Accrual') return `<span style="color:#28a745;font-weight:bold;">+ Acreditación</span>`;
                        if(data === 'Usage') return `<span style="color:#dc3545;font-weight:bold;">- Deducción</span>`;
                        if(data === 'Adjustment') return `<span style="color:#ffc107;font-weight:bold;">± Ajuste</span>`;
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
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json" },
            responsive: true,
            order: [[6, 'desc']] // Ordenar por fecha reciente
        });
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
