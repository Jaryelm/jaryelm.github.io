<?php
require_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';
require_once '../../backend/php/rrhh_employee_form_lib.php';

$id_candidato = (int) ($_GET['id'] ?? 0);
$pdo = medidata_rrhh_pdo();
if (!$pdo || $id_candidato <= 0) {
    die("Candidato no válido o servicio no disponible.");
}

$stmt = $pdo->prepare("SELECT payload, status FROM employees_form WHERE id_candidate = ? AND deleted = 0 ORDER BY id DESC LIMIT 1");
$stmt->execute([$id_candidato]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || $row['status'] !== 'Enviado') {
    $prefill = [];
    $mensaje = "El candidato aún no ha enviado el formulario.";
} else {
    $prefill = json_decode((string) $row['payload'], true) ?: [];
    $mensaje = "";
}

$isUsr = strpos($_SERVER['SCRIPT_NAME'] ?? '', '_usr.php') !== false;
$volver = $isUsr ? "detalle_postulante_usr.php?id=$id_candidato" : "detalle_postulante.php?id=$id_candidato";
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
    <title>Ver Formulario de Empleado - MEDIDATA</title>
    <style>
        .fe-wrap { max-width: 960px; margin: 24px auto; padding: 0 16px 40px; }
        .fe-card { background: #fff; border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,.08); padding: 24px; }
        .fe-section { margin: 28px 0 0; padding-top: 8px; border-top: 2px solid #e8f4f5; }
        .fe-section h2 { margin: 0 0 12px; color: #035c67; font-size: 1.15rem; }
        .fe-subsection { margin: 16px 0; padding: 12px; background: #fafafa; border-radius: 8px; }
        .fe-subsection h3 { margin: 0 0 10px; font-size: 1rem; color: #333; }
        .fe-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: end; }
        .fe-grid > div { display: flex; flex-direction: column; }
        .fe-grid .full { grid-column: 1 / -1; }
        .fe-grid label { font-weight: 600; margin-bottom: 4px; color: #333; font-size: .9rem; }
        .fe-grid input, .fe-grid select, .fe-grid textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: .95rem; box-sizing: border-box; font-family: inherit; background: #e9ecef; color: #495057;
        }
        .fe-grid input, .fe-grid select { height: 44px; }
        .alert { background: #fff3cd; border: 1px solid #ffc107; padding: 14px; border-radius: 8px; color: #664d03; margin-bottom: 20px; }
        @media (max-width: 640px) { .fe-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<?php include_once($isUsr ? 'menu.php' : '../admin/menu.php'); ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar' ></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once($isUsr ? 'perfil.php' : '../admin/perfil.php'); ?>
    </nav>

    <main>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h1 class="title">Formulario de Empleado Lleno</h1>
            <a href="<?php echo $volver; ?>" class="button">Volver al candidato</a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert">
                <strong>Aviso:</strong> <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php else: ?>
            <div class="fe-wrap" style="margin: 0; max-width: 100%;">
                <div class="fe-card">
                    <fieldset disabled style="border: none; padding: 0; margin: 0;">
                        <?php include __DIR__ . '/_formulario_empleado_campos.php'; ?>
                    </fieldset>
                </div>
            </div>
        <?php endif; ?>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
</body>
</html>
