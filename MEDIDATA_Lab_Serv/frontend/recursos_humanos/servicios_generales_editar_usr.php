<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$id = (int) ($_GET['id'] ?? 0);
$sentencia = $connect->prepare('SELECT * FROM staff_general_services WHERE idsg = :id LIMIT 1');
$sentencia->execute([':id' => $id]);
$data = $sentencia->fetchAll(PDO::FETCH_OBJ);
$staffUsers = medidata_staff_fetch_users_for_select($connect);
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
        <button class="button" onclick="cambiarColor(this, 'lista_colaboradores_usr.php')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, 'lista_excolaboradores_usr.php')">Ex Servicios Generales</button>
        <button class="button" onclick="cambiarColor(this, 'servicios_generales_nuevo_usr.php')">Registrar Servicios Generales</button>
<?php if (count($data) > 0): foreach ($data as $d): ?>
        <form action="" method="POST" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" name="return_page" value="lista_colaboradores_usr.php">
            <div class="containerss staff-edit-form">
                <h1>Actualizar colaborador de servicios generales</h1>
                <input type="hidden" name="sgidp" value="<?php echo (int) $d->idsg; ?>">
                <hr>
                
                <label><b>N° de Empleado (Institucional)</b></label>
                <input type="text" name="num_empleado" value="<?php echo htmlspecialchars($d->num_empleado ?? ''); ?>" placeholder="ejm: EMP-001">

                <label><b>N° de identificación (DNI)</b></label><span class="badge-warning">*</span>
                <input type="text" name="sgiden" maxlength="14" value="<?php echo htmlspecialchars($d->numide); ?>" required>
                
                <label><b>Nombres</b></label><span class="badge-warning">*</span>
                <input type="text" name="sgnam" value="<?php echo htmlspecialchars($d->nomsg); ?>" required>
                
                <label><b>Apellidos</b></label><span class="badge-warning">*</span>
                <input type="text" name="sgape" value="<?php echo htmlspecialchars($d->apesg); ?>" required>
                
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="sgdat" value="<?php echo htmlspecialchars($d->nacsg); ?>" required>
                
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="sgge" required>
                    <option value="Masculino" <?php echo $d->sexsg === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $d->sexsg === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
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

                <label><b>Horario</b></label><span class="badge-warning">*</span>
                <select class="select2" name="id_horario" id="id_schedule" required>
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
                $staffUserFieldName = 'sgid_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '_staff_user_select.php';
                ?>
                
                <?php
                $staffDocId = (int) $d->idsg;
                $staffDocTable = 'staff_general_services';
                $staffDocIdcol = 'idsg';
                $staffDocRow = $d;
                $staffDocRrhh = $rrhh_docs ?? null;
                include __DIR__ . '/_staff_edit_documentos_section.php';
                ?>

                <div class="staff-form-actions">
                <button type="submit" name="upd_staff_general_services" class="registerbtn">Guardar Cambios</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->idsg; ?>">Eliminar colaborador</button>
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
<?php include_once '../../backend/php/upd_staff_general_services.php'; ?>
<script>
window.MEDIDATA_STAFF_SG = {
    toggleSelector: '.staff-sg-state-toggle',
    deleteSelector: '.btn-delete-sg',
    toggleUrl: '../../backend/php/toggle_general_services_state.php',
    deleteUrl: '../../backend/php/delete_general_services.php',
    idParam: 'idsg',
    deleteTitle: '¿Eliminar colaborador de servicios generales?',
    deleteFn: 'deleteGeneralServices'
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
