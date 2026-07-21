<?php
include_once '../../backend/registros/session_check.php';

require_once '../../backend/php/staff_colaborador_bootstrap.php';
require_once '../../backend/php/staff_areas_lib.php';
require_once '../../backend/registros/rrhh_guard.php';
medidata_staff_ensure_tables($connect);

$prefillColab = [
    'identificacion' => '',
    'nombres' => '',
    'apellidos' => '',
    'fecha_nacimiento' => '',
    'telefono' => '',
    'correo_personal' => '',
];
$id_candidato = isset($_GET['id_candidato']) ? (int) $_GET['id_candidato'] : 0;
$candidatoContratado = null;
if ($id_candidato > 0) {
    require_once '../../backend/php/rrhh_candidato_workflow_lib.php';
    $pdoRrhh = medidata_rrhh_pdo();
    if ($pdoRrhh) {
        $stmtCand = $pdoRrhh->prepare(
            'SELECT fullname, dni, email, phonenumber, birthdate FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1'
        );
        $stmtCand->execute([$id_candidato]);
        $candidatoContratado = $stmtCand->fetch(PDO::FETCH_ASSOC);
        if ($candidatoContratado) {
            $parts = medidata_rrhh_candidate_name_parts((string) ($candidatoContratado['fullname'] ?? ''));
            $prefillColab['identificacion'] = (string) ($candidatoContratado['dni'] ?? '');
            $prefillColab['nombres'] = $parts['nombres'];
            $prefillColab['apellidos'] = $parts['apellidos'];
            $prefillColab['telefono'] = (string) ($candidatoContratado['phonenumber'] ?? '');
            $prefillColab['correo_personal'] = (string) ($candidatoContratado['email'] ?? '');
            $bd = (string) ($candidatoContratado['birthdate'] ?? '');
            if ($bd !== '' && strpos($bd, '0000') === false) {
                $prefillColab['fecha_nacimiento'] = substr($bd, 0, 10);
            }
        }
    }
}

function medidata_colab_prefill_val(array $prefill, string $key): string
{
    return htmlspecialchars((string) ($prefill[$key] ?? ''), ENT_QUOTES, 'UTF-8');
}

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

$return_page = medidata_staff_return_page_for_context($contexto, false);
$staffFormIsAdmin = false;

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
    include_once './menu.php'; 
?>
<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once './perfil.php'; ?>
    </nav>
    <main>
        <?php
        $hora = (int) date('H');
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos Días' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        <?php if ($candidatoContratado): ?>
        <div class="alert" style="background:#d4edda;border-color:#81D43A;color:#155724;">
            <strong>Candidato contratado:</strong> complete el registro de colaborador con los datos precargados.
            <a href="lista_colaboradores_usr.php" class="button" style="margin-left:12px;">Ir a lista de colaboradores</a>
        </div>
        <?php endif; ?>
        
        <div class="rrhh-tab-nav">
            <a href="lista_colaboradores_usr.php" class="button tab-button<?php echo ($contexto === 'colaboradores') ? ' active' : ''; ?>">Lista de Colaboradores</a>
            <a href="lista_colaboradores_medicos_usr.php" class="button tab-button<?php echo $esMedico ? ' active' : ''; ?>">Lista de Médicos</a>
            <a href="lista_colaboradores_medifarma_usr.php" class="button tab-button<?php echo $esMedifarma ? ' active' : ''; ?>">Lista Medifarma</a>
            <a href="lista_excolaboradores_usr.php" class="button tab-button">Lista de Excolaboradores</a>
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


