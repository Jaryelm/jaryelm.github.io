<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$pdoRrhh = medidata_rrhh_pdo();
$schedules = [];
if ($pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare("SELECT * FROM schedules WHERE deleted = 0 ORDER BY name ASC");
        $stmt->execute();
        $schedules = $stmt->fetchAll(PDO::FETCH_OBJ);
    } catch (Throwable $e) {
        error_log($e->getMessage());
    }
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

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">

    <title>MEDIDATA - Horarios Laborales</title>
</head>
<body>
    <?php include_once '../admin/menu.php'; ?>
    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'horarios.php')">Listar Horarios</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_horario.php')">Registrar Nuevo Horario</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Gestión de Horarios</h3>
                </div>
                
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php if(count($schedules) > 0): ?>
                        <table id="example" class="responsive-table">
                            <thead>
                                <tr>
                                    <th>Nombre del Horario</th>
                                    <th>Días/Horas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules as $d): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($d->name); ?></td>
                                    <td>
                                        <small>
                                        <?php 
                                        $stmtD = $pdoRrhh->prepare("SELECT day, LEFT(entry_time, 5) as entry, LEFT(exit_time, 5) as exit_t FROM schedule_details WHERE id_schedule = ? ORDER BY FIELD(day, 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do')");
                                        $stmtD->execute([$d->id]);
                                        $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);
                                        $dayStr = [];
                                        foreach ($details as $det) {
                                            $dayStr[] = $det['day'] . " (" . $det['entry'] . "-" . $det['exit_t'] . ")";
                                        }
                                        echo implode(", ", $dayStr);
                                        ?>
                                        </small>
                                    </td>
                                    <td style="text-align: center;">
                                        <label class="switch">
                                            <input type="checkbox" class="state-toggle" data-id="<?php echo $d->id; ?>" checked>
                                            <span class="slider"></span>
                                        </label>
                                    </td>
                                    <td style="text-align: center;">
                                        <a title="Editar" href="registrar_horario.php?id=<?php echo $d->id; ?>" class="fa fa-edit" style="color:#06adbf; background:none; border:none; cursor:pointer; font-size: 1.2rem; text-decoration:none;"></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>¡Aviso!</strong> No hay datos registrados.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
    
    <!-- Data Tables -->
    <script type="text/javascript" src="../../backend/js/datatable.js"></script>
    <script type="text/javascript" src="../../backend/js/datatablebuttons.js"></script>
    <script type="text/javascript" src="../../backend/js/jszip.js"></script>
    <script type="text/javascript" src="../../backend/js/pdfmake.js"></script>
    <script type="text/javascript" src="../../backend/js/vfs_fonts.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonshtml5.js"></script>
    <script type="text/javascript" src="../../backend/js/buttonsprint.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        $('#example').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
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

        $('.state-toggle').on('change', function() {
            var id = $(this).data('id');
            var self = this;
            Swal.fire({
                title: "¿Estás seguro?",
                text: "Este horario dejará de estar disponible para nuevas vacantes.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Sí, cambiar estado"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('../../backend/php/toggle_schedule_state.php', { id: id }, function(response) {
                        if (response.success) {
                            Swal.fire("¡Éxito!", response.message, "success").then(() => window.location.reload());
                        } else {
                            $(self).prop('checked', !$(self).is(':checked'));
                            Swal.fire("Error", response.message, "error");
                        }
                    }, 'json');
                } else {
                    $(self).prop('checked', !$(self).is(':checked'));
                }
            });
        });
    });
    </script>
</body>
</html>
