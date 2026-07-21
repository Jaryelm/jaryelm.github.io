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
            <div style="display:flex; justify-content:flex-end; align-items:center; margin-bottom: 20px;">
                <button class="btn" style="background:#06adbf;color:white;border:none;padding:10px 15px;border-radius:5px;cursor:pointer;" onclick="openPolicyModal()">Agregar Política</button>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px;">
                <table id="tabla-Pol&iacute;ticas" class="responsive-table display" style="width:100%">
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
    let tablaPol&iacute;ticas;
    $(document).ready(function() {
        tablaPol&iacute;ticas = $('#tabla-Pol&iacute;ticas').DataTable({
            ajax: {
                url: '../../backend/registros/vacaciones_permisos/fetch_Pol&iacute;ticas.php',
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
                        return data === 'ACTIVE' 
                            ? '<span class="badge" style="background:#28a745;color:white;padding:3px 8px;border-radius:4px;font-size:12px;">Activo</span>' 
                            : '<span class="badge" style="background:#dc3545;color:white;padding:3px 8px;border-radius:4px;font-size:12px;">Inactivo</span>';
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        let toggleIcon = row.status === 'ACTIVE' ? 'bx-toggle-right' : 'bx-toggle-left';
                        let escapedRow = JSON.stringify(row).replace(/'/g, "&#39;").replace(/"/g, "&quot;");
                        return `
                            <button class="btn btn-sm" style="background:#06adbf;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;" onclick="openPolicyModal(${escapedRow})"><i class='bx bx-edit-alt'></i></button>
                            <button class="btn btn-sm" style="background:#035c67;color:white;border:none;padding:5px 10px;border-radius:4px;cursor:pointer;margin-left:5px;" onclick="togglePolicy(${row.policy_id})"><i class='bx ${toggleIcon}'></i></button>
                        `;
                    }
                }
            ],
            language: { url: "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json" },
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
                        <option value="ACTIVE" ${isEdit && policy.status === 'ACTIVE' ? 'selected' : ''}>Activo</option>
                        <option value="INACTIVE" ${isEdit && policy.status === 'INACTIVE' ? 'selected' : ''}>Inactivo</option>
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
                tablaPol&iacute;ticas.ajax.reload(null, false);
            }
        });
    }

    function togglePolicy(id) {
        Swal.fire({
            title: '¿Cambiar estado?',
            text: 'El estado de la política cambiará',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../backend/registros/vacaciones_permisos/toggle_politica.php',
                    type: 'POST',
                    data: { policy_id: id },
                    dataType: 'json',
                    success: function(response) {
                        if(response.success) {
                            tablaPol&iacute;ticas.ajax.reload(null, false);
                            Swal.fire('Actualizado', 'El estado ha sido actualizado', 'success');
                        } else {
                            Swal.fire('Error', response.message || 'Error al actualizar', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'No se pudo procesar la solicitud', 'error');
                    }
                });
            }
        });
    }
    </script>
</body>
</html>






