/**
 * Lista de Colaboradores - DataTable server-side (paginacion por servidor).
 * Sistema: MEDIDATA - RRHH
 *
 * Configurar antes de incluir este script:
 *   window.MEDIDATA_COLAB_CONFIG = {
 *       ajaxUrl: '../../backend/php/get_colaboradores.php',
 *       estado: '1',            // '1' colaboradores | '0' excolaboradores
 *       variant: '',            // '' admin | 'usr'
 *       deptoMap: { id: 'nombre', ... },
 *       salaryMap: { id: 'etiqueta', ... },
 *       cargoMap: { id: 'nombre', ... }
 *   };
 */
(function ($) {
    'use strict';

    var cfg = window.MEDIDATA_COLAB_CONFIG || {};
    var deptoMap = cfg.deptoMap || {};
    var salaryMap = cfg.salaryMap || {};
    var cargoMap = cfg.cargoMap || {};
    var variant = cfg.variant || '';

    // Metadatos por tabla de origen (nombres de campo reales + columna ID + form de edicion).
    var FIELD_MAP = {
        staff_administrative: {
            label: 'Administrativo', idcol: 'idadm',
            f_nombres: 'nomadm', f_apellidos: 'apeadm', f_sexo: 'sexadm', f_ident: 'numide', f_nac: 'nacadm',
            edit: variant === 'usr' ? 'editar_colaborador_usr.php' : 'editar_colaborador.php'
        },
        doctor: {
            label: 'Médico', idcol: 'idodc',
            f_nombres: 'nodoc', f_apellidos: 'apdoc', f_sexo: 'sexd', f_ident: 'ceddoc', f_nac: 'nacd',
            edit: variant === 'usr' ? 'editar_colaborador_usr.php' : 'editar_colaborador.php'
        },
        nurse: {
            label: 'Enfermería', idcol: 'idnur',
            f_nombres: 'nomnur', f_apellidos: 'apenur', f_sexo: 'sexnur', f_ident: 'numide', f_nac: 'nacinur',
            edit: variant === 'usr' ? 'editar_colaborador_usr.php' : 'editar_colaborador.php'
        },
        staff_general_services: {
            label: 'Servicios Generales', idcol: 'idsg',
            f_nombres: 'nomsg', f_apellidos: 'apesg', f_sexo: 'sexsg', f_ident: 'numide', f_nac: 'nacsg',
            edit: variant === 'usr' ? 'editar_colaborador_usr.php' : 'editar_colaborador.php'
        },
        staff_medifarma: {
            label: 'Medifarma', idcol: 'idmf',
            f_nombres: 'nommf', f_apellidos: 'apemf', f_sexo: 'sexmf', f_ident: 'numide', f_nac: 'nacmf',
            edit: variant === 'usr' ? 'editar_colaborador_usr.php' : 'editar_colaborador.php'
        },
        // Cuentas de usuario (login): se muestran como SOLO LECTURA. Se gestionan en "Usuarios Registrados".
        users: {
            label: 'Usuario', idcol: 'id',
            f_nombres: 'name', f_apellidos: '', f_sexo: 'sexo', f_ident: 'cedula', f_nac: '',
            edit: '../usuarios/editar_user.php'
        }
    };

    function isUser(row) { return row.source_table === 'users'; }
    
    // Etiqueta de categoría para cuentas de usuario = su rol (guiones -> espacios, con acentos).
    function formatRol(rol) {
        if (!rol) { return 'Usuario'; }
        var r = String(rol);
        if (r.indexOf('Recursos') !== -1) { return 'Recursos Humanos'; }
        if (r.indexOf('Almac') !== -1) { return 'Almacén'; }
        if (r.indexOf('Radiolog') !== -1) { return 'Radiólogo'; }
        if (r.indexOf('Facturaci') !== -1) { return 'Facturación'; }
        return r.replace(/_/g, ' ');
    }

    // Celda de solo lectura (para filas de usuarios).
    function readonlyCell(value, tag) {
        tag = tag || 'td';
        var display = (value === null || value === undefined || value === '') ? '—' : esc(value);
        return '<' + tag + ' style="color:#555;">' + display + '</' + tag + '>';
    }

    function esc(text) {
        if (text === null || text === undefined) return '';
        return $('<div>').text(text).html();
    }

    function escAttr(text) {
        return esc(text).replace(/"/g, '&quot;');
    }

    function editableCell(row, field, value, tag) {
        // DataTables ya genera el <td>, así que debemos retornar un elemento válido en su interior (ej. div o span)
        tag = 'div';
        var meta = FIELD_MAP[row.source_table];
        return '<' + tag + ' class="editable-cell" contenteditable="true"' +
            ' data-id="' + row.id + '" data-field="' + field + '"' +
            ' data-table="' + escAttr(row.source_table) + '" data-idcol="' + meta.idcol + '"' +
            ' style="background-color:#f9f9f9; border:1px dashed #ccc; cursor:text; padding: 4px; border-radius: 4px; min-width: 50px;">' +
            (value === null || value === undefined || value === '' ? '—' : esc(value)) +
            '</' + tag + '>';
    }

    function selectOpen(row, field, extraStyle) {
        var meta = FIELD_MAP[row.source_table];
        return '<select class="inline-select select2" data-id="' + row.id + '" data-field="' + field + '"' +
            ' data-table="' + escAttr(row.source_table) + '" data-idcol="' + meta.idcol + '"' +
            ' style="' + (extraStyle || '') + '">';
    }

    function opt(value, label, selected) {
        return '<option value="' + escAttr(value) + '"' + (selected ? ' selected' : '') + '>' + esc(label) + '</option>';
    }

    function titleCase(s) {
        return String(s).toLowerCase().replace(/\b[a-záéíóúñ]/g, function (c) { return c.toUpperCase(); });
    }

    function actionsCell(innerHtml) {
        return '<div class="rrhh-acciones-cell">' + innerHtml + '</div>';
    }

    // Categoría a mostrar (solo visual). Para médicos se usa la especialidad (nomesp);
    // las especialidades no-médicas se reetiquetan a su área. No modifica datos.
    function categoriaLabel(row) {
        if (row.source_table === 'doctor') {
            var esp = String(row.especialidad || '').toUpperCase();
            if (!esp) { return 'Médico'; }
            if (esp.indexOf('SISTEMAS') !== -1) { return 'TI / Sistemas'; }
            if (esp.indexOf('NUTRICION') !== -1) { return 'Nutrición'; }
            if (esp.indexOf('PSICOLOG') !== -1) { return 'Psicología'; }
            if (esp.indexOf('ODONTOLOG') !== -1 || esp.indexOf('ENDODONCIA') !== -1 ||
                esp.indexOf('PROSTODONCIA') !== -1 || esp.indexOf('PERIODONC') !== -1) {
                return 'Odontología';
            }
            return titleCase(row.especialidad);
        }
        var meta = FIELD_MAP[row.source_table];
        return meta ? meta.label : row.source_table;
    }

    var columns = [
        { // 0 N° (Row counter)
            data: null, orderable: false, searchable: false,
            render: function (data, type, row, meta) {
                return '<div style="text-align:center; font-weight:bold; color:#555;">' + (meta.row + meta.settings._iDisplayStart + 1) + '</div>';
            }
        },
        { // 1 Categoria
            data: 'source_table', orderable: true,
            render: function (d, t, row) {
                return '<span class="badge-primary" style="padding:4px; border-radius:4px; font-size:0.8rem;">' + esc(categoriaLabel(row)) + '</span>';
            }
        },
        { // 2 Tipo de empleado
            data: null, orderable: true,
            render: function (data, type, row) {
                var v = row.tipo_empleado || '';
                return selectOpen(row, 'tipo_empleado') +
                    opt('Permanente', 'Permanente', v === 'Permanente') +
                    opt('Temporal', 'Temporal', v === 'Temporal') +
                    opt('Tiempo parcial', 'Tiempo parcial', v === 'Tiempo parcial') +
                    '</select>';
            }
        },
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, FIELD_MAP[row.source_table].f_ident, row.identificacion, 'th'); } }, // 3 DNI
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, FIELD_MAP[row.source_table].f_nombres, row.nombres); } }, // 4 NOMBRES
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, FIELD_MAP[row.source_table].f_apellidos, row.apellidos); } }, // 5 APELLIDOS
        { // 6 Sexo
            data: null, orderable: true,
            render: function (d, t, row) {
                return selectOpen(row, FIELD_MAP[row.source_table].f_sexo) +
                    opt('', '—', !row.sexo) +
                    opt('Masculino', 'Masculino', row.sexo === 'Masculino') +
                    opt('Femenino', 'Femenino', row.sexo === 'Femenino') +
                    '</select>';
            }
        },
        { // 7 Area/Depto
            data: null, orderable: false,
            render: function (d, t, row) {
                var html = selectOpen(row, 'id_departamento', ' min-width:120px;') + opt('', '—', !row.id_departamento);
                Object.keys(deptoMap).forEach(function (idDep) {
                    html += opt(idDep, deptoMap[idDep], String(row.id_departamento) === String(idDep));
                });
                return html + '</select>';
            }
        },
        { // 8 Cargo
            data: null, orderable: false,
            render: function (d, t, row) {
                var html = selectOpen(row, 'id_cargo', ' min-width:120px;') + opt('', '—', !row.id_cargo);
                Object.keys(cargoMap).forEach(function (idCargo) {
                    html += opt(idCargo, cargoMap[idCargo], String(row.id_cargo) === String(idCargo));
                });
                return html + '</select>';
            }
        },
        { // 9 Nivel salarial
            data: null, orderable: false,
            render: function (d, t, row) {
                var html = selectOpen(row, 'id_salary_level', ' min-width:120px;') + opt('', '—', !row.id_salary_level);
                Object.keys(salaryMap).forEach(function (idSl) {
                    html += opt(idSl, salaryMap[idSl], String(row.id_salary_level) === String(idSl));
                });
                return html + '</select>';
            }
        },
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, 'salario', row.salario === null ? '' : row.salario); } }, // 10
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, 'cuenta_bac', row.cuenta_bac); } }, // 11
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, 'fecha_ingreso', row.fecha_ingreso); } }, // 12
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, 'telefono', row.telefono); } }, // 13
        { data: null, orderable: false, render: function (d, t, row) { return editableCell(row, 'correo_personal', row.correo_personal); } }, // 14
        { // 15 Correo institucional
            data: null, orderable: false,
            render: function (d, t, row) {
                var field = isUser(row) ? 'email' : 'correo_institucional';
                return editableCell(row, field, row.correo_institucional);
            }
        },
        { // 16 Fecha nacimiento
            data: null, orderable: false,
            render: function (d, t, row) {
                var meta = FIELD_MAP[row.source_table];
                if (!meta || !meta.f_nac) { return readonlyCell(row.fecha_nacimiento); }
                return editableCell(row, meta.f_nac, row.fecha_nacimiento);
            }
        },
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, 'id_biometrico', row.id_biometrico); } }, // 17
        { data: null, orderable: true, render: function (d, t, row) { return editableCell(row, 'num_locker', row.num_locker); } }, // 18
        { // 19 Contrato
            data: null, orderable: false, searchable: false,
            render: function (d, t, row) {
                var meta = FIELD_MAP[row.source_table];
                var inputId = 'upload_contrato_' + row.source_table + '_' + row.id;
                var viewUrl = '../../backend/php/view_staff_doc.php?id=' + row.id + '&doc=contrato&table=' + encodeURIComponent(row.source_table) + '&idcol=' + meta.idcol;
                var html = '<div class="doc-actions">';
                if (row.tiene_contrato) {
                    html += '<div class="doc-row">';
                    html += '<a class="doc-btn" href="#" onclick="verPDF(\'' + viewUrl + '\', \'Ver Documento\'); return false;" title="Ver contrato"><i class="bx bx-show"></i> Ver</a>';
                    html += '<a class="doc-btn" href="#" title="Eliminar contrato" onclick="deleteContract(' + row.id + ', \'' + row.source_table + '\', \'' + meta.idcol + '\'); return false;"><i class="bx bx-trash"></i> Eliminar</a>';
                    html += '</div>';
                } else {
                    html += '<span class="doc-empty">Sin contrato</span>';
                }
                html += '<label class="doc-btn" title="Subir contrato" onclick="document.getElementById(\'' + inputId + '\').click();"><i class="bx bx-upload"></i> Subir</label>';
                html += '<input type="file" id="' + inputId + '" style="display:none;" accept=".pdf,.jpg,.png" onchange="uploadContract(this, ' + row.id + ', \'' + row.source_table + '\', \'' + meta.idcol + '\')">';
                html += '</div>';
                return html;
            }
        },
        { // 20 Estado
            data: null, orderable: false, searchable: false,
            render: function (d, t, row) {
                return '<label class="switch"><input type="checkbox" class="unified-state-toggle" data-id="' + row.id + '" data-table="' + escAttr(row.source_table) + '"' + (row.state == '1' ? ' checked' : '') + '/><span class="slider"></span></label>';
            }
        },
        { // 21 Acciones
            data: null, orderable: false, searchable: false,
            render: function (d, t, row) {
                var fieldMeta = FIELD_MAP[row.source_table];
                var html = '<a title="Actualizar" href="' + fieldMeta.edit + '?id=' + row.id + '&table=' + escAttr(row.source_table) + '" class="fa fa-pencil tooltip"></a>';
                if (cfg.estado === '1' && row.source_table !== 'users') {
                    html += ' <a title="Trasladar" href="#" class="fa fa-exchange tooltip btn-transfer-colab" data-id="' + row.id + '" data-table="' + escAttr(row.source_table) + '" style="background-color: #17a2b8; color: white; margin-left: 5px;"></a>';
                }
                if (cfg.allowDelete) {
                    html += ' <a title="Eliminar" href="#" class="fa fa-trash tooltip btn-delete-colab" data-id="' + row.id + '" data-table="' + escAttr(row.source_table) + '" data-idcol="' + fieldMeta.idcol + '"></a>';
                }
                return actionsCell(html);
            }
        }
    ];

    $(function () {
        if (!$('#example').length) { return; }

        var tabla = $('#example').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,
            dom: 'Bfrtip',
            ajax: {
                url: cfg.ajaxUrl || '../../backend/php/get_colaboradores.php',
                type: 'GET',
                data: function (d) {
                    d.estado = cfg.estado || '1';
                    d.tipo = cfg.tipo || '';
                    d.excluir = cfg.excluir || '';
                },
                error: function (xhr) {
                    var msg = 'No se pudieron cargar los datos de la lista.';
                    try {
                        var res = JSON.parse(xhr.responseText || '{}');
                        if (res.error) { msg = res.error; }
                        if (res.message) { msg = res.message; }
                    } catch (e) {}
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', msg, 'error');
                    }
                }
            },
            columns: columns,
            columnDefs: [
                { targets: -1, className: 'rrhh-col-acciones', width: cfg.allowDelete ? '5.5rem' : '3.5rem' }
            ],
            order: [[5, 'asc'], [4, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
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
                emptyTable: 'No hay colaboradores registrados.',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros totales)',
                search: 'Buscar:',
                paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
            },
            drawCallback: function () {
                $('#example select.inline-select.select2').each(function () {
                    var $el = $(this);
                    if ($el.data('select2')) {
                        $el.select2('destroy');
                    }
                });
                if (typeof window.medidataInitRrhhSelect2 === 'function') {
                    window.medidataInitRrhhSelect2($('#example'));
                }
            }
        });

        window.medidataTablaColaboradores = tabla;

        // Toggle de estado (delegado, porque las filas se crean dinamicamente).
        $('#example tbody').on('change', '.unified-state-toggle', function () {
            var checkbox = $(this);
            var id = checkbox.data('id');
            var table = checkbox.data('table');
            var newState = checkbox.is(':checked') ? 1 : 0;

            if (cfg.estado === '0' && newState === 1 && table !== 'users') {
                // Modo ex-colaboradores: al reactivar, preguntar destino
                checkbox.prop('checked', false); // revertir temporalmente
                
                var optionsHtml = '<select id="transfer-target" class="form-control form-select" style="display: block; width: 100%; font-size:1rem; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; background-color: #fff; color: #495057; cursor: pointer; outline: none;">' +
                    '<option value="staff_medifarma"' + (table==='staff_medifarma'?' selected':'') + '>Medifarma</option>' +
                    '<option value="staff_general_services"' + (['staff_administrative', 'nurse', 'staff_general_services'].indexOf(table) !== -1 ? ' selected' : '') + '>Medicasa</option>' +
                    '<option value="doctor"' + (table==='doctor'?' selected':'') + '>Médicos</option>' +
                    '</select>';

                Swal.fire({
                    title: 'Reintegrar Colaborador',
                    html: 'Seleccione a qué área desea reintegrar a esta persona:<br><br>' + optionsHtml,
                    showCancelButton: true,
                    confirmButtonText: 'Reintegrar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#28a745'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var targetTable = $('#transfer-target').val();
                        $.ajax({
                            url: '../../backend/php/transfer_colaborador.php',
                            type: 'POST',
                            data: { id: id, source_table: table, target_table: targetTable, new_state: 1 },
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({ title: 'Éxito', text: 'Reintegrado exitosamente', icon: 'success', timer: 1200, showConfirmButton: false });
                                    tabla.ajax.reload(null, false);
                                } else {
                                    Swal.fire('Error', response.message || 'Error al reintegrar.', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Error de comunicación', 'error');
                            }
                        });
                    }
                });
                return;
            }

            var endpoints = {
                'doctor': '../../backend/php/toggle_doctor_state.php',
                'nurse': '../../backend/php/toggle_nurse_state.php',
                'staff_administrative': '../../backend/php/toggle_administrative_state.php',
                'staff_general_services': '../../backend/php/toggle_general_services_state.php',
                'staff_medifarma': '../../backend/php/toggle_medifarma_state.php',
                'users': '../../backend/php/toggle_user_state.php'
            };
            var url = endpoints[table];
            if (!url) {
                Swal.fire('Error', 'Tipo de empleado no reconocido.', 'error');
                checkbox.prop('checked', !checkbox.is(':checked'));
                return;
            }

            $.ajax({
                url: url, type: 'POST', data: { id: id, state: newState }, dataType: 'json',
                success: function (response) {
                    if (response.success || response.status === 'success') {
                        Swal.fire({ title: 'Éxito', text: response.message || 'Estado actualizado', icon: 'success', timer: 1200, showConfirmButton: false });
                        tabla.ajax.reload(null, false);
                    } else {
                        Swal.fire('Error', response.message || 'No se pudo actualizar el estado.', 'error');
                        checkbox.prop('checked', !checkbox.is(':checked'));
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                    checkbox.prop('checked', !checkbox.is(':checked'));
                }
            });
        });

        // Eliminar (solo donde cfg.allowDelete; por ahora médicos -> delete_doctor.php).
        $('#example tbody').on('click', '.btn-delete-colab', function (e) {
            e.preventDefault();
            var btn = $(this);
            var id = btn.data('id');
            var table = btn.data('table');
            var idcol = btn.data('idcol');

            var deleteEndpoints = {
                'doctor': '../../backend/php/delete_doctor.php',
                'nurse': '../../backend/php/delete_nurse.php',
                'staff_administrative': '../../backend/php/delete_administrative.php',
                'staff_general_services': '../../backend/php/delete_general_services.php',
                'staff_medifarma': '../../backend/php/delete_medifarma.php'
            };
            var deleteWarnings = {
                'doctor': 'Se eliminarán también citas, eventos y registros vinculados.',
                'nurse': 'El registro de enfermería se eliminará permanentemente.',
                'staff_administrative': 'El registro administrativo se eliminará permanentemente.',
                'staff_general_services': 'El registro de servicios generales se eliminará permanentemente.',
                'staff_medifarma': 'El registro de medifarma se eliminará permanentemente.'
            };
            var url = deleteEndpoints[table];
            if (!url) {
                Swal.fire('Error', 'La eliminación no está disponible para este tipo de personal.', 'error');
                return;
            }

            Swal.fire({
                title: '¿Eliminar registro?',
                text: deleteWarnings[table] || 'Esta acción no se puede deshacer.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then(function (result) {
                if (!result.isConfirmed) { return; }
                var payload = {};
                payload[idcol] = id;
                $.ajax({
                    url: url, type: 'POST', data: payload, dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({ title: 'Eliminado', text: response.message || 'Eliminado correctamente', icon: 'success', timer: 1300, showConfirmButton: false });
                            tabla.ajax.reload(null, false);
                        } else {
                            Swal.fire('Error', response.message || 'No se pudo eliminar.', 'error');
                        }
                    },
                    error: function () {
                        Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                    }
                });
            });
        });
        // Traslado (botón de transfer) para colaboradores activos
        $('#example tbody').on('click', '.btn-transfer-colab', function (e) {
            e.preventDefault();
            var btn = $(this);
            var id = btn.data('id');
            var table = btn.data('table');

            var optionsHtml = '<select id="transfer-target-btn" class="form-control form-select" style="display: block; width: 100%; font-size:1rem; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; background-color: #fff; color: #495057; cursor: pointer; outline: none;">' +
                '<option value="staff_medifarma"' + (table==='staff_medifarma'?' selected':'') + '>Medifarma</option>' +
                '<option value="staff_general_services"' + (['staff_administrative', 'nurse', 'staff_general_services'].indexOf(table) !== -1 ? ' selected' : '') + '>Medicasa</option>' +
                '<option value="doctor"' + (table==='doctor'?' selected':'') + '>Médicos</option>' +
                '</select>';

            Swal.fire({
                title: 'Trasladar Personal',
                html: 'Seleccione la nueva área a la que será transferido:<br><br>' + optionsHtml,
                showCancelButton: true,
                confirmButtonText: 'Trasladar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#17a2b8'
            }).then(function(result) {
                if (result.isConfirmed) {
                    var targetTable = $('#transfer-target-btn').val();
                    if (targetTable === table) {
                        Swal.fire('Atención', 'Ya pertenece a esta área.', 'info');
                        return;
                    }
                    $.ajax({
                        url: '../../backend/php/transfer_colaborador.php',
                        type: 'POST',
                        data: { id: id, source_table: table, target_table: targetTable },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({ title: 'Éxito', text: 'Trasladado exitosamente', icon: 'success', timer: 1200, showConfirmButton: false });
                                tabla.ajax.reload(null, false);
                            } else {
                                Swal.fire('Error', response.message || 'Error al trasladar.', 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Error de comunicación', 'error');
                        }
                    });
                }
            });
        });
    });
})(jQuery);
