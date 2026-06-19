<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$candidato = null;
$vacanteNombre = 'N/A';
$puestoNombre = 'N/A';
$rrhh_error = null;
$volverUrl = medidata_rrhh_detalle_volver_url('postulantes.php');

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    $rrhh_error = 'Base de datos de Recursos Humanos no disponible.';
} elseif ($id <= 0) {
    $rrhh_error = 'Identificador de candidato no válido.';
} else {
    try {
        $sql = "SELECT p.*, v.vacant_name, pt.name AS position_name
                FROM postulantes p
                LEFT JOIN vacantes_trabajo v ON p.id_vacant_position = v.id
                LEFT JOIN puestos_trabajo pt ON v.id_position = pt.id
                WHERE p.id = :id AND p.deleted = 0
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $candidato = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$candidato) {
            $rrhh_error = 'Candidato no encontrado.';
        } else {
            $vacanteNombre = $candidato->vacant_name ?? 'N/A';
            $puestoNombre = $candidato->position_name ?? 'N/A';
        }
    } catch (Throwable $e) {
        error_log('detalle_postulante: ' . $e->getMessage());
        $rrhh_error = 'No se pudo cargar la información del candidato.';
    }
}
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
        <?php include __DIR__ . '/_detalle_candidato_body.php'; ?>
    </main>
</section>
<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/rrhh_candidato_estado.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
</body>
</html>
