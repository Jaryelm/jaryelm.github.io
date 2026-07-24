<?php require_once '../../backend/registros/session_check.php'; ?>
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
    <title>MEDIDATA - MIS VACACIONES</title>
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
                <button class="button" onclick="abrirModal()">
                    <i class='bx bx-plus'></i> Nueva Solicitud
                </button>
            </div>
        <div class="vp-panel">
            <table id="tabla-solicitudes" class="responsive-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th>Tipo</th>
                        <th>Fechas</th>
                        <th>Días/Horas</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Resolución</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Llenado vía DataTables Ajax si fuera necesario, para esta fase puede estar vacío o cargarse en el futuro -->
                </tbody>
            </table>
        </div>
        </main>
    </section>

    <!-- Modal Nueva Solicitud -->
    <div id="modalNuevaSolicitud" class="vp-modal">
        <div class="vp-modal-content">
            <span class="vp-modal-close" onclick="cerrarModal()">&times;</span>
            <h2>Generar Solicitud</h2>

            <div class="vp-balance-box">
                <div class="vp-balance-item">
                    <small>Días Disponibles</small>
                    <span id="ui_disponibles">0</span>
                </div>
                <div class="vp-balance-item">
                    <small>Días Solicitados</small>
                    <span id="ui_solicitados">0</span>
                </div>
                <div class="vp-balance-item">
                    <small>Saldo Restante</small>
                    <span id="ui_saldo">0</span>
                </div>
            </div>

            <form id="formSolicitud">
                <div class="vp-form-row">
                    <label>Tipo de Ausencia</label>
                    <select name="type_id" id="type_id" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="vp-form-row" id="div_pago" style="display:none;">
                    <label>
                        <input type="checkbox" name="is_paid_vacation" id="is_paid_vacation" value="1" class="vp-checkbox-inline">
                        Solicitud de pago en efectivo (Vacaciones Pagadas)
                    </label>
                </div>

                <div class="vp-form-row">
                    <label>Tipo de Duraci&oacute;n</label>
                    <select name="duration_type" id="duration_type" required>
                        <option value="full_day">D&iacute;as completos</option>
                        <option value="partial_day">Por horas (D&iacute;a Parcial)</option>
                    </select>
                </div>

                <div class="vp-form-row vp-form-row-split">
                    <div>
                        <label>Fecha de Inicio</label>
                        <input type="date" name="start_date" id="start_date" required>
                    </div>
                    <div id="div_end_date">
                        <label>Fecha de Finalizaci&oacute;n</label>
                        <input type="date" name="end_date" id="end_date" required>
                    </div>
                </div>

                <div class="vp-form-row vp-form-row-split" id="div_times" style="display: none;">
                    <div>
                        <label>Hora de Inicio</label>
                        <input type="time" name="start_time" id="start_time">
                    </div>
                    <div>
                        <label>Hora de Fin</label>
                        <input type="time" name="end_time" id="end_time">
                    </div>
                </div>

                <div class="vp-form-row">
                    <label>Cantidad de días (Cálculo Automático)</label>
                    <input type="number" name="days_amount" id="days_amount" step="0.5" readonly required>
                </div>

                <div class="vp-form-row">
                    <label>Comentarios</label>
                    <textarea name="comments" rows="3"></textarea>
                </div>

                <div class="vp-form-row">
                    <label>Documento de respaldo</label>
                    <input type="file" name="proof_doc" id="proof_doc" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                    <small class="vp-muted">PDF, imagen (JPG/PNG/WEBP) o Word. Obligatorio si el tipo de ausencia lo requiere. Máx. 5&nbsp;MB.</small>
                </div>

                <div class="vp-form-row">
                    <label>Flujo de aprobación</label>
                    <div id="flujo-aprobacion" class="vp-flow-box">
                        <span class="vp-muted">Selecciona un tipo de ausencia para ver el flujo…</span>
                    </div>
                </div>

                <div class="vp-modal-footer">
                    <button type="button" class="vp-btn-cancel" onclick="cerrarModal()">Cancelar</button>
                    <button type="submit" class="vp-btn-submit">Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <script>
    let diasDisponiblesTotales = 0;

    const userId = <?php echo $_SESSION['id']; ?>;

