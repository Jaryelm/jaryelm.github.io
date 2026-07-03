<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/php/rrhh_candidato_workflow_lib.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$volverUrl = 'detalle_postulante.php?id=' . $id;
$saveUrl = '../../backend/php/rrhh_entrevista_guardar.php';
$calendarioUrl = 'entrevista.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../backend/vendor/boxicons/css/boxicons.min.css">
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
        <?php include __DIR__ . '/_formulario_entrevista_body.php'; ?>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
</body>
</html>
