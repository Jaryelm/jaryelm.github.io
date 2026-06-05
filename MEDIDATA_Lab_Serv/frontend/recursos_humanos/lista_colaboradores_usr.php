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
    include_once './menu.php';
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
            include_once './perfil.php';
            // incuir el archivo menu principal
            ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

            <div class="rrhh-tab-nav">
                <a href="lista_colaboradores_usr.php" class="button tab-button active">Lista de Colaboradores</a>
                <a href="lista_excolaboradores_usr.php" class="button tab-button">Lista de Excolaboradores</a>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="table-title">
                        <h1>Lista de Colaboradores</h1>
                    </div>
                    
                    <div class="table-responsive" style="overflow-x:auto;">
                        <table id="colaboradores_table" class="responsive-table" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">N°</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Cedula</th>
                                    <th scope="col">Tipo Empleado</th>
                                    <th scope="col">Sexo</th>
                                    <th scope="col">Especialidad</th>
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

                        if (!Array.isArray(collaborators)) {
                            tbody.append('<tr><td colspan="6">No se pudieron cargar los colaboradores.</td></tr>');
                            return;
                        }

                        let counter = 1;
                        collaborators.forEach((collaborator) => {
                            if (collaborator.Estado !== '1') return; // Solo activos

                            const row = `
                                <tr>
                                    <td data-title="N°">${counter++}</td>
                                    <td data-title="Nombre">${collaborator.Nombre}</td>
                                    <td data-title="Cedula">${collaborator.Cedula}</td>
                                    <td data-title="Tipo Empleado">${collaborator.Tipo_Empleado}</td>
                                    <td data-title="Sexo">${collaborator.Sexo}</td>
                                    <td data-title="Especialidad">${collaborator.Especialidad}</td>
                                </tr>
                            `;
                            tbody.append(row);
                        });

                        function reEnumerateTable() {
                            $('#colaboradores_body tr').each(function(index) {
                                $(this).find('td:first').text(index + 1);
                            });
                        }

                        $('#colaboradores_table').DataTable({
                            destroy: true,
                            pageLength: 10,
                            dom: 'Bfrtip',
                            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
                            order: [
                                [0, 'asc']
                            ], // Orden ascendente en la columna de N°
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
                    error: function(xhr, status, error) {
                        Swal.fire('Error', error || 'No se pudieron cargar los colaboradores', 'error');
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