$(document).ready(function() {

        // Cargar saldo principal al inicio
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_vacation_profile.php?user_id=' + userId, function(res) {
            if(!res.error) {
                diasDisponiblesTotales = parseFloat(res.info_vacaciones.dias_pendientes) || 0;
                $('#main_balance_ui').text(diasDisponiblesTotales);
            }
        });


        $('#tabla-solicitudes').DataTable({
            ajax: '../../backend/registros/vacaciones_permisos/fetch_my_requests.php',
            columns: [
                { data: 'colaborador' },
                { data: 'type_name' },
                { data: 'fechas' },
                { data: 'days_amount' },
                { data: 'status' },
                { data: 'comments' },
                { data: 'last_comment' },
                {
                    data: null,
                    orderable: false,
                    className: 'vp-col-center',
                    render: function(data, type, row) {
                        return `<button class="vp-icon-btn" onclick="verHistorial(${row.request_id})" title="Ver historial del flujo de aprobación"><i class='bx bx-show'></i> Ver</button>`;
                    }
                }
            ],
            order: [],
            columnDefs: [
                { targets: [3, 4], className: 'vp-col-center' }
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
        
        // Cargar Tipos de Ausencia
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_absence_types.php', function(data) {
            if(!data.error) {
                data.forEach(function(tipo) {
                    $('#type_id').append(`<option value="${tipo.type_id}" data-category="${tipo.category}">${tipo.name}</option>`);
                });
            }
        });
        
        // Evento Tipo Ausencia
        $('#type_id').change(function() {
            let cat = $(this).find(':selected').data('category');
            if (cat === 'Vacation') {
                $('#div_pago').show();
                $('#duration_type').val('full_day').trigger('change').prop('disabled', true);
            } else {
                $('#div_pago').hide();
                $('#is_paid_vacation').prop('checked', false);
                $('#duration_type').prop('disabled', false);
            }
            cargarFlujo($(this).val() || 0);
        });
        
        // Evento Fechas -> Calcular Días
        // Manejar cambio de tipo de duración
        $('#duration_type').change(function() {
            if ($(this).val() === 'partial_day') {
                $('#div_end_date').hide();
                $('#end_date').prop('required', false);
                $('#div_times').css('display', 'flex');
                $('#start_time, #end_time').prop('required', true);
            } else {
                $('#div_end_date').show();
                $('#end_date').prop('required', true);
                $('#div_times').hide();
                $('#start_time, #end_time').prop('required', false);
            }
            calcularDias();
        });

        $('#start_date, #end_date, #start_time, #end_time, #duration_type').change(calcularDias);

        // Enviar Formulario
        $('#formSolicitud').submit(function(e) {
            e.preventDefault();
            
            // Validar si los dias son validos
            if(parseFloat($('#ui_saldo').text()) < 0) {
                Swal.fire('Atención', 'No tienes suficientes días disponibles para esta solicitud.', 'warning');
                return;
            }

            $.ajax({
                url: '../../backend/registros/vacaciones_permisos/submit_absence_request.php',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(res) {
                    let data = typeof res === 'string' ? JSON.parse(res) : res;
                    if(data.status === 'success') {
                        Swal.fire('Éxito', data.message, 'success').then(() => {
                            cerrarModal();
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }
            });
        });
    });

    function abrirModal() {
        $('#formSolicitud')[0].reset();
        $('#div_pago').hide();
        $('#ui_solicitados').text('0');
        $('#ui_saldo').text('0');
        $('#days_amount').val('');
        cargarFlujo($('#type_id').val() || 0);

        // Cargar saldo en vivo
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_vacation_profile.php?user_id=' + userId, function(res) {
            if(!res.error) {
                diasDisponiblesTotales = parseFloat(res.info_vacaciones.dias_pendientes) || 0;
                $('#ui_disponibles').text(diasDisponiblesTotales);
                $('#ui_saldo').text(diasDisponiblesTotales);
            }
        });

        $('#modalNuevaSolicitud').fadeIn();
    }

    function cerrarModal() {
        $('#modalNuevaSolicitud').fadeOut();
    }

        function calcularDias() {
        let durationType = $('#duration_type').val();
        let start = $('#start_date').val();
        
        if (durationType === 'partial_day') {
            let startTime = $('#start_time').val();
            let endTime = $('#end_time').val();
            
            if (start && startTime && endTime) {
                // Set end_date same as start_date
                $('#end_date').val(start);
                
                let d1 = new Date(start + 'T' + startTime);
                let d2 = new Date(start + 'T' + endTime);
                let diffTime = d2 - d1;
                
                if (diffTime <= 0) {
                    $('#days_amount').val(0);
                    $('#ui_solicitados').text(0);
                    $('#ui_saldo').text(diasDisponiblesTotales);
                    return;
                }
                
                let hours = diffTime / (1000 * 60 * 60);
                let diffDays = hours / 8; // Assuming 8h = 1 day
                diffDays = Math.round(diffDays * 10) / 10;
                
                $('#days_amount').val(diffDays);
                $('#ui_solicitados').text(diffDays);
                
                let saldo = diasDisponiblesTotales - diffDays;
                $('#ui_saldo').text(saldo.toFixed(1));
            }
            return;
        }

        let end = $('#end_date').val();
        if(start && end) {
            let d1 = new Date(start);
            let d2 = new Date(end);
            
            if(d2 < d1) {
                $('#days_amount').val(0);
                $('#ui_solicitados').text(0);
                $('#ui_saldo').text(diasDisponiblesTotales);
                return;
            }
            
            let diffTime = Math.abs(d2 - d1);
            let diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 para incluir el día de fin
            
            $('#days_amount').val(diffDays);
            $('#ui_solicitados').text(diffDays);
            
            let saldo = diasDisponiblesTotales - diffDays;
            $('#ui_saldo').text(saldo.toFixed(1));

            if (saldo < 0) {
                $('#ui_saldo').css('color', 'red');
            } else {
                $('#ui_saldo').css('color', 'var(--blue)');
            }
        }
    }

    // ---- Flujo de aprobación (preview) e historial (stepper) ----
    function vpEsc(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    const VP_STEP_META = {
        approved:  { cls: 'vp-step--approved',  icon: 'bx-check' },
        rejected:  { cls: 'vp-step--rejected',  icon: 'bx-x' },
        current:   { cls: 'vp-step--current',   icon: 'bx-time-five' },
        waiting:   { cls: 'vp-step--waiting',   icon: '' },
        cancelled: { cls: 'vp-step--cancelled', icon: 'bx-minus' }
    };

    // mode: 'preview' (solo pasos numerados) | 'history' (con estado y quién resolvió)
    function renderStepper(steps, mode) {
        if (!steps || !steps.length) {
            return '<span class="vp-muted">Este flujo no tiene pasos configurados (aprobación directa por RRHH).</span>';
        }
        let html = '<ul class="vp-stepper">';
        steps.forEach(function(s) {
            if (mode === 'preview') {
                html += `<li class="vp-step vp-step--waiting">
                    <span class="vp-step-badge">${s.step_order}</span>
                    <div class="vp-step-body"><div class="vp-step-title">Paso ${s.step_order} · ${vpEsc(s.label)}</div></div>
                </li>`;
                return;
            }
            const meta  = VP_STEP_META[s.state] || VP_STEP_META.waiting;
            const badge = meta.icon ? `<i class='bx ${meta.icon}'></i>` : s.step_order;
            let metaLine;
            if (s.state === 'approved')       metaLine = `Aprobado por ${vpEsc(s.by)}${s.at ? ' · ' + vpEsc(s.at) : ''}`;
            else if (s.state === 'rejected')  metaLine = `Rechazado por ${vpEsc(s.by)}${s.at ? ' · ' + vpEsc(s.at) : ''}`;
            else if (s.state === 'current')   metaLine = 'Pendiente de aprobación';
            else if (s.state === 'cancelled') metaLine = 'No procesado';
            else                              metaLine = 'En espera';
            const comment = (s.comment && (s.state === 'approved' || s.state === 'rejected'))
                ? `<div class="vp-step-comment">"${vpEsc(s.comment)}"</div>` : '';
            html += `<li class="vp-step ${meta.cls}">
                <span class="vp-step-badge">${badge}</span>
                <div class="vp-step-body">
                    <div class="vp-step-title">Paso ${s.step_order} · ${vpEsc(s.label)}</div>
                    <div class="vp-step-meta">${metaLine}</div>
                    ${comment}
                </div>
            </li>`;
        });
        html += '</ul>';
        return html;
    }

    // Lista de documentos adjuntos con enlace al visor protegido.
    function renderDocs(attachments) {
        if (!attachments || !attachments.length) return '';
        let html = `<div class="vp-flow-name"><i class='bx bx-paperclip'></i> Documentos adjuntos</div><ul class="vp-doc-list">`;
        attachments.forEach(function(a) {
            html += `<li><a href="../../backend/registros/vacaciones_permisos/view_absence_doc.php?id=${a.id}" target="_blank" rel="noopener"><i class='bx bx-file'></i> ${vpEsc(a.name)}</a></li>`;
        });
        html += '</ul>';
        return html;
    }

    // Carga el flujo que procesará la solicitud (solo lectura) en el modal Nueva Solicitud.
    function cargarFlujo(typeId) {
        const cont = $('#flujo-aprobacion');
        cont.html('<span class="vp-muted">Cargando flujo…</span>');
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_request_flow.php', { type_id: typeId || 0 })
            .done(function(res) {
                if (!res || res.error || !res.workflow) {
                    cont.html('<span class="vp-muted">No hay flujo de aprobación configurado.</span>');
                    return;
                }
                let html = `<div class="vp-flow-name"><i class='bx bx-git-repo-forked'></i> ${vpEsc(res.workflow.name)}</div>`;
                html += renderStepper(res.steps, 'preview');
                cont.html(html);
            })
            .fail(function() {
                cont.html('<span class="vp-muted">No se pudo cargar el flujo.</span>');
            });
    }

    const VP_ESTADOS_HIST = {
        'Pending':     { mod: 'pending',     text: 'Pendiente' },
        'In_Progress': { mod: 'in-progress', text: 'En Proceso' },
        'Approved':    { mod: 'approved',    text: 'Aprobada' },
        'Rejected':    { mod: 'rejected',    text: 'Rechazada' },
        'Cancelled':   { mod: 'cancelled',   text: 'Cancelada' }
    };
    function histBadge(status) {
        const e = VP_ESTADOS_HIST[status] || { mod: 'neutral', text: status || '—' };
        return `<span class="vp-badge vp-badge--${e.mod}">${e.text}</span>`;
    }

    // Botón "Ver" de la tabla: historial del flujo (quién aprobó / quién falta).
    function verHistorial(requestId) {
        Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_request_history.php', { request_id: requestId })
            .done(function(res) {
                if (!res || res.error) {
                    Swal.fire('Error', (res && res.error) ? res.error : 'No se pudo cargar el historial.', 'error');
                    return;
                }
                const r = res.request;
                const head = `<table class="vp-details-table">
                    <tr><th>Solicitud</th><td>#${vpEsc(r.request_id)}</td></tr>
                    <tr><th>Tipo</th><td>${vpEsc(r.type_name)}</td></tr>
                    <tr><th>Fechas</th><td>${vpEsc(r.start_date)}${(r.end_date && r.end_date !== r.start_date) ? ' al ' + vpEsc(r.end_date) : ''}</td></tr>
                    <tr><th>Días</th><td>${vpEsc(r.days_amount)}</td></tr>
                    <tr><th>Estado</th><td>${histBadge(r.status)}</td></tr>
                    <tr><th>Flujo</th><td>${vpEsc(r.workflow_name)}</td></tr>
                </table>`;
                const body = `<div class="vp-flow-name"><i class='bx bx-list-check'></i> Flujo de aprobación</div>` + renderStepper(res.steps, 'history');
                Swal.fire({
                    title: 'Historial de la solicitud',
                    html: `<div class="vp-hist">${head}${body}${renderDocs(res.attachments)}</div>`,
                    width: 620,
                    confirmButtonText: 'Cerrar'
                });
            })
            .fail(function() {
                Swal.fire('Error', 'No se pudo cargar el historial.', 'error');
            });
    }
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>






