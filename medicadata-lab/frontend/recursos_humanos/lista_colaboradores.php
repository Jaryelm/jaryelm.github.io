<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">

    <title>MEDIDATA</title>
</head>

<body>
    <?php
    include_once '../admin/menu.php';
    // incuir el archivo menu principal
    ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php
            include_once '../admin/perfil.php';
            // incuir el archivo menu principal
            ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

            <div class="data">
                <div class="content-data">
                    <div class="table-title">
                        <h1>Lista de Colaboradores</h1>
                    </div>
                    
                    <div class="table-container">
                        <table id="colaboradores_table" class="display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Cedula</th>
                                    <th>Tipo Empleado</th>
                                    <th>Sexo</th>
                                    <th>Especialidad</th>
                                </tr>
                            </thead>
                            <tbody id="colaboradores_body">
                                <!-- Los datos se cargarán dinámicamente con JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script>
        $(document).ready(() => {
            loadCollaborators();

            function loadCollaborators() {
                $.ajax({
                    url: '../../backend/registros/recursos_humanos/fetch_collaborators.php',
                    type: "GET",
                    dataType: 'json',
                    success: function(collaborators) {
                        const tbody = $('#colaboradores_body');
                        tbody.empty();

                        if (collaborators && collaborators.error) {
                            tbody.append('<tr><td colspan="6">' + collaborators.error + '</td></tr>');
                            Swal.fire('Error', collaborators.error, 'error');
                            return;
                        }

                        if (!Array.isArray(collaborators)) {
                            tbody.append('<tr><td colspan="6">No se pudieron cargar los colaboradores.</td></tr>');
                            return;
                        }

                        if (collaborators.length === 0) {
                            tbody.append('<tr><td colspan="6">No hay colaboradores registrados.</td></tr>');
                            return;
                        }

                        collaborators.forEach((collaborator) => {
                            const row = `
                                <tr>
                                    <td>${collaborator.ID}</td>
                                    <td>${collaborator.Nombre}</td>
                                    <td>${collaborator.Cedula}</td>
                                    <td>${collaborator.Tipo_Empleado}</td>
                                    <td>${collaborator.Sexo}</td>
                                    <td>${collaborator.Especialidad}</td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                        $('#colaboradores_table').DataTable({
                            destroy: true,
                            pageLength: 10,
                            dom: 'Bfrtip',
                            buttons: [
                                { extend: 'copy', className: 'button' },
                                { extend: 'csv', className: 'button' },
                                { extend: 'excel', className: 'button' },
                                { extend: 'pdf', className: 'button' },
                                { extend: 'print', className: 'button' }
                            ],
                            order: [
                                [0, 'desc']
                            ], // Orden descendente en la columna de ID
                            scrollX: true,
                            scrollCollapse: true,
                            language: {
                                "sProcessing": "Procesando...",
                                "sLengthMenu": "Mostrar _MENU_ registros",
                                "sZeroRecords": "No se encontraron resultados",
                                "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                                "sInfoEmpty": "Mostrando 0 a 0 de 0 registros",
                                "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
                                "sSearch": "Buscar:",
                                "oPaginate": {
                                    "sFirst": "Primero",
                                    "sLast": "Último",
                                    "sNext": "Siguiente",
                                    "sPrevious": "Anterior"
                                }
                            }
                        });
                    },
                    error: function(xhr) {
                        let msg = 'No se pudieron cargar los colaboradores';
                        try {
                            const r = xhr.responseJSON || JSON.parse(xhr.responseText);
                            if (r && r.error) msg = r.error;
                            else if (r && r.message) msg = r.message;
                        } catch (e) {}
                        $('#colaboradores_body').html('<tr><td colspan="6">' + msg + '</td></tr>');
                        Swal.fire('Error', msg, 'error');
                    }
                });
            };
        });
    </script>

    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>

    <!-- SubMenu -->
    <script src='../../backend/js/submenu.js'></script>

    <!-- Script para manejar el cambio de color en los botones -->
    <script src="../../backend/registros/script/botones_color.js"></script>

    <!-- NAVBAR -->
    <script src="../../backend/js/script.js"></script>
</body>

</html>