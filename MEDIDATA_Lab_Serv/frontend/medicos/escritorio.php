<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA - MÉDICO</title>
    <style>
        .med-cards { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px,1fr)); gap:18px; margin-top:18px; }
        .med-card { display:flex; align-items:center; gap:14px; padding:18px; border-radius:10px; background:#fff; border:1px solid #e6e9ee; color:#035c67; text-decoration:none; transition:transform .15s ease, box-shadow .15s ease; }
        .med-card:hover { transform:translateY(-3px); box-shadow:0 8px 18px rgba(0,0,0,.08); }
        .med-card i { font-size:30px; }
        .med-card span { font-weight:600; }
    </style>
</head>
<body>
    <?php include_once '../medicos/menu.php'; ?>

    <section id="content">
        <nav>
            <i class='bx bx-menu toggle-sidebar'></i>
            <form action="#">
                <div class="form-group"></div>
            </form>
            <span class="divider"></span>
            <?php include_once '../admin/perfil.php'; ?>
        </nav>
        <main>
            <?php
            $hora_actual = date('H');
            $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
            ?>
            <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name ?? '') . '</strong>'; ?></h1>

            <div class="vp-panel">
                <p class="vp-muted">Accesos rápidos</p>
                <div class="med-cards">
                    <a class="med-card" href="../vacaciones_permisos/mis_vacaciones.php"><i class='bx bx-calendar-check'></i><span>Mis solicitudes de vacaciones/permisos</span></a>
                    <a class="med-card" href="../vacaciones_permisos/incapacidades.php"><i class='bx bx-plus-medical'></i><span>Mis incapacidades</span></a>
                    <a class="med-card" href="../vacaciones_permisos/aprobaciones.php"><i class='bx bx-check-double'></i><span>Aprobaciones de mi equipo</span></a>
                    <a class="med-card" href="../recursos/relojbio_usr.php"><i class='bx bxs-time'></i><span>Mis marcas biométricas</span></a>
                </div>
            </div>
        </main>
    </section>

    <script src="../../backend/js/jquery.min.js"></script>
    <script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
    <script src="../../backend/js/script.js"></script>
    <script src="../../backend/js/submenu.js"></script>
</body>
</html>
