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
    <title>MEDIDATA - Pol&iacute;ticas VACACIONES</title>
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
            <div class="page-actions">
                <button class="button" onclick="openPolicyModal()">
                    <i class='bx bx-plus'></i> Agregar Política
                </button>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px;">
                <table id="tabla-politicas" class="responsive-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Años Mín.</th>
                            <th>Años Máx.</th>
                            <th>Días Otorgados</th>
                            <th>Máx. Acumulables</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
    <script>
    let tablaPoliticas;
    $(document).ready(function() {
        tablaPoliticas = $('#tabla-politicas').DataTable({
            ajax: {
                url: '../../backend/registros/vacaciones_permisos/fetch_politicas.php',
                type: 'GET'
            },
            columns: [
                { data: 'policy_id' },
                { data: 'min_seniority_years' },
                { data: 'max_seniority_years' },
                { data: 'granted_days' },
                { data: 'max_accumulated_days' },
                { 
                    data: 'status',
                    render: function(data, type, row) {
                        var checked = data == 1 ? 'checked' : '';
                        return `<label class="switch"><input type="checkbox" class="toggle-status" onchange="togglePolicy(${row.policy_id})" ${checked}><span class="slider round"></span></label>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        let escapedRow = JSON.stringify(row).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
                        return `<button class="btn-edit" onclick="openPolicyModal(${escapedRow})" style="background:none;border:none;color:var(--blue);cursor:pointer;font-size:1.2rem;"><i class='bx bx-edit'></i></button>`;
                    }
                }
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
                    paginate: { first: 'Primero', last: '�ltimo', next: 'Siguiente', previous: 'Anterior' }
                },
            responsive: true
        });
    });

    function openPolicyModal(policy = null) {
        let isEdit = policy !== null;
        let title = isEdit ? 'Editar Política' : 'Agregar Política';
        let html = `
            <form id="policyForm" style="text-align:left; font-size:14px;">
                <input type="hidden" id="policy_id" value="${isEdit ? policy.policy_id : ''}">
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;">Años Mínimos de Antigüedad:</label>
                    <input type="number" id="min_seniority_years" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;" value="${isEdit ? policy.min_seniority_years : ''}" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;">Años Máximos de Antigüedad:</label>
                    <input type="number" id="max_seniority_years" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;" value="${isEdit ? policy.max_seniority_years : ''}" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;">Días Otorgados:</label>
                    <input type="number" id="granted_days" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;" value="${isEdit ? policy.granted_days : ''}" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;">Máximo de Días Acumulables:</label>
                    <input type="number" id="max_accumulated_days" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;" value="${isEdit ? policy.max_accumulated_days : ''}" required>
                </div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;margin-bottom:5px;">Estado:</label>
                    <select id="status" class="form-control" style="width:100%;padding:8px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;">
                        <option value="1" ${isEdit && policy.status == 1 ? 'selected' : ''}>Activo</option>
                        <option value="0" ${isEdit && policy.status == 0 ? 'selected' : ''}>Inactivo</option>
                    </select>
                </div>
            </form>
        `;

        Swal.fire({
            title: title,
            html: html,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            customClass: {
                confirmButton: 'btn swal2-confirm',
                cancelButton: 'btn swal2-cancel'
            },
            preConfirm: () => {
                let data = {
                    policy_id: document.getElementById('policy_id').value,
                    min_seniority_years: document.getElementById('min_seniority_years').value,
                    max_seniority_years: document.getElementById('max_seniority_years').value,
                    granted_days: document.getElementById('granted_days').value,
                    max_accumulated_days: document.getElementById('max_accumulated_days').value,
                    status: document.getElementById('status').value
                };
                if (!data.min_seniority_years || !data.max_seniority_years || !data.granted_days || !data.max_accumulated_days) {
                    Swal.showValidationMessage('Todos los campos numéricos son requeridos');
                    return false;
                }
                return $.ajax({
                    url: '../../backend/registros/vacaciones_permisos/save_politica.php',
                    type: 'POST',
                    data: data,
                    dataType: 'json'
                }).then(response => {
                    if (!response.success) {
                        throw new Error(response.message || 'Error al guardar');
                    }
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Falló la solicitud: ${error}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Guardado', 'La política ha sido guardada', 'success');
                tablaPoliticas.ajax.reload(null, false);
            }
        });
    }

    function togglePolicy(id) {
        $.ajax({
            url: '../../backend/registros/vacaciones_permisos/toggle_politica.php',
            type: 'POST',
            data: { policy_id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    tablaPoliticas.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', response.message || 'Error al cambiar estado.', 'error');
                    tablaPoliticas.ajax.reload(null, false);
                }
            }
        });
    }
    </script>
</body>
</html>













