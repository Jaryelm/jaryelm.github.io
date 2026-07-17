<?php
include_once '../../backend/registros/session_check.php';
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'Administrador') {
    $ctx = isset($_GET['contexto']) ? ('?contexto=' . urlencode((string) $_GET['contexto'])) : '';
    header('Location: agregar_colaborador_usr.php' . $ctx);
    exit;
}
require_once '../../backend/php/staff_colaborador_bootstrap.php';
require_once '../../backend/php/staff_areas_lib.php';
medidata_staff_ensure_tables($connect);

$staffUsers = medidata_staff_fetch_users_for_select($connect);

$cargos = [];
try {
    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
    $stmt_p->execute();
    $cargos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$contexto = isset($_GET['contexto']) ? trim((string) $_GET['contexto']) : 'colaboradores';
if (!in_array($contexto, medidata_staff_lista_contextos(), true)) {
    $contexto = 'colaboradores';
}
$esMedico = ($contexto === 'medicos');
$esMedifarma = ($contexto === 'medifarma');

$return_page = medidata_staff_return_page_for_context($contexto, true);
$staffFormIsAdmin = true;

$form_titulo = 'Nuevo Colaborador';
if ($esMedico) $form_titulo = 'Nuevo Médico';
if ($esMedifarma) $form_titulo = 'Nuevo Colaborador Medifarma';

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
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>
<?php 
    include_once '../admin/menu.php'; 
?>
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
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos Días' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        
        <div class="rrhh-tab-nav">
            <a href="lista_colaboradores.php" class="button tab-button<?php echo ($contexto === 'colaboradores') ? ' active' : ''; ?>">Lista de Colaboradores</a>
            <a href="lista_colaboradores_medicos.php" class="button tab-button<?php echo $esMedico ? ' active' : ''; ?>">Lista de Médicos</a>
            <a href="lista_colaboradores_medifarma.php" class="button tab-button<?php echo $esMedifarma ? ' active' : ''; ?>">Lista Medifarma</a>
            <a href="lista_excolaboradores.php" class="button tab-button">Lista de Excolaboradores</a>
        </div>

        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
            <input type="hidden" name="return_page" value="<?php echo htmlspecialchars($return_page); ?>">
            <input type="hidden" name="lista_contexto" value="<?php echo htmlspecialchars($contexto); ?>">
            <div class="containerss staff-form">
                <h1><?php echo htmlspecialchars($form_titulo); ?></h1>
                <?php include __DIR__ . '/_staff_colaborador_form_fields.php'; ?>
                <div class="staff-form-actions">
                    <button type="submit" name="add_colaborador" class="registerbtn">Guardar</button>
                </div>
            </div>
        </form>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>
<script src="../../backend/js/script.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/add_colaborador.php'; ?>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>
<script src="../../backend/js/cat_schedules.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') return;
    (function ($) {
        'use strict';
        var $chkHonorarios = $('#por_honorarios');
        var $idSalaryLevel = $('#id_salary_level');
        var $salario = $('input[name="salario"]');

        function toggleHonorarios() {
            var isChecked = $chkHonorarios.is(':checked');
            if (isChecked) {
                $idSalaryLevel.prop('required', false).prop('disabled', true).val('').trigger('change');
                $salario.prop('disabled', true).val('');
            } else {
                $idSalaryLevel.prop('required', true).prop('disabled', false);
                $salario.prop('disabled', false);
            }
        }
        $chkHonorarios.on('change', toggleHonorarios);
        setTimeout(toggleHonorarios, 100);
    })(jQuery);
});
</script>
</body>
</html>


