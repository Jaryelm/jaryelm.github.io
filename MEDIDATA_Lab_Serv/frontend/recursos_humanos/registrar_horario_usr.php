<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';
require_once '../../backend/php/schedule_lib.php';

$id_edit = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $id_edit > 0;
$edit_data = null;
$pdoRrhh = medidata_rrhh_pdo();
$cancelUrl = 'horarios_usr.php';
$listUrl = 'horarios_usr.php';

if ($pdoRrhh) {
    medidata_schedule_ensure_schema($pdoRrhh);
}

if ($is_edit && $pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare('SELECT * FROM schedules WHERE id = :id AND deleted = 0');
        $stmt->bindParam(':id', $id_edit, PDO::PARAM_INT);
        $stmt->execute();
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_data) {
            $is_edit = false;
        }
    } catch (Throwable $e) {
        $is_edit = false;
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
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once './menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? 'Buenos Días' : (($hora_actual >= 12 && $hora_actual < 18) ? 'Buenas Tardes' : 'Buenas Noches');
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <?php if (!medidata_rrhh_disponible()): ?>
        <div class="alert-danger" style="margin-bottom: 20px;">
            <strong>Base de datos RRHH no disponible.</strong>
            No se puede guardar hasta que esté activa <code>medic9ue_medi_rrhh_interviews</code>.
        </div>
        <?php endif; ?>

        <button class="button" onclick="cambiarColor(this, 'horarios_usr.php')">Listar Horarios</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_horario_usr.php')">Registrar Nuevo Horario</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3><?php echo $is_edit ? 'Modificar' : 'Crear'; ?> Estructura de Horario</h3>
                </div>

                <form id="scheduleForm" action="../../backend/php/<?php echo $is_edit ? 'upd' : 'add'; ?>_schedule.php" method="POST" autocomplete="off">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $id_edit; ?>">
                        <input type="hidden" name="upd_schedule" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_schedule" value="1">
                    <?php endif; ?>

                    <?php include __DIR__ . '/_registrar_horario_form_body.php'; ?>
                </form>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script>
window.MEDIDATA_HORARIO_CFG = {
    listUrl: <?php echo json_encode($listUrl, JSON_UNESCAPED_UNICODE); ?>,
    btnText: <?php echo json_encode($is_edit ? 'Actualizar Horario' : 'Guardar Horario', JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="../../backend/registros/script/rrhh_horario_calc.js"></script>

</body>
</html>
