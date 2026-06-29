<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/demo_flujo_guard.php';

$demoCandidatos = medidata_demo_flujo_candidatos();
$demoEtapas = medidata_demo_flujo_etapas();
$demoVacanteId = medidata_demo_flujo_vacante_id();
$maria = null;
foreach ($demoCandidatos as $c) {
    if (str_contains($c->fullname, 'María Elena')) {
        $maria = $c;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
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
        <?php include __DIR__ . '/_demo_flujo_body.php'; ?>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
</body>
</html>
