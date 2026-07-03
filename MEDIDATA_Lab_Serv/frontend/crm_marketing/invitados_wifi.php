<?php
include_once '../../backend/registros/session_check.php';
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
    <title>MEDIDATA</title>
</head>
<body>
    <?php include_once '../admin/menu.php'; ?>

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

            <div class="data">
                <div class="content-data">
                    <div class="table-title">
                        <h1>Invitados con Acceso WiFi</h1>
                    </div>

                    <div class="table-responsive">
                        <table id="tabla_wifi" class="responsive-table" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>NOMBRE</th>
                                    <th>CELULAR</th>
                                    <th>CORREO</th>
                                    <th>SERVICIO</th>
                                    <th>IP</th>
                                    <th>MAC</th>
                                    <th>FECHA DE REGISTRO</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>

    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <script>
        (function () {
            function esc(text) {
                if (text === null || text === undefined || text === '') { return ''; }
                return $('<div>').text(text).html();
            }
            function fmtFecha(value) {
                if (!value) { return '—'; }
                var d = new Date(String(value).replace(' ', 'T'));
                if (isNaN(d.getTime())) { return esc(value); }
                var meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                var hh = ('0' + d.getHours()).slice(-2);
                var mm = ('0' + d.getMinutes()).slice(-2);
                return d.getDate() + ' ' + meses[d.getMonth()] + ' ' + d.getFullYear() + ', ' + hh + ':' + mm;
            }

            $(function () {
                $('#tabla_wifi').DataTable({
                    processing: true,
                    serverSide: true,
                    scrollX: true,
                    dom: 'Bfrtip',
                    ajax: {
                        url: '../../backend/php/get_wifi_invitados.php',
                        type: 'GET'
                    },
                    columns: [
                        { data: 'nombre', render: function (d) { return esc(d) || '—'; } },
                        { data: 'celular', render: function (d) { return esc(d) || '—'; } },
                        {
                            data: 'email',
                            render: function (d) {
                                return d ? '<a href="mailto:' + esc(d) + '">' + esc(d) + '</a>' : '—';
                            }
                        },
                        {
                            data: 'servicios',
                            render: function (d) {
                                if (!d) { return '—'; }
                                return '<span class="badge-primary" style="padding:4px 8px; border-radius:4px; font-size:0.8rem;">' + esc(d) + '</span>';
                            }
                        },
                        { data: 'userip', render: function (d) { return esc(d) || '—'; } },
                        { data: 'usermac', render: function (d) { return esc(d) || '—'; } },
                        { data: 'created_at', render: function (d) { return fmtFecha(d); } }
                    ],
                    order: [[6, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    buttons: [
                        { extend: 'copy', className: 'button' },
                        { extend: 'csv', className: 'button', title: 'invitados_wifi' },
                        { extend: 'excel', className: 'button', title: 'invitados_wifi' },
                        { extend: 'print', className: 'button' }
                    ],
                    language: {
                        processing: 'Cargando...',
                        lengthMenu: 'Mostrar _MENU_ registros',
                        zeroRecords: 'No se encontraron resultados',
                        emptyTable: 'No hay invitados registrados.',
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                        infoFiltered: '(filtrado de _MAX_ registros totales)',
                        search: 'Buscar:',
                        paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
                    }
                });
            });
        })();
    </script>

    <script src='../../backend/js/submenu.js'></script>
</body>
</html>
