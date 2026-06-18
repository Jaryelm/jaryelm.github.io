<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);
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
        <button class="button" onclick="cambiarColor(this, 'servicios_generales_nuevo_usr.php')">Registrar Servicios Generales</button>
        <button class="button" onclick="cambiarColor(this, 'servicios_generales_usr.php')">Servicios Generales</button>

        <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="return_page" value="servicios_generales_usr.php">
            <div class="containerss">
                <h1>Nuevo colaborador de servicios generales</h1>
                <div class="alert-danger">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <strong>Importante:</strong> Complete los campos marcados con <span class="badge-warning">*</span>
                </div>
                <hr>
                <label><b>N° de identificación</b></label><span class="badge-warning">*</span>
                <input type="text" name="sgiden" maxlength="14" placeholder="ejm: 0801199012345" required>
                <label><b>Nombre</b></label><span class="badge-warning">*</span>
                <input type="text" name="sgnam" placeholder="ejm: Juan Raúl" required>
                <label><b>Apellido</b></label><span class="badge-warning">*</span>
                <input type="text" name="sgape" placeholder="ejm: Ramírez Requena" required>
                <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
                <input type="date" name="sgdat" required>
                <label><b>Fecha de ingreso</b></label><span class="badge-warning">*</span>
                <input type="date" name="sgingreso" required>
                <label><b>Género</b></label><span class="badge-warning">*</span>
                <select class="select2" name="sgge" required>
                    <option value="">Seleccione</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                </select>
                <label><b>Área / Servicio</b></label>
                <input type="text" name="sgarea" placeholder="ejm: Mantenimiento, Limpieza, Seguridad">
                <?php
                $staffUserFieldName = 'sgid_user';
                $staffSelectedUserId = 0;
                include '_staff_user_select.php';
                ?>
                <hr>
                <button type="submit" name="add_general_services" class="registerbtn">Guardar</button>
            </div>
        </form>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/add_general_services.php'; ?>
<script src='../../backend/js/submenu.js'></script>
<script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>
