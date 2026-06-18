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
    <link rel="stylesheet" href="../../backend/css/cards.css">
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
                <a href="lista_colaboradores_usr.php" class="button tab-button">Lista de Colaboradores</a>
                <a href="lista_colaboradores_medicos_usr.php" class="button tab-button active">Lista de Médicos</a>
                <a href="lista_excolaboradores_usr.php" class="button tab-button">Lista de Excolaboradores</a>
            </div>

            <div class="data">
                <div class="content-data">
                    <div class="table-title">
                        <h1>Lista de Médicos</h1>
                    </div>

                    <div class="table-responsive" style="overflow-x:auto;">
                        <table id="medicos_table" class="responsive-table" style="width:100%">
                            <thead>
                                <tr id="medicos_head">
                                    <!-- Encabezados generados dinámicamente -->
                                </tr>
                            </thead>
                            <tbody id="medicos_body">
                                <!-- Los médicos se cargarán dinámicamente con JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </section>
    <?php include __DIR__ . '/_rrhh_extra_modal.php'; ?>
    <script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

    <script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/registros/script/rrhh_colaborador_extra.js"></script>
    <script>
        $(document).ready(() => {
            loadMedicos();

            function loadMedicos() {
                $.ajax({
                    url: RRHH_COLAB.endpoint,
                    type: "GET",
                    dataType: 'json',
                    success: function(collaborators) {
                        const tbody = $('#medicos_body');
                        tbody.empty();
                        $('#medicos_head').html(RRHH_COLAB.headRow());

                        if (!Array.isArray(collaborators)) {
                            tbody.append('<tr><td colspan="' + RRHH_COLAB.colCount() + '">No se pudieron cargar los médicos.</td></tr>');
                            return;
                        }

                        let counter = 1;
                        collaborators.forEach((collaborator) => {
                            if (collaborator.Estado !== '1') return; // Solo activos
                            if (collaborator.Tipo_Empleado !== 'Doctor') return; // Solo médicos
                            tbody.append(RRHH_COLAB.bodyRow(collaborator, counter++));
                        });

                        $('.toggle-state').on('change', function() {
                            const checkbox = $(this);
                            const row = checkbox.closest('tr');
                            const id = checkbox.data('id');
                            const type = checkbox.data('type');
                            const newState = checkbox.is(':checked') ? 1 : 0;

                            const endpoints = {
                                'Doctor': '../../backend/php/toggle_doctor_state.php',
                                'Enfermero': '../../backend/php/toggle_nurse_state.php',
                                'Administrativo': '../../backend/php/toggle_administrative_state.php',
                                'Servicios Generales': '../../backend/php/toggle_general_services_state.php',
                                'Usuario': '../../backend/php/toggle_user_state.php'
                            };

                            const url = endpoints[type];
                            if (!url) {
                                Swal.fire('Error', 'Tipo de empleado no reconocido.', 'error');
                                checkbox.prop('checked', !checkbox.is(':checked'));
                                return;
                            }

                            $.ajax({
                                url: url,
                                type: 'POST',
                                data: { id: id, state: newState },
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        Swal.fire({
                                            title: 'Éxito',
                                            text: response.message,
                                            icon: 'success',
                                            timer: 1500,
                                            showConfirmButton: false
                                        });
                                        row.fadeOut(400, function() {
                                            $(this).remove();
                                            reEnumerateTable();
                                        });
                                    } else {
                                        Swal.fire('Error', response.message || 'No se pudo actualizar el estado.', 'error');
                                        checkbox.prop('checked', !checkbox.is(':checked'));
                                    }
                                },
                                error: function() {
                                    Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                                    checkbox.prop('checked', !checkbox.is(':checked'));
                                }
                            });
                        });

                        function reEnumerateTable() {
                            $('#medicos_body tr').each(function(index) {
                                $(this).find('td:first').text(index + 1);
                            });
                        }

                        const medicosTable = $('#medicos_table').DataTable({
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

                        function ajustarColumnasMedicos() {
                            medicosTable.columns.adjust();
                        }
                        $('.toggle-sidebar').off('click.medicos').on('click.medicos', function () {
                            setTimeout(ajustarColumnasMedicos, 350);
                        });
                        $(window).off('resize.medicos').on('resize.medicos', ajustarColumnasMedicos);
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error', error || 'No se pudieron cargar los médicos', 'error');
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
