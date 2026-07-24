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
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <link href='../../backend/css/fullcalendar.css' rel='stylesheet' />
    <title>MEDIDATA - GESTIÓN DE FERIADOS</title>
    <style>
        .vp-cal-legend { display:flex; gap:18px; flex-wrap:wrap; align-items:center; margin:4px 0 14px; }
        .vp-cal-legend .dot { display:inline-block; width:12px; height:12px; border-radius:3px; margin-right:6px; vertical-align:middle; }
        #calendar { max-width: 100%; }
        #calendar .fc-event.vp-feriado { border:none; padding:2px 4px; font-weight:600; cursor:pointer; }
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
            <div class="page-actions">
                <a href="#" id="btn-agregar" class="button">
                    <i class='bx bx-plus'></i> Agregar Feriado
                </a>
                <a href="calendario_feriados.php" class="button">
                    <i class='bx bx-calendar'></i> Calendario General
                </a>
            </div>
            <div class="vp-panel">
                <p class="vp-muted">
                    Selecciona un día (o arrastra sobre varios días) para crear un feriado; haz clic en un feriado existente para editarlo o eliminarlo.
                    Los feriados se descuentan automáticamente al calcular las vacaciones y se reflejan en los calendarios del módulo.
                </p>
                <div class="vp-cal-legend">
                    <span><span class="dot" style="background:#f39c12;"></span>Feriado</span>
                </div>
                <div id="calendar"></div>
            </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/moment.min.js"></script>
    <script src="../../backend/js/fullcalendar/fullcalendar.js"></script>
    <script src="../../backend/js/fullcalendar/locale/es.js"></script>
    <script>
    $(document).ready(function() {
        moment.locale('es');

        var SAVE_URL   = '../../backend/registros/vacaciones_permisos/save_feriado.php';
        var DELETE_URL = '../../backend/registros/vacaciones_permisos/delete_feriado.php';
        var FEED_URL   = '../../backend/registros/vacaciones_permisos/fetch_feriados.php';

        function vpEsc(v) {
            if (v === null || v === undefined) return '';
            return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function fechaBonita(ymd) {
            return moment(String(ymd).substr(0, 10), 'YYYY-MM-DD').format('DD/MM/YYYY');
        }
        function refetch() {
            $('#calendar').fullCalendar('refetchEvents');
        }

        // -------- Alta / edición --------
        // opts: { holiday_id, date, description }  -> edición (un solo día)
        //       { prefillStart, prefillEnd }        -> alta (rango opcional)
        function showForm(opts) {
            opts = opts || {};
            var isEdit = !!opts.holiday_id;
            var startVal = isEdit ? String(opts.date).substr(0, 10) : (opts.prefillStart || '');
            var endVal   = opts.prefillEnd || '';

            var html = `
                <form id="form-feriado" style="text-align:left;">
                    <input type="hidden" id="holiday_id" value="${isEdit ? vpEsc(opts.holiday_id) : ''}">
                    <div class="vp-form-row">
                        <label>${isEdit ? 'Fecha' : 'Fecha de inicio'}</label>
                        <input type="date" id="fecha" class="form-control" value="${vpEsc(startVal)}" required>
                    </div>
                    ${isEdit ? '' : `
                    <div class="vp-form-row">
                        <label>Fecha de fin <small class="vp-muted">(opcional, para feriados de varios días)</small></label>
                        <input type="date" id="fecha_fin" class="form-control" value="${vpEsc(endVal)}">
                    </div>`}
                    <div class="vp-form-row">
                        <label>Descripción</label>
                        <input type="text" id="descripcion" class="form-control" maxlength="255" value="${isEdit ? vpEsc(opts.description) : ''}" placeholder="Ej. Semana Santa" required>
                    </div>
                </form>
            `;

            Swal.fire({
                title: isEdit ? 'Editar Feriado' : 'Agregar Feriado',
                html: html,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#035c67',
                focusConfirm: false,
                preConfirm: () => {
                    var fecha = $('#fecha').val();
                    var fechaFin = $('#fecha_fin').val(); // vacío en modo edición
                    var descripcion = ($('#descripcion').val() || '').trim();
                    if (!fecha || !descripcion) {
                        Swal.showValidationMessage('La fecha y la descripción son obligatorias.');
                        return false;
                    }
                    if (fechaFin && fechaFin < fecha) {
                        Swal.showValidationMessage('La fecha de fin no puede ser anterior a la de inicio.');
                        return false;
                    }
                    return {
                        holiday_id: $('#holiday_id').val(),
                        date: fecha,
                        end_date: fechaFin || '',
                        description: descripcion
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(SAVE_URL, result.value, function(res) {
                        if (res.success) {
                            Swal.fire('Guardado', res.message, 'success');
                            refetch();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        }

        // -------- Eliminar --------
        function eliminarFeriado(holidayId, desc, date) {
            Swal.fire({
                title: '¿Eliminar feriado?',
                html: `Se eliminará <strong>${vpEsc(desc)}</strong> (${vpEsc(fechaBonita(date))}). Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post(DELETE_URL, { holiday_id: holidayId }, function(res) {
                        if (res.success) {
                            Swal.fire('Eliminado', res.message, 'success');
                            refetch();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }, 'json');
                }
            });
        }

        // -------- Calendario --------
        $('#calendar').fullCalendar({
            locale: 'es',
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,basicWeek,basicDay'
            },
            defaultView: 'month',
            editable: false,
            selectable: true,
            selectHelper: true,
            eventLimit: true,
            showNonCurrentDates: false,
            events: function(start, end, timezone, callback) {
                $.getJSON(FEED_URL, function(res) {
                    var data = (res && res.data) ? res.data : [];
                    var eventos = data.map(function(f) {
                        return {
                            id: 'hol_' + f.holiday_id,
                            holiday_id: f.holiday_id,
                            title: f.description,
                            start: String(f.date).substr(0, 10),
                            allDay: true,
                            color: '#f39c12',
                            className: 'vp-feriado'
                        };
                    });
                    callback(eventos);
                }).fail(function() {
                    callback([]);
                });
            },
            // Crear feriado seleccionando un día o un rango
            select: function(start, end) {
                // 'end' es exclusivo en FullCalendar: se resta un día para el fin inclusivo.
                var finIncl = end.clone().subtract(1, 'days');
                var prefillStart = start.format('YYYY-MM-DD');
                var prefillEnd = finIncl.isAfter(start) ? finIncl.format('YYYY-MM-DD') : '';
                $('#calendar').fullCalendar('unselect');
                showForm({ prefillStart: prefillStart, prefillEnd: prefillEnd });
            },
            eventRender: function(event, element) {
                element.find('.fc-title').html('<strong>' + vpEsc(event.title) + '</strong>');
                element.attr('title', vpEsc(event.title) + ' — ' + fechaBonita(event.start.format('YYYY-MM-DD')));
            },
            // Clic en un feriado existente: ver / editar / eliminar
            eventClick: function(event) {
                var date = event.start.format('YYYY-MM-DD');
                Swal.fire({
                    title: vpEsc(event.title),
                    html: `<p style="margin:0;">Feriado del <strong>${vpEsc(fechaBonita(date))}</strong></p>`,
                    icon: 'info',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="bx bx-edit"></i> Editar',
                    denyButtonText: '<i class="bx bx-trash"></i> Eliminar',
                    cancelButtonText: 'Cerrar',
                    confirmButtonColor: '#035c67',
                    denyButtonColor: '#dc3545'
                }).then((result) => {
                    if (result.isConfirmed) {
                        showForm({ holiday_id: event.holiday_id, date: date, description: event.title });
                    } else if (result.isDenied) {
                        eliminarFeriado(event.holiday_id, event.title, date);
                    }
                });
            }
        });

        $('#btn-agregar').click(function(e) {
            e.preventDefault();
            showForm({});
        });

        setTimeout(function() { $(window).trigger('resize'); }, 300);
    });
    </script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
