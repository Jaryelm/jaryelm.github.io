<?php
if (!isset($_POST['add_administrative'])) {
    return;
}

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
medidata_staff_ensure_tables($connect);

$numide = strtoupper(trim((string) ($_POST['admiden'] ?? '')));
$nomadm = strtoupper(trim((string) ($_POST['admnam'] ?? '')));
$apeadm = strtoupper(trim((string) ($_POST['admape'] ?? '')));
$nacadm = trim((string) ($_POST['admdat'] ?? ''));
$sexadm = trim((string) ($_POST['admge'] ?? ''));
$cargo = strtoupper(trim((string) ($_POST['admcargo'] ?? '')));
$idUser = medidata_staff_parse_id_user($_POST['admid_user'] ?? null);

if ($numide === '' || $nomadm === '') {
    echo '<script>Swal.fire("Campos requeridos", "Complete identificación y nombre.", "warning");</script>';
    return;
}

try {
    if ($idUser !== null) {
        $linked = medidata_staff_id_user_linked($connect, $idUser, 'staff_administrative');
        if ($linked !== null) {
            echo '<script>Swal.fire("Usuario en uso", "Ese usuario ya está vinculado como colaborador de ' . $linked['label'] . '.", "warning");</script>';
            return;
        }
    }

    $check = $connect->prepare('SELECT COUNT(*) FROM staff_administrative WHERE numide = :numide');
    $check->execute([':numide' => $numide]);
    if ((int) $check->fetchColumn() > 0) {
        echo '<script>Swal.fire("Duplicado", "Ya existe un colaborador administrativo con esa identificación.", "warning");</script>';
        return;
    }

    $stmt = $connect->prepare(
        'INSERT INTO staff_administrative (id_user, numide, nomadm, apeadm, nacadm, sexadm, cargo, state)
         VALUES (:id_user, :numide, :nomadm, :apeadm, :nacadm, :sexadm, :cargo, \'1\')'
    );
    $ok = $stmt->execute([
        ':id_user' => $idUser,
        ':numide' => $numide,
        ':nomadm' => $nomadm,
        ':apeadm' => $apeadm,
        ':nacadm' => $nacadm,
        ':sexadm' => $sexadm,
        ':cargo' => $cargo !== '' ? $cargo : null,
    ]);

    if ($ok) {
        $returnPage = medidata_staff_return_page($_POST, 'administrativo.php');
        echo '<script>Swal.fire("Agregado", "Colaborador administrativo registrado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo registrar el colaborador", "error");</script>';
    }
} catch (Throwable $e) {
    error_log('add_administrative: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
}
