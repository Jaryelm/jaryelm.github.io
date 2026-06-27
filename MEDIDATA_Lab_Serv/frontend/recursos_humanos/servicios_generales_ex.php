<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" type="text/css" href="../../backend/css/datatable.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/buttonsdataTables.css">
    <link rel="stylesheet" type="text/css" href="../../backend/css/font.css">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
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
        $hora = (int) date('H');
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos DÃ­as' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="rrhh-tab-nav" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
    <a href="servicios_generales.php" class="button tab-button <?php echo ($current_page == 'servicios_generales.php' || $current_page == 'servicios_generales_usr.php') ? 'active' : ''; ?>">Personal Activo</a>
    <a href="servicios_generales_ex.php" class="button tab-button <?php echo ($current_page == 'servicios_generales_ex.php' || $current_page == 'servicios_generales_ex_usr.php') ? 'active' : ''; ?>">Ex Colaboradores</a>
    <a href="agregar_colaborador.php" class="button tab-button" style="background-color: #28a745; color: white;">Agregar Colaborador</a>
</div>
<div class="data">
            <div class="content-data">
                <div class="head"><h3>Ex Personal de Servicios Generales (Inactivos)</h3></div>
                <div class="table-responsive" style="overflow-x:auto;">
                    <?php
                    $sentencia = $connect->prepare("SELECT * FROM staff_general_services WHERE state = '0' ORDER BY idsg DESC");
                    $sentencia->execute();
                    $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
                    ?>
                    <?php if (count($data) > 0): ?>
                    <table id="example" class="responsive-table">
                        <thead>
                            <tr>
                                <th>DNI</th>
                                <th>Colaborador</th>
                                <th>Ãrea</th>
                                <th>Sexo</th>
                                <th>Fecha Nacimiento</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $d): ?>
                            <tr>
                                <th scope="row"><?php echo htmlspecialchars($d->numide); ?></th>
                                <td><?php echo htmlspecialchars($d->nomsg . ' ' . $d->apesg); ?></td>
                                <td><?php echo htmlspecialchars($d->area ?? 'â€”'); ?></td>
                                <td><?php echo htmlspecialchars($d->sexsg); ?></td>
                                <td><?php echo htmlspecialchars($d->nacsg); ?></td>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" class="staff-sg-state-toggle" data-id="<?php echo (int) $d->idsg; ?>" <?php echo $d->state == '1' ? 'checked' : ''; ?>/>
                                        <span class="slider"></span>
                                    </label>
                                </td>
                                <td>
                                    <a title="Actualizar" href="servicios_generales_editar.php?id=<?php echo (int) $d->idsg; ?>" class="fa fa-pencil tooltip"></a>
                                    <a title="Eliminar" href="#" class="fa fa-trash tooltip btn-delete-sg" data-id="<?php echo (int) $d->idsg; ?>"></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="alert">
                        <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                        <strong>Sin datos</strong> No hay colaboradores de servicios generales registrados.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/registros/script/tabla_personal_staff.js"></script>
<script src="../../backend/registros/script/inline_editing.js"></script>
<script src="../../backend/js/datatable.js"></script>
<script src="../../backend/js/datatablebuttons.js"></script>
<script src="../../backend/js/jszip.js"></script>
<script src="../../backend/js/pdfmake.js"></script>
<script src="../../backend/js/vfs_fonts.js"></script>
<script src="../../backend/js/buttonshtml5.js"></script>
<script src="../../backend/js/buttonsprint.js"></script>
<script>
window.MEDIDATA_STAFF_SG = {
    toggleSelector: '.staff-sg-state-toggle',
    deleteSelector: '.btn-delete-sg',
    toggleUrl: '../../backend/php/toggle_general_services_state.php',
    deleteUrl: '../../backend/php/delete_general_services.php',
    idParam: 'idsg',
    deleteTitle: 'Â¿Eliminar colaborador de servicios generales?',
    deleteFn: 'deleteGeneralServices'
};
$(document).ready(function () {
    if ($('#example').length) {
        $('#example').DataTable({
            pageLength: 10,
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
            language: {
                sProcessing: 'Procesando...',
                sLengthMenu: 'Mostrar _MENU_ registros',
                sZeroRecords: 'No se encontraron resultados',
                sInfo: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                sInfoEmpty: 'Mostrando 0 a 0 de 0 registros',
                sInfoFiltered: '(filtrado de _MAX_ registros totales)',
                sSearch: 'Buscar:',
                oPaginate: { sFirst: 'Primero', sLast: 'Ãšltimo', sNext: 'Siguiente', sPrevious: 'Anterior' }
            }
        });
    }
});
</script>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>

