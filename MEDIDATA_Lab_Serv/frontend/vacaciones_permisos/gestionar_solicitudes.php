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
    <title>MEDIDATA - GESTIONAR SOLICITUDES</title>
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
                <table id="tabla-solicitudes-global" class="responsive-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empleado</th>
                            <th>Tipo de Ausencia</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Días</th>
                            <th>Estado</th>
                            <th>Fecha Solicitud</th>
                            <th>Acciones</th>
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
        $('#tabla-solicitudes-global').DataTable({
            ajax: {
                url: '../../backend/registros/vacaciones_permisos/fetch_solicitudes.php',
                type: 'GET'
            },
            columns: [
                { data: 'request_id' },
                { data: 'user_name' },
                { data: 'type_name' },
                { data: 'start_date' },
                { data: 'end_date' },
                { data: 'days_amount' },
                { 
                    data: 'request_status',
                    render: function(data) {
                        let badgeColor = '#6c757d'; // default
                        let text = data;
                        if(data === 'Pending') { badgeColor = '#ffc107'; text = 'Pendiente'; }
                        else if(data === 'Approved') { badgeColor = '#28a745'; text = 'Aprobada'; }
                        else if(data === 'Rejected') { badgeColor = '#dc3545'; text = 'Rechazada'; }
                        return `<span class="badge" style="background:${badgeColor};color:white;padding:4px 8px;border-radius:4px;font-size:12px;">${text}</span>`;
                    }
                },
                { data: 'created_at' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm" style="background:#06adbf;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;" onclick="viewRequest(${row.request_id})" title="Ver Detalles"><i class='bx bx-show'></i> Ver</button>`;
                    }
                }
            ],
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json" },
            responsive: true,
            order: [[7, 'desc']]
        });
    });

    function viewRequest(id) {
        // En el futuro, abrir modal con historial de aprobación
        Swal.fire('Detalles', 'Visualización de solicitud ID: ' + id, 'info');
    }
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
