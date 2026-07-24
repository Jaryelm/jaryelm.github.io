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
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA - FLUJOS DE APROBACIÓN</title>
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
                <button class="button" onclick="agregarFlujo()">
                    <i class='bx bx-plus'></i> Agregar Flujo
                </button>
            </div>
            <div class="vp-panel">
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

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
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
                    paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
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
                        <div id="steps-container">
                            ${stepsHtml}
                        </div>
                        <button class="vp-add-step-btn" onclick="addStepRow()"><i class='bx bx-plus'></i> Añadir Paso</button>
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
                  <div class="step-row">
                      <div class="drag-handle" title="Arrastrar para ordenar">
                          <i class='bx bx-menu'></i>
                      </div>
                      <div class="step-fields">
                          <select class="step-target rrhh-no-margin" onchange="toggleStepVal(this)">
                              <option value="Direct_Manager" ${type=='Direct_Manager'?'selected':''}>Jefe Inmediato</option>
                              <option value="Role_Recursos_Humanos" ${isHR?'selected':''}>Recursos Humanos</option>
                              <option value="Role_Administrador" ${isAdmin?'selected':''}>Administrador</option>
                              <option value="Department_Manager" ${type=='Department_Manager'?'selected':''}>Jefe de Departamento</option>
                              <option value="Specific_Role" ${isOtherRole?'selected':''}>Otro Rol...</option>
                              <option value="Specific_User" ${type=='Specific_User'?'selected':''}>Empleado Específico</option>
                          </select>
                          <select class="step-val step-val-user rrhh-no-margin" style="display:${type=='Specific_User'?'block':'none'};">
                              ${userOptions}
                          </select>
                          <input type="text" class="step-val step-val-text rrhh-no-margin" placeholder="Ej. Encargado Compras" value="${type=='Specific_User' ? '' : val}" style="display:${isOtherRole?'block':'none'};">
                      </div>
                      <i class='bx bx-trash step-del' onclick="$(this).closest('.step-row').remove();" title="Eliminar Paso"></i>
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
















