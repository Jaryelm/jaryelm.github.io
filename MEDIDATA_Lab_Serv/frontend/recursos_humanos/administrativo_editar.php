<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
require_once '../../backend/registros/rrhh_guard.php';
medidata_staff_ensure_tables($connect);

$id = (int) ($_GET['id'] ?? 0);
$stmt_a = $connect->prepare("
        SELECT idadm, id_user, numide, nomadm, apeadm, nacadm, sexadm, state,
               num_empleado, tipo_empleado, duracion_contrato, fecha_ingreso,
               id_departamento, id_cargo, id_horario, id_salary_level, salario,
               cuenta_bac, telefono, correo_personal, correo_institucional,
               num_locker, id_biometrico, id_candidate_rrhh,
               (url_contrato IS NOT NULL AND url_contrato != '') AS has_contrato,
               (url_solicitud IS NOT NULL AND url_solicitud != '') AS has_solicitud,
               (url_psicometricas IS NOT NULL AND url_psicometricas != '') AS has_psicometricas
        FROM staff_administrative 
        WHERE idadm = ? LIMIT 1
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

$rrhh_docs = null;
if (count($data) > 0 && !empty($data[0]->id_candidate_rrhh)) {
    $pdoRrhh = medidata_rrhh_pdo();
    if ($pdoRrhh) {
        $stmtHR = $pdoRrhh->prepare("SELECT * FROM hiring_requirements WHERE id_candidate = ? LIMIT 1");
        $stmtHR->execute([$data[0]->id_candidate_rrhh]);
        $rrhh_docs = $stmtHR->fetch(PDO::FETCH_ASSOC);
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
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
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
        $saludo = ($hora >= 6 && $hora < 12) ? 'Buenos Días' : (($hora >= 12 && $hora < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>
        <button class="button" onclick="cambiarColor(this, 'lista_colaboradores.php')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, 'lista_excolaboradores.php')">Ex Administrativos</button>
        <button class="button" onclick="cambiarColor(this, 'administrativo_nuevo.php')">Registrar Administrativo</button>

        <?php if (count($data) > 0): foreach ($data as $d): ?>
        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="return_page" value="lista_colaboradores.php">
            <div class="containerss staff-edit-form">
                <h1>Actualizar colaborador administrativo</h1>
                <input type="hidden" name="admidp" value="<?php echo (int) $d->idadm; ?>">
                <hr>
                
                <label><b>N° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" value="<?php echo htmlspecialchars($d->num_empleado ?? ''); ?>" placeholder="ejm: EMP-001">

                <label><b>N° de identificación (DNI)</b></label><span class="badge-warning">*</span>
                <input type="text" name="admiden" maxlength="14" value="<?php echo htmlspecialchars($d->numide); ?>" required>
                
                <label><b>Nombres</b></label><span class="badge-warning">*</span>
                <input type="text" name="admnam" value="<?php echo htmlspecialchars($d->nomadm); ?>" required>
                
                <label><b>Apellidos</b></label><span class="badge-warning">*</span>
                <input type="text" name="admape" value="<?php echo htmlspecialchars($d->apeadm); ?>" required>
                
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="admdat" value="<?php echo htmlspecialchars($d->nacadm); ?>" required>
                
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="admge" required>
                    <option value="Masculino" <?php echo $d->sexadm === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $d->sexadm === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>

                <hr>
                <h3>Información Laboral</h3>
                
                <label><b>Tipo de Empleado</b></label><span class="badge-warning">*</span>
                <select class="select2" name="tipo_empleado" id="tipo_empleado" required onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
                    <option value="Permanente" <?php echo ($d->tipo_empleado ?? '') === 'Permanente' ? 'selected' : ''; ?>>Permanente</option>
                    <option value="Temporal" <?php echo ($d->tipo_empleado ?? '') === 'Temporal' ? 'selected' : ''; ?>>Temporal</option>
                    <option value="Tiempo parcial" <?php echo ($d->tipo_empleado ?? '') === 'Tiempo parcial' ? 'selected' : ''; ?>>Tiempo parcial</option>
                </select>

                <div id="duracion_contrato_div" style="display:<?php echo in_array($d->tipo_empleado ?? '', ['Temporal', 'Tiempo parcial']) ? 'block' : 'none'; ?>; margin-top:10px;">
                    <label><b>Duración de Contrato</b></label>
                    <input type="text" name="duracion_contrato" value="<?php echo htmlspecialchars($d->duracion_contrato ?? ''); ?>" placeholder="Ej: 6 meses">
                </div>

                <label><b>Fecha de Ingreso</b></label>
                <input type="date" name="fecha_ingreso" value="<?php echo htmlspecialchars($d->fecha_ingreso ?? ''); ?>">

                <label><b>Departamento</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_departamento" id="id_departament" required>
                    <option value="<?php echo (int)($d->id_departamento ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Cargo / Posición</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_cargo" required>
                    <option value="" disabled>Seleccione...</option>
                    <?php foreach ($cargos as $cargo): ?>
                        <option value="<?php echo $cargo['id']; ?>" <?php echo ($d->id_cargo ?? 0) == $cargo['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cargo['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <label><b>Horario</b></label>
                <select class="select2" name="id_horario" id="id_schedule">
                    <option value="<?php echo (int)($d->id_horario ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Nivel Salarial</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_salary_level" id="id_salary_level" required>
                    <option value="<?php echo (int)($d->id_salary_level ?? 0); ?>" selected>Cargando...</option>
                </select>

                <label><b>Salario Base</b></label>
                <input type="number" step="0.01" name="salario" value="<?php echo htmlspecialchars($d->salario ?? ''); ?>" placeholder="Ej: 15000.00">

                <label><b>N° Cuenta de BAC</b></label>
                <input type="text" name="cuenta_bac" value="<?php echo htmlspecialchars($d->cuenta_bac ?? ''); ?>" placeholder="Número de cuenta de banco BAC">

                <hr>
                <h3>Información de Contacto y Accesos</h3>

                <label><b>Teléfono Celular</b></label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($d->telefono ?? ''); ?>" placeholder="Ej: 99887766">

                <label><b>Correo Personal</b></label>
                <input type="email" name="correo_personal" value="<?php echo htmlspecialchars($d->correo_personal ?? ''); ?>" placeholder="Correo electrónico personal">

                <label><b>Correo Institucional</b></label>
                <input type="email" name="correo_institucional" value="<?php echo htmlspecialchars($d->correo_institucional ?? ''); ?>" placeholder="Correo electrónico de Medicasa">

                <label><b>N° de Locker Asignado</b></label>
                <input type="text" name="num_locker" value="<?php echo htmlspecialchars($d->num_locker ?? ''); ?>" placeholder="Ej: L-10">

                <label><b>ID Empleado (Reloj Biométrico)</b></label>
                <input type="number" name="id_biometrico" value="<?php echo htmlspecialchars($d->id_biometrico ?? ''); ?>" placeholder="Ej: 123">

                <?php
                $staffUserFieldName = 'admid_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '_staff_user_select.php';
                ?>
                
                <?php
                $staffDocId = (int) $d->idadm;
                $staffDocTable = 'staff_administrative';
                $staffDocIdcol = 'idadm';
                $staffDocHas = [
                    'solicitud' => !empty($d->has_solicitud),
                    'psicometricas' => !empty($d->has_psicometricas),
                    'contrato' => !empty($d->has_contrato),
                ];
                $staffDocRrhh = $rrhh_docs;
                include __DIR__ . '/_staff_edit_documentos_section.php';
                ?>

                <div class="staff-form-actions">
                <button type="submit" name="upd_administrative" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->idadm; ?>">Eliminar colaborador</button>
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
<?php include_once '../../backend/php/upd_administrative.php'; ?>
<script>
window.MEDIDATA_STAFF_ADMIN = {
    toggleSelector: '.staff-state-toggle',
    deleteSelector: '.btn-delete-staff',
    toggleUrl: '../../backend/php/toggle_administrative_state.php',
    deleteUrl: '../../backend/php/delete_administrative.php',
    idParam: 'idadm',
    deleteTitle: '¿Eliminar colaborador administrativo?',
    deleteFn: 'deleteAdministrative'
};
</script>
<script src="../../backend/registros/script/tabla_personal_staff.js"></script>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>
<script src="../../backend/js/cat_schedules.js"></script>
</body>
</html>
