<?php
session_start();
require_once __DIR__ . '/../bd/Conexion.php';

if (!function_exists('medidata_ensure_users_name_varchar50')) {
    function medidata_ensure_users_name_varchar50(PDO $connect): void
    {
        try {
            $col = $connect->query("SHOW COLUMNS FROM users LIKE 'name'")->fetch(PDO::FETCH_ASSOC);
            if (!$col || !isset($col['Type'])) {
                return;
            }
            if (preg_match('/varchar\((\d+)\)/i', (string) $col['Type'], $m) && (int) $m[1] < 50) {
                $connect->exec(
                    'ALTER TABLE users MODIFY COLUMN name VARCHAR(50) COLLATE utf8_unicode_ci NOT NULL'
                );
            }
        } catch (Throwable $e) {
            error_log('medidata_ensure_users_name_varchar50: ' . $e->getMessage());
        }
    }
}

if (isset($_POST['actualizar_user'])) {
    medidata_ensure_users_name_varchar50($connect);
    $id = intval($_POST['user_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $nombreCompleto = trim((string) ($_POST['nombre_completo'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $rol = trim($_POST['rol'] ?? '');
    $uidBiometrico = trim($_POST['uid_biometrico'] ?? '');

    if ($id <= 0 || empty($username) || $nombreCompleto === '' || empty($email) || empty($rol)) {
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

        if ($uidBiometrico !== '') {
            $stmtUid = $connect->prepare("SELECT id FROM users WHERE uid_biometrico = :uid AND id != :id");
            $stmtUid->execute([':uid' => $uidBiometrico, ':id' => $id]);
            if ($stmtUid->fetch()) {
                $_SESSION['errorMsg'] = 'El ID biométrico ya está asignado a otro usuario.';
                header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
                exit;
            }
        }

        $stmt = $connect->prepare("UPDATE users SET username = :username, name = :name, email = :email, rol = :rol, uid_biometrico = :uid_biometrico WHERE id = :id");
        $uidValue = $uidBiometrico !== '' ? $uidBiometrico : null;
        if (mb_strlen($nombreCompleto) > 50) {
            $_SESSION['errorMsg'] = 'El nombre completo no puede superar 50 caracteres (actual: ' . mb_strlen($nombreCompleto) . ').';
            header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
            exit;
        }
        if (strlen($email) > 35) {
            $_SESSION['errorMsg'] = 'El correo no puede superar 35 caracteres (actual: ' . strlen($email) . ').';
            header('Location: ../../frontend/usuarios/editar_user.php?id=' . $id);
            exit;
        }

        $stmt->execute([
            ':username' => $username,
            ':name' => $nombreCompleto,
            ':email' => $email,
            ':rol' => $rol,
            ':uid_biometrico' => $uidValue,
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
