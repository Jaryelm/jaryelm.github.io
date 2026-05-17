<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">

    <title>MEDIDATA</title>

    <style>
        /* Refinamiento de estilos para integrarse con admin.css */
        .content-data {
            margin-top: 20px;
        }

        .table-title h1 {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-blue);
            margin-bottom: 5px;
        }

        /* Estilo para los botones de DataTables (Mantenidos según preferencia) */
        .dt-buttons .dt-button {
            background-color: var(--blue) !important;
            color: white !important;
            border: none !important;
            border-radius: 5px !important;
            padding: 5px 15px !important;
            margin-right: 5px !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
        }

        .dt-buttons .dt-button:hover {
            background-color: var(--dark-blue) !important;
            transform: translateY(-2px);
        }

        .dataTables_filter input {
            padding: 6px 10px !important;
            border: 1px solid var(--grey) !important;
            border-radius: 5px !important;
            outline: none !important;
        }

        .dataTables_filter input:focus {
            border-color: var(--blue) !important;
        }
    </style>
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

            <div class="data">
                <div class="content-data">
                    <div class="head">
                        <h3>Lista de Colaboradores</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table id="colaboradores_table" class="display nowrap">
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
    <script>
        $(document).ready(() => {
            loadCollaborators();

            function loadCollaborators() {
                $.ajax({
                    url: '../../backend/registros/recursos_humanos/fetch_collaborators.php',
                    type: "GET",
                    success: function(data) {
                        const collaborators = JSON.parse(data);
                        const tbody = $('#colaboradores_body');
                        tbody.empty();

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
                            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
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
                    error: function(xhr, status, error) {
                        alert(error);
                    }
                });
            };
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

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