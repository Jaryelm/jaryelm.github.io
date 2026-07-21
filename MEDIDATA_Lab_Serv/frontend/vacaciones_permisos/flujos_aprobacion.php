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
    <title>MEDIDATA - FLUJOS Aprobaci&oacute;n</title>
        <style>
        .swal2-cancel-custom-color { color: #333 !important; font-size: 15px !important; padding: 10px 24px !important; }
        .swal2-confirm-custom { font-size: 15px !important; padding: 10px 24px !important; }
        .sortable-ghost { opacity: 0.4; background-color: #e8f4f8 !important; border-left: 4px solid #06adbf !important; }
        .step-target:focus, .step-val:focus { border-color: #06adbf !important; box-shadow: 0 0 5px rgba(6, 173, 191, 0.3); }
        .drag-handle:active { cursor: grabbing !important; }
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
            <div style="display:flex; justify-content:flex-end; align-items:center; margin-bottom: 20px;">
                <button class="btn" style="background:#06adbf;color:white;border:none;padding:10px 15px;border-radius:5px;cursor:pointer;" onclick="agregarFlujo()">Agregar Flujo</button>
            </div>
            <div style="background:#fff; padding:20px; border-radius:8px;">
                <table id="tabla-flujos" class="responsive-table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Descripci&oacute;n</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>

    <script>
        let tablaFlujos;
        $(document).ready(function() {
            tablaFlujos = $('#tabla-flujos').DataTable({
                ajax: {
                    url: '../../backend/registros/vacaciones_permisos/fetch_workflows.php',
                    dataSrc: function(json) {
                        if (json.error) {
                            console.error(json.error);
                            return [];
                        }
                        return json;
                    }
                },
                columns: [
                    { data: 'workflow_id' },
                    { data: 'name' },
                    { data: 'description' },
                                                            { 
                        data: 'status',
                        render: function(data, type, row) {
                            const isChecked = data == 1 ? 'checked' : '';
                            return `<label class="switch" title="Cambiar Estado">
                                        <input type="checkbox" onchange="toggleStatus(${row.workflow_id}, ${data})" ${isChecked}>
                                        <span class="slider"></span>
                                    </label>`;
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `<a title="Editar" href="#" class="fa fa-pencil tooltip rrhh-icon-edit" onclick='editarFlujo(${JSON.stringify(row).replace(/'/g, "&#39;")}); return false;' style="font-size:18px; margin-left: 10px;"></a>` + 
                                   `<a title="Configurar Pasos" href="#" class="fa fa-cogs tooltip rrhh-icon-edit" onclick="configurarPasos(${row.workflow_id}, '${(row.name || '').replace(/'/g, "\\'")}'); return false;" style="font-size:18px; margin-left: 10px;"></a>`;
                        }
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                }
            });
        });

        function agregarFlujo() {
            Swal.fire({
                title: 'Agregar Flujo',
                html: `
                    <div class="form-group" style="text-align: left; margin-bottom: 10px;">
                        <label>Nombre del Flujo</label>
                        <input type="text" id="workflow_name" class="swal2-input" style="width: 90%; margin: 5px auto; display: block;" placeholder="Ej. Aprobaci&oacute;n Regular">
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label>Descripción</label>
                        <textarea id="workflow_desc" class="swal2-textarea" style="width: 90%; margin: 5px auto; display: block;" placeholder="Descripción..."></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const name = $('#workflow_name').val();
                    const desc = $('#workflow_desc').val();
                    if (!name) {
                        Swal.showValidationMessage('El nombre es requerido');
                    }
                    return { name: name, description: desc };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    guardarFlujo(result.value);
                }
            });
        }

        function editarFlujo(row) {
            Swal.fire({
                title: 'Editar Flujo',
                html: `
                    <input type="hidden" id="workflow_id" value="${row.workflow_id}">
                    <div class="form-group" style="text-align: left; margin-bottom: 10px;">
                        <label>Nombre del Flujo</label>
                        <input type="text" id="workflow_name" class="swal2-input" style="width: 90%; margin: 5px auto; display: block;" value="${row.name}">
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label>Descripción</label>
                        <textarea id="workflow_desc" class="swal2-textarea" style="width: 90%; margin: 5px auto; display: block;">${row.description || ''}</textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const id = $('#workflow_id').val();
                    const name = $('#workflow_name').val();
                    const desc = $('#workflow_desc').val();
                    if (!name) {
                        Swal.showValidationMessage('El nombre es requerido');
                    }
                    return { id: id, name: name, description: desc };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    guardarFlujo(result.value);
                }
            });
        }

        function guardarFlujo(data) {
            $.ajax({
                url: '../../backend/registros/vacaciones_permisos/save_workflow.php',
                type: 'POST',
                dataType: 'json',
                data: data,
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Éxito', 'El flujo ha sido guardado', 'success');
                        tablaFlujos.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', res.message || 'Error al guardar', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de comunicación', 'error');
                }
            });
        }

        function toggleStatus(id, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            const actionText = currentStatus == 1 ? 'desactivar' : 'activar';
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: 'Deseas ' + actionText + ' este flujo?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '../../backend/registros/vacaciones_permisos/toggle_workflow.php',
                        type: 'POST',
                        dataType: 'json',
                        data: { id: id, status: newStatus },
                        success: function(res) {
                            if (res.success) {
                                tablaFlujos.ajax.reload(null, false);
                            } else {
                                Swal.fire('Error', res.message || 'Error al cambiar estado', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Error de comunicación', 'error');
                        }
                    });
                }
            });
        }
                                            let globalUsersList = [];
        
        function configurarPasos(id, name) {
            $.getJSON('../../backend/registros/vacaciones_permisos/fetch_workflow_steps.php?id=' + id, function(res) {
                globalUsersList = res.users || [];
                let stepsHtml = '';
                if(res.data) {
                    res.data.forEach(function(step, index) {
                        let val = step.approver_type === 'Specific_Role' ? (step.approver_role_name || '') : 
                                  (step.approver_type === 'Specific_User' ? (step.approver_user_id || '') : '');
                        stepsHtml += getStepRowHtml(step.approver_type, val);
                    });
                }
                
                Swal.fire({
                    title: 'Secuencia de Aprobación',
                    html: `
                          <div style="text-align:center; margin-bottom:15px; color:#555; font-size:14px;">
                              Flujo: <b>${name}</b><br>
                              <small>Arrastra los pasos desde el icono izquierdo para reordenarlos.</small>
                          </div>
                        <div id="steps-container" style="text-align: left; max-height: 350px; overflow-y: auto; padding: 10px; background: #f4f6f9; border-radius: 8px; border: 1px solid #e0e0e0; min-height: 100px;">
                            ${stepsHtml}
                        </div>
                        <button class="btn" onclick="addStepRow()" style="margin-top: 15px; background: #06adbf; color: #fff; padding: 10px 20px; border-radius: 5px; font-weight: 600; font-size: 14px; border: none; box-shadow: 0 2px 4px rgba(6, 173, 191, 0.2);"><i class='bx bx-plus'></i> Añadir Paso</button>
                    `,
                    width: '700px',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar Pasos',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#06adbf',
                    cancelButtonColor: '#e0e0e0',
                    customClass: {
                        cancelButton: 'swal2-cancel-custom-color',
                        confirmButton: 'swal2-confirm-custom'
                    },
                    didOpen: () => {
                        if (typeof Sortable === 'undefined') {
                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
                            script.onload = () => initSortable();
                            document.head.appendChild(script);
                        } else {
                            initSortable();
                        }
                    },
                    preConfirm: () => {
                        let valid = true;
                        const steps = [];
                        $('#steps-container .step-row').each(function(index) {
                            const target = $(this).find('.step-target').val();
                            let type = target;
                            let val = '';
                            
                            if (target === 'Specific_User') {
                                val = $(this).find('.step-val-user').val();
                                if(!val) {
                                    valid = false;
                                    Swal.showValidationMessage('Debe seleccionar un empleado para el paso ' + (index + 1));
                                }
                            } else if (target === 'Specific_Role') {
                                val = $(this).find('.step-val-text').val();
                                if(!val) {
                                    valid = false;
                                    Swal.showValidationMessage('Debe ingresar un rol para el paso ' + (index + 1));
                                }
                            } else if (target === 'Role_Recursos_Humanos') {
                                type = 'Specific_Role';
                                val = 'Recursos_Humanos';
                            } else if (target === 'Role_Administrador') {
                                type = 'Specific_Role';
                                val = 'Administrador';
                            }
                            
                            if(type) steps.push({ order: index + 1, type: type, value: val });
                        });
                        
                        if (!valid) {
                            return false;
                        }
                        
                        return { workflow_id: id, steps: steps };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        guardarPasos(result.value);
                    }
                });
            }).fail(function() {
                Swal.fire('Error', 'Error al cargar los pasos del flujo desde el servidor', 'error');
            });
        }
        
        function initSortable() {
            const el = document.getElementById('steps-container');
            if(el) {
                new Sortable(el, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost'
                });
            }
        }
        
        function getStepRowHtml(type = '', val = '') {
            const isHR = (type === 'Specific_Role' && val === 'Recursos_Humanos');
            const isAdmin = (type === 'Specific_Role' && val === 'Administrador');
            const isOtherRole = (type === 'Specific_Role' && !isHR && !isAdmin);
            
            let userOptions = '<option value="">-- Seleccionar --</option>';
            globalUsersList.forEach(u => {
                const sel = (val == u.id) ? 'selected' : '';
                userOptions += `<option value="${u.id}" ${sel}>${u.name} (${u.rol})</option>`;
            });
            
            return `
                  <div class="step-row" style="display:flex; gap:10px; margin-bottom:12px; align-items:center; background:#fff; padding:10px 15px; border-radius:8px; box-shadow:0 2px 5px rgba(0,0,0,0.05); border-left:4px solid #06adbf; transition: all 0.2s ease; height: 58px; box-sizing: border-box;">
                      <div class="drag-handle" style="cursor:grab; color:#999; font-size:24px; padding-right:5px; display:flex; align-items:center;" title="Arrastrar para ordenar">
                          <i class='bx bx-menu'></i>
                      </div>
                      <div style="flex-grow: 1; display:flex; gap:10px; flex-wrap: nowrap; align-items:center; justify-content:center;">
                          <select class="step-target rrhh-no-margin" style="flex:1; min-width:160px; height: 34px; padding:4px 8px; border:1px solid #ccc; border-radius:4px; font-size:14px; outline:none; box-sizing:border-box; vertical-align:middle;" onchange="toggleStepVal(this)">
                              <option value="Direct_Manager" ${type=='Direct_Manager'?'selected':''}>Jefe Inmediato</option>
                              <option value="Role_Recursos_Humanos" ${isHR?'selected':''}>Recursos Humanos</option>
                              <option value="Role_Administrador" ${isAdmin?'selected':''}>Administrador</option>
                              <option value="Department_Manager" ${type=='Department_Manager'?'selected':''}>Jefe de Departamento</option>
                              <option value="Specific_Role" ${isOtherRole?'selected':''}>Otro Rol...</option>
                              <option value="Specific_User" ${type=='Specific_User'?'selected':''}>Empleado Específico</option>
                          </select>
                          <select class="step-val step-val-user rrhh-no-margin" style="flex:1; min-width:120px; height: 34px; padding:4px 8px; border:1px solid #ccc; border-radius:4px; font-size:14px; outline:none; box-sizing:border-box; vertical-align:middle; display:${type=='Specific_User'?'block':'none'};">
                              ${userOptions}
                          </select>
                          <input type="text" class="step-val step-val-text rrhh-no-margin" placeholder="Ej. Encargado Compras" value="${type=='Specific_User' ? '' : val}" style="flex:1; min-width:120px; height: 34px; padding:4px 8px; border:1px solid #ccc; border-radius:4px; font-size:14px; outline:none; box-sizing:border-box; vertical-align:middle; display:${isOtherRole?'block':'none'};">
                      </div>
                      <i class='bx bx-trash' style="color:#b02a37; font-size:22px; cursor:pointer; padding:5px; display:flex; align-items:center;" onclick="$(this).closest('.step-row').remove();" title="Eliminar Paso"></i>
                  </div>
            `;
        }
        
        function addStepRow() {
            $('#steps-container').append(getStepRowHtml());
            const container = $('#steps-container');
            container.scrollTop(container[0].scrollHeight);
        }
        
        function toggleStepVal(selectObj) {
            const val = $(selectObj).val();
            const inputUser = $(selectObj).siblings('.step-val-user');
            const inputText = $(selectObj).siblings('.step-val-text');
            if(val === 'Specific_Role') {
                inputUser.hide();
                inputText.show();
            } else if (val === 'Specific_User') {
                inputText.hide();
                inputUser.show();
            } else {
                inputUser.hide();
                inputText.hide();
            }
        }
        
        function guardarPasos(data) {
            $.ajax({
                url: '../../backend/registros/vacaciones_permisos/save_workflow_steps.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Éxito', 'Pasos guardados', 'success');
                    } else {
                        Swal.fire('Error', res.message || 'Error al guardar', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de comunicación', 'error');
                }
            });
        }
</script>
<script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>













