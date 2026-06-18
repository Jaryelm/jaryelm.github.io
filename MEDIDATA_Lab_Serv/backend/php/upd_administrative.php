<?php
if (!isset($_POST['upd_administrative'])) {
    return;
}

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$idadm = (int) ($_POST['admidp'] ?? 0);
$numide = strtoupper(trim((string) ($_POST['admiden'] ?? '')));
$nomadm = strtoupper(trim((string) ($_POST['admnam'] ?? '')));
$apeadm = strtoupper(trim((string) ($_POST['admape'] ?? '')));
$nacadm = trim((string) ($_POST['admdat'] ?? ''));
$fechaIngreso = trim((string) ($_POST['admingreso'] ?? ''));
$sexadm = trim((string) ($_POST['admge'] ?? ''));
$cargo = strtoupper(trim((string) ($_POST['admcargo'] ?? '')));
$idUser = medidata_staff_parse_id_user($_POST['admid_user'] ?? null);

try {
    if ($idadm <= 0) {
        throw new RuntimeException('Identificador no válido.');
    }

    if ($idUser !== null) {
        $linked = medidata_staff_id_user_linked($connect, $idUser, 'staff_administrative', $idadm);
        if ($linked !== null) {
            throw new RuntimeException('Ese usuario ya está vinculado como colaborador de ' . $linked['label'] . '.');
        }
    }

    $stmt = $connect->prepare(
        'UPDATE staff_administrative SET id_user = :id_user, numide = :numide, nomadm = :nomadm, apeadm = :apeadm,
         nacadm = :nacadm, fecha_ingreso = :fecha_ingreso, sexadm = :sexadm, cargo = :cargo WHERE idadm = :idadm LIMIT 1'
    );
    $ok = $stmt->execute([
        ':id_user' => $idUser,
        ':numide' => $numide,
        ':nomadm' => $nomadm,
        ':apeadm' => $apeadm,
        ':nacadm' => $nacadm,
        ':fecha_ingreso' => $fechaIngreso !== '' ? $fechaIngreso : null,
        ':sexadm' => $sexadm,
        ':cargo' => $cargo !== '' ? $cargo : null,
        ':idadm' => $idadm,
    ]);

    if ($ok) {
        $returnPage = medidata_staff_return_page($_POST, 'administrativo.php');
        echo '<script>Swal.fire("Actualizado", "Colaborador actualizado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo actualizar", "error");</script>';
    }
    exit;
} catch (Throwable $e) {
    error_log('upd_administrative: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
