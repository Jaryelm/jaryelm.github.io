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
                            <th>Departamentos</th>
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
                        data: 'departments_label',
                        render: function(data) {
                            return data && data.length ? data : '<span style="color:#999;">Sin asignar</span>';
                        }
                    },
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

        // El flujo se asigna por DEPARTAMENTO del solicitante: cada departamento puede
        // tener a lo sumo un flujo y el paso "Jefe de Departamento" lo resuelve el jefe
        // asignado en la vista "Jefes de Departamento".
        function cargarDepartamentos() {
            return $.getJSON('../../backend/registros/vacaciones_permisos/fetch_departamentos_jefes.php')
                .then(res => res.data || []);
        }

        function deptOptionsHtml(departamentos, seleccionados) {
            const sel = (seleccionados || []).map(Number);
            return departamentos.map(d => {
                const marcado = sel.includes(Number(d.id)) ? 'selected' : '';
                const jefe = d.jefe_nombre ? ` (Jefe: ${d.jefe_nombre})` : ' (sin jefe asignado)';
                return `<option value="${d.id}" ${marcado}>${d.name}${jefe}</option>`;
            }).join('');
        }

        function abrirModalFlujo(titulo, row, departamentos) {
            const selDeps = row ? (row.departments || []).map(d => d.id) : [];
            Swal.fire({
                title: titulo,
                html: `
                    ${row ? `<input type="hidden" id="workflow_id" value="${row.workflow_id}">` : ''}
                    <div class="form-group" style="text-align: left; margin-bottom: 10px;">
                        <label>Nombre del Flujo</label>
                        <input type="text" id="workflow_name" class="swal2-input" style="width: 90%; margin: 5px auto; display: block;" placeholder="Ej. Aprobaci&oacute;n Regular" value="${row ? (row.name || '').replace(/"/g, '&quot;') : ''}">
                    </div>
                    <div class="form-group" style="text-align: left; margin-bottom: 10px;">
                        <label>Descripción</label>
                        <textarea id="workflow_desc" class="swal2-textarea" style="width: 90%; margin: 5px auto; display: block;" placeholder="Descripción...">${row ? (row.description || '') : ''}</textarea>
                    </div>
                    <div class="form-group" style="text-align: left;">
                        <label>Departamentos que usan este flujo</label>
                        <select id="workflow_depts" multiple size="8" class="swal2-select" style="width: 90%; margin: 5px auto; display: block;">
                            ${deptOptionsHtml(departamentos, selDeps)}
                        </select>
                        <small style="display:block; color:#777; margin-top:4px;">
                            Mantén presionada la tecla Ctrl para seleccionar varios. Un departamento
                            solo puede pertenecer a un flujo; el aprobador "Jefe de Departamento" es
                            el jefe asignado a cada departamento.
                        </small>
                    </div>
                `,
                width: '640px',
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                preConfirm: () => {
                    const name = $('#workflow_name').val();
                    const desc = $('#workflow_desc').val();
                    const depts = ($('#workflow_depts').val() || []).map(Number);
                    if (!name) {
                        Swal.showValidationMessage('El nombre es requerido');
                        return false;
                    }
                    const payload = { name: name, description: desc, department_ids: depts };
                    if (row) payload.id = $('#workflow_id').val();
                    return payload;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    guardarFlujo(result.value);
                }
            });
        }

        function agregarFlujo() {
            cargarDepartamentos()
                .then(deps => abrirModalFlujo('Agregar Flujo', null, deps))
                .catch(() => Swal.fire('Error', 'No se pudo cargar la lista de departamentos', 'error'));
        }

        function editarFlujo(row) {
            cargarDepartamentos()
                .then(deps => abrirModalFlujo('Editar Flujo', row, deps))
                .catch(() => Swal.fire('Error', 'No se pudo cargar la lista de departamentos', 'error'));
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
        function configurarPasos(id, name) {
            $.getJSON('../../backend/registros/vacaciones_permisos/fetch_workflow_steps.php?id=' + id, function(res) {
                let stepsHtml = '';
                if(res.data) {
                    res.data.forEach(function(step) {
                        let val = step.approver_type === 'Specific_Role' ? (step.approver_role_name || '') : '';
                        stepsHtml += getStepRowHtml(step.approver_type, val);
                    });
                }

                Swal.fire({
                    title: 'Secuencia de Aprobación',
                    html: `
                          <div style="text-align:center; margin-bottom:15px; color:#555; font-size:14px;">
                              Flujo: <b>${name}</b><br>
                              <small>Arrastra los pasos desde el icono izquierdo para reordenarlos.
                              El paso "Jefe de Departamento" lo resuelve el jefe asignado al
                              departamento del solicitante.</small>
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
                        const steps = [];
                        $('#steps-container .step-row').each(function(index) {
                            const target = $(this).find('.step-target').val();
                            let type = target;
                            let val = '';

                            if (target === 'Role_Recursos_Humanos') {
                                type = 'Specific_Role';
                                val = 'Recursos_Humanos';
                            } else if (target === 'Role_Administrador') {
                                type = 'Specific_Role';
                                val = 'Administrador';
                            }

                            if(type) steps.push({ order: index + 1, type: type, value: val });
                        });

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
            const isDeptManager = !isHR && !isAdmin; // por defecto: Jefe de Departamento

            return `
                  <div class="step-row">
                      <div class="drag-handle" title="Arrastrar para ordenar">
                          <i class='bx bx-menu'></i>
                      </div>
                      <div class="step-fields">
                          <select class="step-target rrhh-no-margin">
                              <option value="Department_Manager" ${isDeptManager?'selected':''}>Jefe de Departamento (del solicitante)</option>
                              <option value="Role_Recursos_Humanos" ${isHR?'selected':''}>Recursos Humanos</option>
                              <option value="Role_Administrador" ${isAdmin?'selected':''}>Administrador</option>
                          </select>
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
















