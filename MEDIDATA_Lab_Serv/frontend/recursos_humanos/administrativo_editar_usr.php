<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$id = (int) ($_GET['id'] ?? 0);
$sentencia = $connect->prepare('SELECT * FROM staff_administrative WHERE idadm = :id LIMIT 1');
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
        <button class="button" onclick="cambiarColor(this, 'lista_excolaboradores_usr.php')">Ex Administrativos</button>
        <button class="button" onclick="cambiarColor(this, 'administrativo_nuevo_usr.php')">Registrar Administrativo</button>
<?php if (count($data) > 0): foreach ($data as $d): ?>
        <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="return_page" value="lista_colaboradores_usr.php">
            <div class="containerss">
                <h1>Actualizar colaborador administrativo</h1>
                <input type="hidden" name="admidp" value="<?php echo (int) $d->idadm; ?>">
                <hr>
                <label><b>N° de identificación</b></label><span class="badge-warning">*</span>
                <input type="text" name="admiden" maxlength="14" value="<?php echo htmlspecialchars($d->numide); ?>" required>
                <label><b>Nombre</b></label><span class="badge-warning">*</span>
                <input type="text" name="admnam" value="<?php echo htmlspecialchars($d->nomadm); ?>" required>
                <label><b>Apellido</b></label><span class="badge-warning">*</span>
                <input type="text" name="admape" value="<?php echo htmlspecialchars($d->apeadm); ?>" required>
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="admdat" value="<?php echo htmlspecialchars($d->nacadm); ?>" required>
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="admge" required>
                    <option value="Masculino" <?php echo $d->sexadm === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $d->sexadm === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                </select>
                <label><b>Cargo / Área administrativa</b></label>
                <input type="text" name="admcargo" value="<?php echo htmlspecialchars($d->cargo ?? ''); ?>">
                <?php
                $staffUserFieldName = 'admid_user';
                $staffSelectedUserId = isset($d->id_user) ? (int) $d->id_user : 0;
                include '_staff_user_select.php';
                ?>
                <hr>
                <button type="submit" name="upd_administrative" class="registerbtn">Guardar</button>
                <button type="button" class="registerbtn btn-delete-staff" style="background:#c0392b;margin-top:10px;"
                    data-id="<?php echo (int) $d->idadm; ?>">Eliminar colaborador</button>
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
</body>
</html>
