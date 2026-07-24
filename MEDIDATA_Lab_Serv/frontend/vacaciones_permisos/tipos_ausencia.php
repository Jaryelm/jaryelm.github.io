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
    <title>MEDIDATA - TIPOS DE AUSENCIA</title>
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
                <a href="#" id="btn-agregar" class="button">
                    <i class='bx bx-plus'></i> Agregar Tipo
                </a>
            </div>
            <div class="vp-panel">
                <table id="tabla-tipos" class="responsive-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Pagado</th>
                            <th>Deduce Vac.</th>
                            <th>Req. Doc.</th>
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
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script>
        $(document).ready(function() {
            var table = $('#tabla-tipos').DataTable({
                "ajax": {
                    "url": "../../backend/registros/vacaciones_permisos/fetch_tipos_ausencia.php",
                    "type": "GET",
                    "dataSrc": "data"
                },
                "columns": [
                    { "data": "code" },
                    { "data": "name" },
                    { "data": "category" },
                    { 
                        "data": "is_paid",
                        "render": function(data) {
                            return data == 1 ? '<span class="status completed">Sí</span>' : '<span class="status pending">No</span>';
                        }
                    },
                    { 
                        "data": "deducts_vacation",
                        "render": function(data) {
                            return data == 1 ? '<span class="status completed">Sí</span>' : '<span class="status pending">No</span>';
                        }
                    },
                    { 
                        "data": "requires_document",
                        "render": function(data) {
                            return data == 1 ? '<span class="status completed">Sí</span>' : '<span class="status pending">No</span>';
                        }
                    },
                    { 
                        "data": "status",
                        "render": function(data, type, row) {
                            var checked = data == 1 ? 'checked' : '';
                            return `<label class="switch"><input type="checkbox" class="toggle-status" data-id="${row.type_id}" data-status="${data}" ${checked}><span class="slider round"></span></label>`;
                        }
                    },
                    { 
                        "data": null,
                        "render": function(data, type, row) {
                            return `<button class="btn-edit vp-icon-btn" data-id="${row.type_id}"><i class='bx bx-edit'></i></button>`;
                        }
                    }
                ],
                                "dom": 'Bfrtip',
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                "buttons": [
                    { extend: 'copy', className: 'button' },
                    { extend: 'csv', className: 'button' },
                    { extend: 'excel', className: 'button' },
                    { extend: 'print', className: 'button' }
                ],
                "language": {
                    "processing": 'Cargando...',
                    "lengthMenu": 'Mostrar _MENU_ registros',
                    "zeroRecords": 'No se encontraron resultados',
                    "emptyTable": 'No hay datos disponibles.',
                    "info": 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    "infoEmpty": 'Mostrando 0 a 0 de 0 registros',
                    "infoFiltered": '(filtrado de _MAX_ registros totales)',
                    "search": 'Buscar:',
                    "paginate": { "first": 'Primero', "last": 'Último', "next": 'Siguiente', "previous": 'Anterior' }
                }
            });

            function showForm(data = null) {
                var isEdit = data !== null;
                var html = `
                    <form id="form-tipo" style="text-align: left;">
                        <input type="hidden" id="type_id" value="${isEdit ? data.type_id : ''}">
                        <div class="vp-form-row">
                            <label>Código</label>
                            <input type="text" id="code" class="form-control" value="${isEdit ? data.code : ''}" required>
                        </div>
                        <div class="vp-form-row">
                            <label>Nombre</label>
                            <input type="text" id="name" class="form-control" value="${isEdit ? data.name : ''}" required>
                        </div>
                        <div class="vp-form-row">
                            <label>Categoría</label>
                            <select id="category" class="form-control" required>
                                <option value="Vacation" ${isEdit && data.category === 'Vacation' ? 'selected' : ''}>Vacaciones</option>
                                <option value="Permission" ${isEdit && data.category === 'Permission' ? 'selected' : ''}>Permiso</option>
                                <option value="Medical_Leave" ${isEdit && data.category === 'Medical_Leave' ? 'selected' : ''}>Incapacidad Médica</option>
                                <option value="License" ${isEdit && data.category === 'License' ? 'selected' : ''}>Licencia</option>
                            </select>
                        </div>
                        <div class="vp-form-row">
                            <label class="vp-checkbox-inline"><input type="checkbox" id="is_paid" ${isEdit && data.is_paid == 1 ? 'checked' : ''}> ¿Es pagado?</label>
                        </div>
                        <div class="vp-form-row">
                            <label class="vp-checkbox-inline"><input type="checkbox" id="deducts_vacation" ${isEdit && data.deducts_vacation == 1 ? 'checked' : ''}> ¿Deduce vacaciones?</label>
                        </div>
                        <div class="vp-form-row">
                            <label class="vp-checkbox-inline"><input type="checkbox" id="requires_document" ${isEdit && data.requires_document == 1 ? 'checked' : ''}> ¿Requiere documento?</label>
                        </div>
                        <div class="vp-form-row">
                            <label class="vp-checkbox-inline"><input type="checkbox" id="requires_special_auth" ${!isEdit || data.requires_special_auth == 1 ? 'checked' : ''}> ¿Requiere autorización especial?</label>
                        </div>
                    </form>
                `;

                Swal.fire({
                    title: isEdit ? 'Editar Tipo' : 'Agregar Tipo',
                    html: html,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        return {
                            type_id: $('#type_id').val(),
                            code: $('#code').val(),
                            name: $('#name').val(),
                            category: $('#category').val(),
                            is_paid: $('#is_paid').is(':checked') ? 1 : 0,
                            deducts_vacation: $('#deducts_vacation').is(':checked') ? 1 : 0,
                            requires_document: $('#requires_document').is(':checked') ? 1 : 0,
                            requires_special_auth: $('#requires_special_auth').is(':checked') ? 1 : 0
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post('../../backend/registros/vacaciones_permisos/save_tipo_ausencia.php', result.value, function(res) {
                            if (res.success) {
                                Swal.fire('Guardado', '', 'success');
                                table.ajax.reload();
                            } else {
                                Swal.fire('Error', res.message, 'error');
                            }
                        }, 'json');
                    }
                });
            }

            $('#btn-agregar').click(function(e) {
                e.preventDefault();
                showForm();
            });

            $('#tabla-tipos tbody').on('click', '.btn-edit', function() {
                var data = table.row($(this).parents('tr')).data();
                showForm(data);
            });

            $('#tabla-tipos tbody').on('change', '.toggle-status', function() {
                var id = $(this).data('id');
                var currentStatus = $(this).data('status');
                $.post('../../backend/registros/vacaciones_permisos/toggle_tipo_ausencia.php', { type_id: id, current_status: currentStatus }, function(res) {
                    if (!res.success) {
                        Swal.fire('Error', res.message, 'error');
                        table.ajax.reload();
                    } else {
                        table.ajax.reload();
                    }
                }, 'json');
            });
        });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>








