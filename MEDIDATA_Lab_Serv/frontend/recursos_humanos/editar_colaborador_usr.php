<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
require_once '../../backend/php/staff_areas_lib.php';
require_once '../../backend/registros/rrhh_guard.php';
medidata_staff_ensure_tables($connect);

$id = (int) ($_GET['id'] ?? 0);
$table = $_GET['table'] ?? '';

if (!medidata_staff_area_is_valid($table)) {
    die('Área de empleado no válida.');
}

$areaCols = medidata_staff_area_columns($table);
if (!$areaCols) {
    die('Área de empleado no válida.');
}

$col_id = $areaCols['pk'];
$col_numide = $areaCols['numide'];
$col_nombres = $areaCols['nombres'];
$col_apellidos = $areaCols['apellidos'];
$col_nac = $areaCols['nacimiento'];
$col_sexo = $areaCols['genero'];

$doctorExtraSelect = ($table === 'doctor') ? ', nomesp, direcd, comisiona' : '';
$stmt_a = $connect->prepare("
        SELECT {$col_id} AS id_primary, id_user, {$col_numide} AS numide, {$col_nombres} AS nombres, {$col_apellidos} AS apellidos, {$col_nac} AS nacimiento, {$col_sexo} AS sexo, state,
               num_empleado, tipo_empleado, duracion_contrato, fecha_ingreso,
               id_departamento, id_cargo, id_horario, id_salary_level, salario,
               cuenta_bac, telefono, correo_personal, correo_institucional,
               num_locker, id_biometrico, id_candidate_rrhh,
               (url_contrato IS NOT NULL AND url_contrato != '') AS has_contrato,
               (url_solicitud IS NOT NULL AND url_solicitud != '') AS has_solicitud,
               (url_psicometricas IS NOT NULL AND url_psicometricas != '') AS has_psicometricas{$doctorExtraSelect}
        FROM {$table}
        WHERE {$col_id} = ? LIMIT 1
    ");
$stmt_a->execute([$id]);
$data = $stmt_a->fetchAll(PDO::FETCH_OBJ);

$staffUsers = medidata_staff_fetch_users_for_select($connect);

// Consultar lista de cargos
$cargos = [];
try {
    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
    $stmt_p->execute();
    $cargos = $stmt_p->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$rrhh_docs = [];
if (count($data) > 0 && !empty($data[0]->id_candidate_rrhh)) {
    require_once __DIR__ . '/../../backend/php/staff_form_docs_lib.php';
    $rrhh_docs = medidata_staff_load_hiring_docs_flags((int) $data[0]->id_candidate_rrhh);
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
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>
<?php include_once './menu.php'; ?>
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
        <?php
        $contexto = 'colaboradores';
        $return_page = 'lista_colaboradores_usr.php';
        if ($table === 'doctor') {
            $contexto = 'medicos';
            $return_page = 'lista_colaboradores_medicos_usr.php';
        } elseif ($table === 'staff_medifarma') {
            $contexto = 'medifarma';
            $return_page = 'lista_colaboradores_medifarma_usr.php';
        }
        $esMedico = ($contexto === 'medicos');
        $esMedifarma = ($contexto === 'medifarma');
        ?>
        <div class="rrhh-tab-nav">
            <a href="lista_colaboradores_usr.php" class="button tab-button<?php echo ($contexto === 'colaboradores') ? ' active' : ''; ?>">Lista de Colaboradores</a>
            <a href="lista_colaboradores_medicos_usr.php" class="button tab-button<?php echo $esMedico ? ' active' : ''; ?>">Lista de Médicos</a>
            <a href="lista_colaboradores_medifarma_usr.php" class="button tab-button<?php echo $esMedifarma ? ' active' : ''; ?>">Lista Medifarma</a>
        </div>

        <?php if (count($data) > 0): foreach ($data as $d): ?>
        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="return_page" value="<?php echo htmlspecialchars($return_page); ?>">
            <div class="containerss staff-form staff-edit-form">
                <h1>Actualizar colaborador</h1>
                <input type="hidden" name="id_primary" value="<?php echo (int) $d->id_primary; ?>">
                <input type="hidden" name="area_colaborador" value="<?php echo htmlspecialchars($table); ?>">

                <div class="staff-form-section">
                <h3 class="staff-form-section__title">Datos personales</h3>
                <div class="staff-form-grid">
                <div>
                <label><b>N° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" value="<?php echo htmlspecialchars($d->num_empleado ?? ''); ?>" placeholder="ejm: EMP-001">
                </div>
                <div>
                <label><b>N° de identificación (DNI)</b></label><span class="badge-warning">*</span>
                <input type="text" name="identificacion" maxlength="14" value="<?php echo htmlspecialchars($d->numide); ?>" required>
                </div>
                <div>
                <label><b>Nombres</b></label><span class="badge-warning">*</span>
                <input type="text" name="nombres" value="<?php echo htmlspecialchars($d->nombres); ?>" required>
                </div>
                <div>
                <label><b>Apellidos</b></label><span class="badge-warning">*</span>
                <input type="text" name="apellidos" value="<?php echo htmlspecialchars($d->apellidos); ?>" required>
                </div>
                <div>
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="fecha_nacimiento" value="<?php echo htmlspecialchars($d->nacimiento); ?>" required>
                </div>
                <div>
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="genero" required>
                    <option value="Masculino" <?php echo $d->sexo === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $d->sexo === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>
                </div>
                </div>
                </div>

                <?php if ($table === 'doctor'): ?>
                <?php $staffDoctorRow = $d; include __DIR__ . '/_staff_doctor_fields.php'; ?>
                <?php endif; ?>

                <div class="staff-form-section">
                <h3 class="staff-form-section__title">Información laboral</h3>
                <div class="staff-form-grid">
                <div>
                <label><b>Tipo de Empleado</b></label><span class="badge-warning">*</span>
                <select class="select2" name="tipo_empleado" id="tipo_empleado" required onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
                    <option value="Permanente" <?php echo ($d->tipo_empleado ?? '') === 'Permanente' ? 'selected' : ''; ?>>Permanente</option>
                    <option value="Temporal" <?php echo ($d->tipo_empleado ?? '') === 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                    <option value="Tiempo parcial" <?php echo ($d->tipo_empleado ?? '') === 'Tiempo parcial' ? 'selected' : ''; ?>>Tiempo parcial</option>
                </select>
                </div>
                <div id="duracion_contrato_div" class="staff-form-field--full" style="display:<?php echo in_array($d->tipo_empleado ?? '', ['Temporal', 'Tiempo parcial']) ? 'block' : 'none'; ?>;">
                    <label><b>Duración de Contrato</b></label>
                    <input type="text" name="duracion_contrato" value="<?php echo htmlspecialchars($d->duracion_contrato ?? ''); ?>" placeholder="Ej: 6 meses">
                </div>
                <div>
                <label><b>Fecha de Ingreso</b></label>
                <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($d->fecha_ingreso ?? ''); ?>">
                </div>
                <div>
                <label><b>Departamento</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_departamento" id="id_departament" required>
                    <option value="<?php echo (int)($d->id_departamento ?? 0); ?>" selected>Cargando...</option>
                </select>
                </div>
                <div>
                <label><b>Cargo / Posición</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_cargo" required>
                    <option value="" disabled>Seleccione...</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>" <?php echo ($d->id_cargo ?? 0) == $cargo['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cargo['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                </div>
                <div>
                <label><b>Horario</b></label>
                <select class="select2" name="id_horario" id="id_schedule">
                    <option value="<?php echo (int)($d->id_horario ?? 0); ?>" selected>Cargando...</option>
                </select>
                </div>
                <div class="staff-form-field--full">
                    <label class="staff-form-check">
                        <input type="checkbox" name="por_honorarios" id="por_honorarios" value="1" <?php echo empty($d->id_salary_level) ? 'checked' : ''; ?>>
                        <b>Pago por honorarios</b>
                    </label>
                </div>
                <div>
                <label><b>Nivel Salarial</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_salary_level" id="id_salary_level" required>
                    <option value="<?php echo (int)($d->id_salary_level ?? 0); ?>" selected>Cargando...</option>
                </select>
                </div>
                <div>
                <label><b>Salario Base</b></label>
                <input type="number" step="0.01" name="salario" value="<?php echo htmlspecialchars($d->salario ?? ''); ?>" placeholder="Ej: 15000.00">
                </div>
                <div>
                <label><b>N° Cuenta de BAC</b></label>
                <input type="text" name="cuenta_bac" value="<?php echo htmlspecialchars($d->cuenta_bac ?? ''); ?>" placeholder="Número de cuenta de banco BAC">
                </div>
                </div>
                </div>

                <div class="staff-form-section">
                <h3 class="staff-form-section__title">Contacto y accesos</h3>
                <div class="staff-form-grid">
                <div>
                <label><b>Teléfono Celular</b></label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($d->telefono ?? ''); ?>" placeholder="Ej: 99887766">
                </div>
                <div>
                <label><b>Correo Personal</b></label>
                <input type="email" name="correo_personal" value="<?php echo htmlspecialchars($d->correo_personal ?? ''); ?>" placeholder="Correo electrónico personal">
                </div>
                <div>
                <label><b>Correo Institucional</b></label>
                <input type="email" name="correo_institucional" value="<?php echo htmlspecialchars($d->correo_institucional ?? ''); ?>" placeholder="Correo electrónico de Medicasa">
                </div>
                <div>
                <label><b>N° de Locker Asignado</b></label>
                <input type="text" name="num_locker" value="<?php echo htmlspecialchars($d->num_locker ?? ''); ?>" placeholder="Ej: L-10">
                </div>
                <div>
                <label><b>ID Empleado (Reloj Biométrico)</b></label>
                <input type="number" name="id_biometrico" value="<?php echo htmlspecialchars($d->id_biometrico ?? ''); ?>" placeholder="Ej: 123">
                </div>
                <div class="staff-form-field--full">
                <?php
                $staffUserFieldName = 'id_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '_staff_user_select.php';
                ?>
                </div>
                </div>
                </div>

                <?php
                $staffDocId = (int) $d->id_primary;
                $staffDocTable = $table;
                $staffDocIdcol = $col_id;
                $staffDocHas = [
                    'solicitud' => !empty($d->has_solicitud),
                    'psicometricas' => !empty($d->has_psicometricas),
                    'contrato' => !empty($d->has_contrato),
                ];
                $staffDocRrhh = $rrhh_docs;
                $staffDocCandidateId = (int) ($d->id_candidate_rrhh ?? 0);
                $staffDocEmail = (string) ($d->correo_personal ?? '');
                include __DIR__ . '/_staff_edit_documentos_section.php';
                ?>

                <div class="staff-form-actions">
                <button type="submit" name="upd_colaborador_unificado" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->id_primary; ?>" data-table="<?php echo $table; ?>" data-idcol="<?php echo $col_id; ?>">Eliminar colaborador</button>
                </div>
            </div>
        </form>
        <?php endforeach; else: ?>
        <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/upd_colaborador_unificado.php'; ?>
<script>
<?php
$deleteUrl = '../../backend/php/delete_administrative.php';
$toggleUrl = '../../backend/php/toggle_administrative_state.php';
if ($table === 'doctor') {
    $deleteUrl = '../../backend/php/delete_doctor.php';
    $toggleUrl = '../../backend/php/toggle_doctor_state.php';
} elseif ($table === 'nurse') {
    $deleteUrl = '../../backend/php/delete_nurse.php';
    $toggleUrl = '../../backend/php/toggle_nurse_state.php';
} elseif ($table === 'staff_general_services') {
    $deleteUrl = '../../backend/php/delete_general_services.php';
    $toggleUrl = '../../backend/php/toggle_general_services_state.php';
} elseif ($table === 'staff_medifarma') {
    $deleteUrl = '../../backend/php/delete_medifarma.php';
    $toggleUrl = '../../backend/php/toggle_medifarma_state.php';
}
?>
window.MEDIDATA_STAFF_ADMIN = {
    toggleSelector: '.staff-state-toggle',
    deleteSelector: '.btn-delete-staff',
    toggleUrl: '<?php echo $toggleUrl; ?>',
    deleteUrl: '<?php echo $deleteUrl; ?>',
    idParam: '<?php echo $col_id; ?>',
    deleteTitle: '¿Eliminar colaborador?',
    deleteFn: 'deleteStaff'
};
</script>
<script src="../../backend/registros/script/tabla_personal_staff.js"></script>
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
