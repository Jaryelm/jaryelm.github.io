<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/departament_schema.php';
if (isset($connect_rrhh) && $connect_rrhh instanceof PDO) {
    medidata_ensure_departament_phone_ext($connect_rrhh);
}

$depContext = 'usr';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_head.php'; ?>
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once '../recursos_humanos/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#"><div class="form-group"></div></form>
            <span class="divider"></span>
            <?php include_once '../recursos_humanos/perfil.php'; ?>
        </nav>

        <main>
        <?php
        $hora_actual = date('H');
        if ($hora_actual >= 6 && $hora_actual < 12) {
            $saludo = 'Buenos Días';
        } elseif ($hora_actual >= 12 && $hora_actual < 18) {
            $saludo = 'Buenas Tardes';
        } else {
            $saludo = 'Buenas Noches';
        }
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <?php include __DIR__ . '/_departamento_editar_body.php'; ?>

        </main>
    </section>
    <script src="../../backend/js/jquery.min.js"></script>
    <?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_foot.php'; ?>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/multistep.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <?php include_once '../../backend/php/upd_departament.php'; ?>
    <script src='../../backend/js/submenu.js'></script>
    <script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>
