<?php
if (!isset($_POST['add_general_services'])) {
    return;
}

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$numide = strtoupper(trim((string) ($_POST['sgiden'] ?? '')));
$nomsg = strtoupper(trim((string) ($_POST['sgnam'] ?? '')));
$apesg = strtoupper(trim((string) ($_POST['sgape'] ?? '')));
$nacsg = trim((string) ($_POST['sgdat'] ?? ''));
$fechaIngreso = trim((string) ($_POST['sgingreso'] ?? ''));
$sexsg = trim((string) ($_POST['sgge'] ?? ''));
$area = strtoupper(trim((string) ($_POST['sgarea'] ?? '')));
$idUser = medidata_staff_parse_id_user($_POST['sgid_user'] ?? null);

if ($numide === '' || $nomsg === '') {
    echo '<script>Swal.fire("Campos requeridos", "Complete identificación y nombre.", "warning");</script>';
    return;
}

try {
    if ($idUser !== null) {
        $linked = medidata_staff_id_user_linked($connect, $idUser, 'staff_general_services');
        if ($linked !== null) {
            echo '<script>Swal.fire("Usuario en uso", "Ese usuario ya está vinculado como colaborador de ' . $linked['label'] . '.", "warning");</script>';
            return;
        }
    }

    $check = $connect->prepare('SELECT COUNT(*) FROM staff_general_services WHERE numide = :numide');
    $check->execute([':numide' => $numide]);
    if ((int) $check->fetchColumn() > 0) {
        echo '<script>Swal.fire("Duplicado", "Ya existe un colaborador con esa identificación.", "warning");</script>';
        return;
    }

    $stmt = $connect->prepare(
        'INSERT INTO staff_general_services (id_user, numide, nomsg, apesg, nacsg, fecha_ingreso, sexsg, area, state)
         VALUES (:id_user, :numide, :nomsg, :apesg, :nacsg, :fecha_ingreso, :sexsg, :area, \'1\')'
    );
    $ok = $stmt->execute([
        ':id_user' => $idUser,
        ':numide' => $numide,
        ':nomsg' => $nomsg,
        ':apesg' => $apesg,
        ':nacsg' => $nacsg,
        ':fecha_ingreso' => $fechaIngreso !== '' ? $fechaIngreso : null,
        ':sexsg' => $sexsg,
        ':area' => $area !== '' ? $area : null,
    ]);

    if ($ok) {
        $returnPage = medidata_staff_return_page($_POST, 'servicios_generales.php');
        echo '<script>Swal.fire("Agregado", "Colaborador de servicios generales registrado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo registrar el colaborador", "error");</script>';
    }
} catch (Throwable $e) {
    error_log('add_general_services: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
}
