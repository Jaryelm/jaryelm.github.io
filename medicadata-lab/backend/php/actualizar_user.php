<?php
session_start();
require_once __DIR__ . '/../bd/Conexion.php';

if (isset($_POST['actualizar_user'])) {
    $id = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rol = trim($_POST['rol'] ?? '');

    if ($id <= 0 || empty($username) || empty($name) || empty($email) || empty($rol)) {
        $_SESSION['errorMsg'] = 'Por favor complete todos los campos.';
        header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
        exit;
    }

    try {
        $stmtCheck = $connect->prepare("SELECT id FROM users WHERE id = ?");
        $stmtCheck->execute([$id]);
        if (!$stmtCheck->fetch()) {
            $_SESSION['errorMsg'] = 'Usuario no encontrado.';
            header('Location: ../../frontend/usuarios/mostrar.php');
            exit;
        }

        $stmtEmail = $connect->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmtEmail->execute([':email' => $email, ':id' => $id]);
        if ($stmtEmail->fetch()) {
            $_SESSION['errorMsg'] = 'El correo electrónico ya está registrado por otro usuario.';
            header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
            exit;
        }

        $stmtUser = $connect->prepare("SELECT id FROM users WHERE username = :username AND id != :id");
        $stmtUser->execute([':username' => $username, ':id' => $id]);
        if ($stmtUser->fetch()) {
            $_SESSION['errorMsg'] = 'El nombre de usuario ya está registrado.';
            header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
            exit;
        }

        $stmt = $connect->prepare("UPDATE users SET username = :username, name = :name, email = :email, rol = :rol WHERE id = :id");
        $stmt->execute([
            ':username' => $username,
            ':name' => $name,
            ':email' => $email,
            ':rol' => $rol,
            ':id' => $id
        ]);

        $_SESSION['successMsg'] = 'El usuario se actualizó correctamente.';
        header('Location: ../../frontend/usuarios/mostrar.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['errorMsg'] = 'Error al actualizar: ' . $e->getMessage();
        header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
        exit;
    }
}
