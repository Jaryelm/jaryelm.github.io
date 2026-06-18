<?php
if (!isset($_POST['upd_general_services'])) {
    return;
}

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$idsg = (int) ($_POST['sgidp'] ?? 0);
$numide = strtoupper(trim((string) ($_POST['sgiden'] ?? '')));
$nomsg = strtoupper(trim((string) ($_POST['sgnam'] ?? '')));
$apesg = strtoupper(trim((string) ($_POST['sgape'] ?? '')));
$nacsg = trim((string) ($_POST['sgdat'] ?? ''));
$fechaIngreso = trim((string) ($_POST['sgingreso'] ?? ''));
$sexsg = trim((string) ($_POST['sgge'] ?? ''));
$area = strtoupper(trim((string) ($_POST['sgarea'] ?? '')));
$idUser = medidata_staff_parse_id_user($_POST['sgid_user'] ?? null);

try {
    if ($idsg <= 0) {
        throw new RuntimeException('Identificador no válido.');
    }

    if ($idUser !== null) {
        $linked = medidata_staff_id_user_linked($connect, $idUser, 'staff_general_services', $idsg);
        if ($linked !== null) {
            throw new RuntimeException('Ese usuario ya está vinculado como colaborador de ' . $linked['label'] . '.');
        }
    }

    $stmt = $connect->prepare(
        'UPDATE staff_general_services SET id_user = :id_user, numide = :numide, nomsg = :nomsg, apesg = :apesg,
         nacsg = :nacsg, fecha_ingreso = :fecha_ingreso, sexsg = :sexsg, area = :area WHERE idsg = :idsg LIMIT 1'
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
        ':idsg' => $idsg,
    ]);

    if ($ok) {
        $returnPage = medidata_staff_return_page($_POST, 'servicios_generales.php');
        echo '<script>Swal.fire("Actualizado", "Colaborador actualizado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo actualizar", "error");</script>';
    }
    exit;
} catch (Throwable $e) {
    error_log('upd_general_services: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
