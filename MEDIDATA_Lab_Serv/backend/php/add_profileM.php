<?php
if (!isset($_POST['add_profileM'])) {
    return;
}

$idodc = (int) ($_POST['mid'] ?? 0);
$corr = trim((string) ($_POST['come'] ?? ''));
$username = trim((string) ($_POST['namedc'] ?? ''));
$password = md5((string) ($_POST['pwdm'] ?? ''));
$rol = trim((string) ($_POST['rlm'] ?? ''));

try {
    if ($idodc <= 0 || $corr === '' || $username === '' || $rol === '') {
        throw new RuntimeException('Complete todos los campos del perfil.');
    }

    $query = 'UPDATE doctor SET corr = :corr, username = :username, password = :password, rol = :rol WHERE idodc = :idodc LIMIT 1';
    $statement = $connect->prepare($query);
    $ok = $statement->execute([
        ':corr' => $corr,
        ':username' => $username,
        ':password' => $password,
        ':rol' => $rol,
        ':idodc' => $idodc,
    ]);

    if ($ok) {
        echo '<script>Swal.fire("Perfil creado", "El perfil del médico fue creado correctamente", "success").then(function(){ window.location="mostrar.php"; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo crear el perfil", "error");</script>';
    }
    exit;
} catch (Throwable $e) {
    error_log('add_profileM: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
