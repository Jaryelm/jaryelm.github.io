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
    <title>MEDIDATA - INCAPACIDADES</title>
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
                <button class="button" onclick="abrirModalInc()">
                    <i class='bx bx-plus'></i> Registrar Incapacidad
                </button>
            </div>
            <div class="vp-panel">
                <p class="vp-muted">
                    Registra tus incapacidades. El sistema bloquea las vacaciones durante el período de incapacidad y,
                    si ya tienes vacaciones que se traslapan, recalcula omitiendo esos días.
                </p>
                <table id="tabla-incapacidades" class="responsive-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Desde</th>
                            <th>Hasta</th>
                            <th>Días</th>
                            <th>Institución emisora</th>
                            <th>N.º incapacidad</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>

    <!-- Modal Registrar Incapacidad -->
    <div id="modalIncapacidad" class="vp-modal">
        <div class="vp-modal-content">
            <span class="vp-modal-close" onclick="cerrarModalInc()">&times;</span>
            <h2>Registrar Incapacidad</h2>

            <form id="formIncapacidad">
                <div class="vp-form-row">
                    <label>Tipo de incapacidad</label>
                    <select name="type_id" id="inc_type_id" required>
                        <option value="">Seleccione...</option>
                    </select>
                </div>

                <div class="vp-form-row vp-form-row-split">
                    <div>
                        <label>Fecha de inicio</label>
                        <input type="date" name="start_date" id="inc_start_date" required>
                    </div>
                    <div>
                        <label>Fecha final</label>
                        <input type="date" name="end_date" id="inc_end_date" required>
                    </div>
                </div>

                <div class="vp-form-row vp-form-row-split">
                    <div>
                        <label>Instituci&oacute;n emisora</label>
                        <input type="text" name="issuing_institution" id="inc_institucion" maxlength="255" placeholder="Ej. IHSS, cl&iacute;nica, hospital..." required>
                    </div>
                    <div>
                        <label>N&uacute;mero de incapacidad</label>
                        <input type="text" name="medical_leave_number" id="inc_numero" maxlength="100" placeholder="N.&ordm; de incapacidad" required>
                    </div>
                </div>

                <div class="vp-form-row">
                    <label>Documento adjunto</label>
                    <input type="file" name="proof_doc" id="inc_proof_doc" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                    <small class="vp-muted">PDF, imagen (JPG/PNG/WEBP) o Word. Obligatorio si el tipo lo requiere. Máx. 5&nbsp;MB.</small>
                </div>

                <div class="vp-form-row">
                    <label>Comentarios</label>
                    <textarea name="comments" rows="2"></textarea>
                </div>

                <div class="vp-modal-footer">
                    <button type="button" class="vp-btn-cancel" onclick="cerrarModalInc()">Cancelar</button>
                    <button type="submit" class="vp-btn-submit">Registrar</button>
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
    var tablaInc;

    function estadoBadge(st) {
        var map = {
            'Pending': ['pending', 'Pendiente'],
            'In_Progress': ['pending', 'En proceso'],
            'Approved': ['completed', 'Aprobada'],
            'Rejected': ['cancelled', 'Rechazada'],
            'Cancelled': ['cancelled', 'Cancelada']
        };
        var m = map[st] || ['pending', st || '—'];
        return '<span class="status ' + m[0] + '">' + m[1] + '</span>';
    }

    $(document).ready(function() {
        tablaInc = $('#tabla-incapacidades').DataTable({
            ajax: { url: '../../backend/registros/vacaciones_permisos/fetch_incapacidades.php', dataSrc: 'data' },
            columns: [
                { data: 'type_name' },
                { data: 'start_date' },
                { data: 'end_date' },
                { data: 'days_amount', className: 'vp-col-center' },
                { data: 'issuing_institution', defaultContent: '—' },
                { data: 'medical_leave_number', defaultContent: '—' },
                { data: 'request_status', className: 'vp-col-center', render: function(d) { return estadoBadge(d); } }
            ],
            order: [[1, 'desc']],
            dom: 'Bfrtip',
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
            buttons: [
                { extend: 'copy', className: 'button' },
                { extend: 'csv', className: 'button' },
                { extend: 'excel', className: 'button' },
                { extend: 'print', className: 'button' }
            ],
            language: {
                processing: 'Cargando...', lengthMenu: 'Mostrar _MENU_ registros',
                zeroRecords: 'No se encontraron resultados', emptyTable: 'No hay incapacidades registradas.',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros totales)', search: 'Buscar:',
                paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
            }
        });

        // Cargar SOLO los tipos de categoría Medical_Leave
        $.getJSON('../../backend/registros/vacaciones_permisos/fetch_absence_types.php', function(data) {
            if (Array.isArray(data)) {
                data.filter(function(t) { return t.category === 'Medical_Leave'; })
                    .forEach(function(t) {
                        $('#inc_type_id').append('<option value="' + t.type_id + '">' + t.name + '</option>');
                    });
            }
        });

        $('#formIncapacidad').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '../../backend/registros/vacaciones_permisos/submit_absence_request.php',
                type: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                success: function(res) {
                    var data = typeof res === 'string' ? JSON.parse(res) : res;
                    if (data.status === 'success') {
                        Swal.fire('Éxito', data.message, 'success').then(function() {
                            cerrarModalInc();
                            tablaInc.ajax.reload(null, false);
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Error de comunicación con el servidor.', 'error'); }
            });
        });
    });

    function abrirModalInc() {
        $('#formIncapacidad')[0].reset();
        $('#modalIncapacidad').fadeIn();
    }
    function cerrarModalInc() {
        $('#modalIncapacidad').fadeOut();
    }
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
