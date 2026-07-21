<?php
require_once '../../backend/registros/session_check.php';

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
    <title>MEDIDATA - APROBACIONES</title>
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
                <table id="tabla-aprobaciones" class="responsive-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Solicitante</th>
                            <th>Tipo de Ausencia</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Días</th>
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
        $('#tabla-aprobaciones').DataTable({
            ajax: {
                url: '../../backend/registros/vacaciones_permisos/fetch_mis_aprobaciones.php',
                type: 'GET'
            },
            columns: [
                { data: 'request_id' },
                { data: 'user_name' },
                { data: 'type_name' },
                { data: 'start_date' },
                { data: 'end_date' },
                { data: 'days_amount' },
                { data: 'created_at' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <button class="btn btn-sm" style="background:#28a745;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;" onclick="processApproval(${row.request_id}, 'Approve')" title="Aprobar"><i class='bx bx-check'></i> Aprobar</button>
                            <button class="btn btn-sm" style="background:#dc3545;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;margin-left:5px;" onclick="processApproval(${row.request_id}, 'Reject')" title="Rechazar"><i class='bx bx-x'></i> Rechazar</button>
                        `;
                    }
                }
            ],
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json" },
            responsive: true
        });
    });

    function processApproval(id, action) {
        let actionText = action === 'Approve' ? 'aprobar' : 'rechazar';
        Swal.fire({
            title: `¿Desea ${actionText} esta solicitud?`,
            input: 'textarea',
            inputLabel: 'Comentarios (opcional)',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            preConfirm: (comments) => {
                return $.ajax({
                    url: '../../backend/registros/vacaciones_permisos/approve_request.php',
                    type: 'POST',
                    data: { request_id: id, action: action, comments: comments },
                    dataType: 'json'
                }).then(response => {
                    if (!response.success) {
                        throw new Error(response.message || 'Error al procesar');
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Error: ${error.message}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Procesado', 'La solicitud ha sido procesada.', 'success');
                $('#tabla-aprobaciones').DataTable().ajax.reload();
            }
        });
    }
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
