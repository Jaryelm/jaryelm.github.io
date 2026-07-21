<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/rrhh_candidato_workflow_lib.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$volverUrl = 'detalle_postulante_usr.php?id=' . $id;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../backend/vendor/boxicons/css/boxicons.min.css">
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        <?php include __DIR__ . '/_expediente_estado_body.php'; ?>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script src="../../backend/registros/script/rrhh_expediente_estado.js?v=20260720"></script>
</body>
</html>
