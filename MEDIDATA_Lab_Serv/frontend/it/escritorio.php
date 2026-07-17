<?php
include_once '../../backend/registros/session_check.php';
include_once '../../backend/registros/it_guard.php';

date_default_timezone_set('America/Tegucigalpa');

// Métricas de usuarios para el panel de IT.
$totalUsuarios = (int) $connect->query("SELECT COUNT(*) FROM users")->fetchColumn();
$usuariosInactivos = (int) $connect->query("SELECT COUNT(*) FROM users WHERE state = '0'")->fetchColumn();
$usuariosActivos = $totalUsuarios - $usuariosInactivos;

$usuariosPorRol = $connect->query(
    "SELECT COALESCE(NULLIF(TRIM(rol), ''), 'Sin rol') AS rol, COUNT(*) AS n
     FROM users
     GROUP BY rol
     ORDER BY n DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$hora_actual = (int) date('H');
if ($hora_actual >= 6 && $hora_actual < 12) {
    $saludo = 'Buenos Días';
} elseif ($hora_actual >= 12 && $hora_actual < 18) {
    $saludo = 'Buenas Tardes';
} else {
    $saludo = 'Buenas Noches';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='../../backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <title>MEDIDATA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #000;
        }

        .dashboard-container {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
        }

        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }

        .card {
            background-color: #06adbf;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #fff;
            transition: transform 0.3s ease;
        }

        .card h2 {
            margin: 0 0 8px;
            font-size: 16px;
            color: #fff;
            font-weight: 600;
        }

        .card p {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            color: #fff;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card.alt {
            background-color: #035c67;
        }

        .card.warn {
            background-color: #c0392b;
        }

        .it-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .it-badge.on {
            background: #e3f9e5;
            color: #1b7d2b;
        }

        .it-badge.off {
            background: #fde8e8;
            color: #b91c1c;
        }

        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>

<?php include_once '../it/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
<?php include_once '../it/perfil.php'; ?>
        </nav>

        <main>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

            <div class="dashboard-container">
                <header>
                    <h1 style="margin:0;">Panel de IT</h1>
                    <p style="margin:4px 0 0; color:#555;">Gestión de usuarios y accesos del sistema MEDIDATA</p>
                </header>

                <div class="dashboard">
                    <div class="card">
                        <h2>Usuarios Totales</h2>
                        <p><?php echo $totalUsuarios; ?></p>
                    </div>
                    <div class="card alt">
                        <h2>Usuarios Activos</h2>
                        <p><?php echo $usuariosActivos; ?></p>
                    </div>
                    <div class="card warn">
                        <h2>Usuarios Desactivados</h2>
                        <p><?php echo $usuariosInactivos; ?></p>
                    </div>
                    <div class="card alt">
                        <h2>Roles Distintos</h2>
                        <p><?php echo count($usuariosPorRol); ?></p>
                    </div>
                </div>

                <br>
                <header style="text-align:center; margin-bottom:10px;">
                    <h1 style="margin:0;">Usuarios por Rol</h1>
                </header>
                <div class="table-responsive">
                    <table id="tabla_roles" class="responsive-table" style="width:100%;">
                        <thead>
                            <tr><th>Rol</th><th>Cantidad</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuariosPorRol as $fila): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fila['rol']); ?></td>
                                <td><?php echo (int) $fila['n']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <br>
                <header style="text-align:center; margin-bottom:10px;">
                    <h1 style="margin:0;">Accesos de Usuarios</h1>
                </header>
                <div class="table-responsive">
                    <table id="tabla_accesos" class="responsive-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Última actividad</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
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
            var idiomaDataTable = {
                lengthMenu: 'Mostrar _MENU_ registros',
                zeroRecords: 'No se encontraron resultados',
                emptyTable: 'No hay registros disponibles.',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros totales)',
                search: 'Buscar:',
                paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
            };

            $(function () {
                $('#tabla_roles').DataTable({
                    dom: 'Bfrtip',
                    order: [[1, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Todos']],
                    buttons: [
                        { extend: 'copy', className: 'button' },
                        { extend: 'csv', className: 'button', title: 'usuarios_por_rol' },
                        { extend: 'excel', className: 'button', title: 'usuarios_por_rol' },
                        { extend: 'print', className: 'button' }
                    ],
                    language: idiomaDataTable
                });

                $('#tabla_accesos').DataTable({
                    processing: true,
                    serverSide: true,
                    dom: 'Bfrtip',
                    scrollX: true,
                    order: [[4, 'desc']],
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                    ajax: {
                        url: '../../backend/php/get_usuarios.php',
                        type: 'GET'
                    },
                    columns: [
                        { data: 'name', render: function (d) { return esc(d) || '—'; } },
                        { data: 'username', render: function (d) { return esc(d) || '—'; } },
                        { data: 'rol', render: function (d) { return esc(d) || '—'; } },
                        {
                            data: 'state',
                            render: function (d) {
                                return (String(d) === '0')
                                    ? '<span class="it-badge off">Desactivado</span>'
                                    : '<span class="it-badge on">Activo</span>';
                            }
                        },
                        { data: 'last_activity', render: function (d) { return esc(d) || 'Sin registro'; } }
                    ],
                    buttons: [
                        { extend: 'copy', className: 'button' },
                        { extend: 'csv', className: 'button', title: 'accesos_usuarios' },
                        { extend: 'excel', className: 'button', title: 'accesos_usuarios' },
                        { extend: 'print', className: 'button' }
                    ],
                    language: idiomaDataTable
                });
            });
        })();
    </script>

    <script src='../../backend/js/submenu.js'></script>
</body>
</html>
