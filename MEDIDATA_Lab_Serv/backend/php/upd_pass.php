<?php
require '../../backend/bd/Conexion.php';
require_once __DIR__ . '/usuarios/auth_password_lib.php';
session_start();

if (isset($_POST['upd_profile_pass'])) {
    $id = (int) ($_POST['newid'] ?? 0);
    $newPassword = MD5(trim((string) ($_POST['newpass'] ?? '')));

    if ($id <= 0 || trim((string) ($_POST['newpass'] ?? '')) === '') {
        $_SESSION['errorMsg'] = 'Datos incompletos para actualizar la contraseña.';
        header('Location: ../../frontend/usuarios/mostrar.php');
        exit();
    }

    try {
        $query = 'UPDATE users SET password = :password WHERE id = :id LIMIT 1';
        $statement = $connect->prepare($query);
        $query_execute = $statement->execute([
            ':password' => $newPassword,
            ':id' => $id,
        ]);

        if ($query_execute) {
            medidata_auth_password_notify_admin_change(
                $connect,
                $id,
                $_SESSION['username'] ?? null,
                $_SESSION['name'] ?? null
            );
            $_SESSION['successMsg'] = 'Contraseña actualizada correctamente.';
            header('Location: ../../frontend/usuarios/mostrar.php');
            exit();
        }

        $_SESSION['errorMsg'] = 'Error al actualizar la contraseña. Contacte a Soporte TI.';
        header('Location: ../../frontend/usuarios/mostrar.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['errorMsg'] = 'Error en la base de datos: ' . $e->getMessage();
        header('Location: ../../frontend/usuarios/mostrar.php');
        exit();
    }
}
