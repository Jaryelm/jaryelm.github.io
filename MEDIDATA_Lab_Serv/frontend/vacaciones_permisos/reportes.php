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
    <title>MEDIDATA - REPORTES</title>
    <style>
        .vp-filtros { display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end; margin-bottom:16px; }
        .vp-filtros .vp-f { display:flex; flex-direction:column; gap:4px; }
        .vp-filtros label { font-size:12px; font-weight:600; color:var(--dark); }
        .vp-filtros select, .vp-filtros input[type=date] { padding:7px 9px; border:1px solid #d7dbe0; border-radius:6px; min-width:170px; background:#fff; }
        #reporte-titulo { margin:6px 0 12px; font-size:15px; font-weight:600; color:var(--blue); }
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

            <div class="vp-panel">
                <div class="vp-filtros">
                    <div class="vp-f">
                        <label>Tipo de reporte</label>
                        <select id="f_tipo_reporte">
                            <option value="solicitudes">Solicitudes (todas)</option>
                            <option value="vacaciones_programadas">Vacaciones programadas</option>
                            <option value="incapacidades">Incapacidades</option>
                            <option value="nomina">Clasificación para nómina</option>
                            <option value="saldos">Saldos de vacaciones (pendientes)</option>
                        </select>
                    </div>
                    <div class="vp-f">
                        <label>Colaborador</label>
                        <select id="f_colaborador"><option value="">Todos</option></select>
                    </div>
                    <div class="vp-f">
                        <label>Departamento</label>
                        <select id="f_departamento"><option value="">Todos</option></select>
                    </div>
                    <div class="vp-f" data-hide-on="saldos nomina">
                        <label>Estado</label>
                        <select id="f_estado">
                            <option value="">Todos</option>
                            <option value="Pending">Pendiente</option>
                            <option value="In_Progress">En proceso</option>
                            <option value="Approved">Aprobada</option>
                            <option value="Rejected">Rechazada</option>
                            <option value="Cancelled">Cancelada</option>
                        </select>
                    </div>
                    <div class="vp-f" data-hide-on="saldos">
                        <label>Tipo de permiso</label>
                        <select id="f_tipo"><option value="">Todos</option></select>
                    </div>
                    <div class="vp-f" data-hide-on="saldos">
                        <label>Desde</label>
                        <input type="date" id="f_desde">
                    </div>
                    <div class="vp-f" data-hide-on="saldos">
                        <label>Hasta</label>
                        <input type="date" id="f_hasta">
                    </div>
                    <div class="vp-f">
                        <label>&nbsp;</label>
                        <button class="button" onclick="generarReporte()"><i class='bx bx-search'></i> Generar</button>
                    </div>
                </div>

                <div id="reporte-titulo">Selecciona los filtros y presiona <strong>Generar</strong>.</div>
                <table id="tabla-reporte" class="responsive-table" style="width:100%">
                    <thead></thead>
                    <tbody></tbody>
                </table>
            </div>
        </main>
    </section>

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
    var BASE = '../../backend/registros/vacaciones_permisos/';
    var tablaReporte = null;

    function filtrosActuales() {
        return {
            tipo_reporte:    $('#f_tipo_reporte').val(),
            user_id:         $('#f_colaborador').val(),
            id_departamento: $('#f_departamento').val(),
            estado:          $('#f_estado').val(),
            type_id:         $('#f_tipo').val(),
            desde:           $('#f_desde').val(),
            hasta:           $('#f_hasta').val()
        };
    }

    function exportarWord() {
        window.location = BASE + 'export_reporte_word.php?' + $.param(filtrosActuales());
    }

    function generarReporte() {
        var f = filtrosActuales();
        $('#reporte-titulo').text('Generando…');
        $.getJSON(BASE + 'fetch_reporte.php', f, function(res) {
            if (res.error) {
                $('#reporte-titulo').text('—');
                Swal.fire('Error', res.error, 'error');
                return;
            }
            if (tablaReporte) { tablaReporte.destroy(); tablaReporte = null; }
            $('#tabla-reporte thead').html('<tr>' + res.columns.map(function(c) { return '<th>' + c.title + '</th>'; }).join('') + '</tr>');
            $('#tabla-reporte tbody').empty();
            $('#reporte-titulo').text(res.titulo + ' — ' + res.data.length + ' registro(s)');

            tablaReporte = $('#tabla-reporte').DataTable({
                data: res.data,
                columns: res.columns.map(function(c) { return { data: c.data, defaultContent: '—' }; }),
                dom: 'Bfrtip',
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                buttons: [
                    { extend: 'copy',  className: 'button', text: 'Copiar' },
                    { extend: 'csv',   className: 'button', text: 'CSV' },
                    { extend: 'excel', className: 'button', text: 'Excel' },
                    { extend: 'pdf',   className: 'button', text: 'PDF', orientation: 'landscape', pageSize: 'LETTER' },
                    { text: 'Word', className: 'button', action: function() { exportarWord(); } },
                    { extend: 'print', className: 'button', text: 'Imprimir' }
                ],
                language: {
                    processing: 'Cargando...', lengthMenu: 'Mostrar _MENU_ registros',
                    zeroRecords: 'No se encontraron resultados', emptyTable: 'No hay datos para los filtros seleccionados.',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros totales)', search: 'Buscar:',
                    paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
                }
            });
        });
    }

    // Mostrar/ocultar filtros según el tipo de reporte (data-hide-on="tipo1 tipo2").
    function ajustarFiltros() {
        var t = $('#f_tipo_reporte').val();
        $('[data-hide-on]').each(function() {
            var lista = ($(this).attr('data-hide-on') || '').split(/\s+/);
            $(this).toggle(lista.indexOf(t) === -1);
        });
    }

    $(document).ready(function() {
        // Cargar opciones de filtros
        $.getJSON(BASE + 'fetch_reporte_opciones.php', function(res) {
            if (res.error) return;
            (res.colaboradores || []).forEach(function(c) {
                $('#f_colaborador').append('<option value="' + c.id_user + '">' + c.nombre + '</option>');
            });
            (res.departamentos || []).forEach(function(d) {
                $('#f_departamento').append('<option value="' + d.id + '">' + d.name + '</option>');
            });
            (res.tipos || []).forEach(function(t) {
                $('#f_tipo').append('<option value="' + t.type_id + '">' + t.name + '</option>');
            });
        });

        $('#f_tipo_reporte').change(ajustarFiltros);
        ajustarFiltros();
    });
    </script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
