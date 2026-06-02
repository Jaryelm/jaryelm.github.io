<?php
if (!isset($_POST['upd_pass_doctor'])) {
    return;
}

$idodc = (int) ($_POST['midp'] ?? 0);
$password = md5((string) ($_POST['mepasne'] ?? ''));

try {
    if ($idodc <= 0 || trim((string) ($_POST['mepasne'] ?? '')) === '') {
        throw new RuntimeException('Datos incompletos para actualizar la contraseña.');
    }

    $query = 'UPDATE doctor SET password = :password WHERE idodc = :idodc LIMIT 1';
    $statement = $connect->prepare($query);
    $ok = $statement->execute([
        ':password' => $password,
        ':idodc' => $idodc,
    ]);

    if ($ok) {
        echo '<script>Swal.fire("Actualizado", "Contraseña actualizada correctamente", "success").then(function(){ window.location="mostrar.php"; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo actualizar la contraseña", "error");</script>';
    }
    exit;
} catch (Throwable $e) {
    error_log('upd_pass_doctor: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
