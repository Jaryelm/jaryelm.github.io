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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
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
            <div class="vp-panel">
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
    let tablaSolicitudes;

    // Catálogo de estados: color de badge + etiqueta en español. Reutilizado por la
    // columna Estado y por el modal de detalle.
    const VP_ESTADOS = {
        'Pending':     { mod: 'pending',     text: 'Pendiente' },
        'In_Progress': { mod: 'in-progress', text: 'En Proceso' },
        'Approved':    { mod: 'approved',    text: 'Aprobada' },
        'Rejected':    { mod: 'rejected',    text: 'Rechazada' },
        'Cancelled':   { mod: 'cancelled',   text: 'Cancelada' }
    };

    function estadoBadge(status) {
        const e = VP_ESTADOS[status] || { mod: 'neutral', text: status || '—' };
        return `<span class="vp-badge vp-badge--${e.mod}">${e.text}</span>`;
    }

    function vpEsc(v) {
        if (v === null || v === undefined || v === '') return '—';
        return String(v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
    }

    $(document).ready(function() {
        tablaSolicitudes = $('#tabla-solicitudes-global').DataTable({
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
                        return estadoBadge(data);
                    }
                },
                { data: 'created_at' },
                {
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        return `<button class="vp-icon-btn" onclick="viewRequest(${row.request_id})" title="Ver Detalles"><i class='bx bx-show'></i> Ver</button>`;
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
                },
            responsive: true,
            order: [[7, 'desc']]
        });
    });

    // Stepper del flujo de aprobación (historial: quién aprobó / quién falta)
    const VP_STEP_META = {
        approved:  { cls: 'vp-step--approved',  icon: 'bx-check' },
        rejected:  { cls: 'vp-step--rejected',  icon: 'bx-x' },
        current:   { cls: 'vp-step--current',   icon: 'bx-time-five' },
        waiting:   { cls: 'vp-step--waiting',   icon: '' },
        cancelled: { cls: 'vp-step--cancelled', icon: 'bx-minus' }
    };

    function renderStepper(steps) {
        if (!steps || !steps.length) {
            return '<span class="vp-muted">Este flujo no tiene pasos configurados (aprobación directa por RRHH).</span>';
        }
        let html = '<ul class="vp-stepper">';
        steps.forEach(function(s) {
            const meta  = VP_STEP_META[s.state] || VP_STEP_META.waiting;
            const badge = meta.icon ? `<i class='bx ${meta.icon}'></i>` : s.step_order;
            let metaLine;
            if (s.state === 'approved')       metaLine = `Aprobado por ${vpEsc(s.by)}${s.at ? ' · ' + vpEsc(s.at) : ''}`;
            else if (s.state === 'rejected')  metaLine = `Rechazado por ${vpEsc(s.by)}${s.at ? ' · ' + vpEsc(s.at) : ''}`;
            else if (s.state === 'current')   metaLine = 'Pendiente de aprobación (paso actual)';
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

    // Abre el detalle de la solicitud + el historial del flujo. Si está Pendiente/En Proceso
    // ofrece Aprobar / Rechazar (el "Aprobar" resuelve el PASO ACTUAL del flujo).
    function viewRequest(id) {
        const row = tablaSolicitudes.rows().data().toArray()
            .find(r => String(r.request_id) === String(id));
        if (!row) { Swal.fire('Error', 'No se encontró la solicitud.', 'error'); return; }

        Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_request_history.php', { request_id: id })
            .done(function(res) { mostrarDetalle(row, (res && !res.error) ? res : null); })
            .fail(function() { mostrarDetalle(row, null); });
    }

    function mostrarDetalle(row, hist) {
        const pendiente = (row.request_status === 'Pending' || row.request_status === 'In_Progress');

        let horasRow = '';
        if (row.start_time && row.end_time) {
            horasRow = `<tr><th>Horario</th><td>${vpEsc(String(row.start_time).substring(0,5))} - ${vpEsc(String(row.end_time).substring(0,5))}</td></tr>`;
        }

        const detalle = `
            <table class="vp-details-table">
                <tr><th>Solicitud</th><td>#${vpEsc(row.request_id)}</td></tr>
                <tr><th>Empleado</th><td>${vpEsc(row.user_name)}</td></tr>
                <tr><th>Tipo de ausencia</th><td>${vpEsc(row.type_name)}</td></tr>
                <tr><th>Desde</th><td>${vpEsc(row.start_date)}</td></tr>
                <tr><th>Hasta</th><td>${vpEsc(row.end_date)}</td></tr>
                ${horasRow}
                <tr><th>Días / Horas</th><td>${vpEsc(row.days_amount)}</td></tr>
                <tr><th>Estado</th><td>${estadoBadge(row.request_status)}</td></tr>
                <tr><th>Fecha solicitud</th><td>${vpEsc(row.created_at)}</td></tr>
                <tr><th>Comentarios</th><td>${vpEsc(row.comments)}</td></tr>
            </table>
        `;

        let flujo = '';
        if (hist) {
            flujo = `<div class="vp-flow-name"><i class='bx bx-list-check'></i> Flujo de aprobación${hist.request && hist.request.workflow_name ? ' · ' + vpEsc(hist.request.workflow_name) : ''}</div>`
                  + renderStepper(hist.steps);
        }

        let alerta = '';
        if (hist && hist.dept_overlaps && hist.dept_overlaps.length) {
            const items = hist.dept_overlaps.map(o => `${vpEsc(o.name)} (${vpEsc(o.start_date)} – ${vpEsc(o.end_date)})`).join('; ');
            alerta = `<div class="vp-alert-warn"><i class='bx bx-error'></i> <b>${hist.dept_overlaps.length}</b> colaborador(es) del mismo departamento con vacaciones traslapadas: ${items}</div>`;
        }

        Swal.fire({
            title: 'Detalle de solicitud',
            html: `<div class="vp-hist">${detalle}${alerta}${flujo}${hist ? renderDocs(hist.attachments) : ''}</div>`,
            width: 640,
            showConfirmButton: pendiente,
            showDenyButton: pendiente,
            showCancelButton: true,
            confirmButtonText: '<i class="bx bx-check"></i> Aprobar',
            denyButtonText: '<i class="bx bx-x"></i> Rechazar',
            cancelButtonText: 'Cerrar',
            confirmButtonColor: '#28a745',
            denyButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                confirmarDecision(row, 'Approved');
            } else if (result.isDenied) {
                confirmarDecision(row, 'Rejected');
            }
        });
    }

    // Confirma la decisión (con comentario opcional) y la envía al backend.
    function confirmarDecision(row, decision) {
        const esAprobar = (decision === 'Approved');
        const detalle = esAprobar
            ? `Se aprobará la solicitud <b>#${vpEsc(row.request_id)}</b> de <b>${vpEsc(row.user_name)}</b>.<br>` +
              `<small>Si el tipo de ausencia deduce vacaciones, se rebajarán ${vpEsc(row.days_amount)} día(s) del kardex.</small>`
            : `Se rechazará la solicitud <b>#${vpEsc(row.request_id)}</b> de <b>${vpEsc(row.user_name)}</b>.`;

        Swal.fire({
            title: esAprobar ? '¿Aprobar solicitud?' : '¿Rechazar solicitud?',
            html: detalle,
            icon: esAprobar ? 'question' : 'warning',
            input: 'textarea',
            inputPlaceholder: esAprobar ? 'Comentario de resolución (opcional)...' : 'Motivo del rechazo (opcional)...',
            inputAttributes: { 'aria-label': 'Comentario de resolución' },
            showCancelButton: true,
            confirmButtonText: esAprobar ? 'Sí, aprobar' : 'Sí, rechazar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: esAprobar ? '#28a745' : '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                enviarDecision(row.request_id, decision, result.value);
            }
        });
    }

    function enviarDecision(requestId, decision, comment) {
        Swal.fire({ title: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '../../backend/registros/vacaciones_permisos/approve_request.php',
            type: 'POST',
            dataType: 'json',
            data: { request_id: requestId, decision: decision, comment: comment || '' },
            success: function(res) {
                if (res && res.status === 'success') {
                    Swal.fire('Listo', res.message, 'success');
                    tablaSolicitudes.ajax.reload(null, false);
                } else {
                    Swal.fire('Error', (res && res.message) ? res.message : 'No se pudo procesar la solicitud.', 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
            }
        });
    }
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>



