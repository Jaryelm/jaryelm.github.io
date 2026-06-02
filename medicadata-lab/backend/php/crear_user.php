<?php
/**
 * Procesa alta de usuario. Incluir antes de imprimir HTML; el script Swal se imprime al final de la página.
 */
if (!isset($_POST['add_user'])) {
    return;
}

require_once __DIR__ . '/../bd/Conexion.php';

date_default_timezone_set('America/Tegucigalpa');

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

medidata_ensure_users_name_varchar50($connect);

$username = trim((string) ($_POST['username'] ?? ''));
$nombreCompleto = trim((string) ($_POST['nombre_completo'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = trim((string) ($_POST['password'] ?? ''));
$rol = trim((string) ($_POST['rol'] ?? ''));
$uidBiometrico = trim((string) ($_POST['uid_biometrico'] ?? ''));
$currentDateTime = date('Y-m-d H:i:s');

function medidata_crear_user_alert(string $title, string $text, string $icon, ?string $redirect = null): void
{
    $payload = [
        'title' => $title,
        'text' => $text,
        'icon' => $icon,
        'confirmButtonText' => 'OK',
    ];
    echo '<script>Swal.fire(' . json_encode($payload, JSON_UNESCAPED_UNICODE) . ')';
    if ($redirect !== null) {
        echo '.then(function(){ window.location=' . json_encode($redirect, JSON_UNESCAPED_UNICODE) . '; })';
    }
    echo ';</script>';
}

if ($username === '') {
    medidata_crear_user_alert('Campo vacío', 'Por favor ingrese el nombre de usuario.', 'warning');
    return;
}
if ($nombreCompleto === '') {
    medidata_crear_user_alert('Campo vacío', 'Por favor ingrese el nombre completo.', 'warning');
    return;
}
if ($email === '') {
    medidata_crear_user_alert('Campo vacío', 'Por favor ingrese el correo electrónico.', 'warning');
    return;
}
if ($password === '') {
    medidata_crear_user_alert('Campo vacío', 'Por favor ingrese la contraseña.', 'warning');
    return;
}
if ($rol === '') {
    medidata_crear_user_alert('Campo vacío', 'Por favor seleccione el rol del usuario.', 'warning');
    return;
}
if (mb_strlen($nombreCompleto) > 50) {
    medidata_crear_user_alert(
        'Nombre muy largo',
        'El nombre completo no puede superar 50 caracteres (actual: ' . mb_strlen($nombreCompleto) . ').',
        'warning'
    );
    return;
}
if (strlen($username) > 25) {
    medidata_crear_user_alert(
        'Usuario muy largo',
        'El nombre de usuario no puede superar 25 caracteres (actual: ' . strlen($username) . ').',
        'warning'
    );
    return;
}
if (strlen($email) > 35) {
    medidata_crear_user_alert(
        'Correo muy largo',
        'El correo electrónico no puede superar 35 caracteres (actual: ' . strlen($email) . ').',
        'warning'
    );
    return;
}

try {
    $stmtEmail = $connect->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmtEmail->execute([$email]);
    if ($stmtEmail->fetchColumn()) {
        medidata_crear_user_alert('Usuario existente', 'Ya existe un usuario con ese correo electrónico.', 'error');
        return;
    }

    $stmtUser = $connect->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmtUser->execute([$username]);
    if ($stmtUser->fetchColumn()) {
        medidata_crear_user_alert('Usuario existente', 'Ese nombre de usuario ya está registrado.', 'error');
        return;
    }

    if ($uidBiometrico !== '') {
        $stmtUid = $connect->prepare('SELECT id FROM users WHERE uid_biometrico = ? LIMIT 1');
        $stmtUid->execute([$uidBiometrico]);
        if ($stmtUid->fetchColumn()) {
            medidata_crear_user_alert('ID biométrico en uso', 'El ID biométrico ya está asignado a otro usuario.', 'error');
            return;
        }
    }

    $hashedPassword = md5($password);
    $uidValue = $uidBiometrico !== '' ? $uidBiometrico : null;

    $stmt = $connect->prepare(
        'INSERT INTO users (username, name, email, password, rol, created_at, uid_biometrico)
         VALUES (:username, :name, :email, :password, :rol, :created_at, :uid_biometrico)'
    );
    $stmt->execute([
        ':username' => $username,
        ':name' => $nombreCompleto,
        ':email' => $email,
        ':password' => $hashedPassword,
        ':rol' => $rol,
        ':created_at' => $currentDateTime,
        ':uid_biometrico' => $uidValue,
    ]);

    medidata_crear_user_alert(
        'Usuario agregado',
        'El usuario se agregó correctamente.',
        'success',
        'crear_user.php'
    );
} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'Data too long') !== false || stripos($msg, '1406') !== false) {
        $lenNombre = mb_strlen($nombreCompleto);
        $lenUsuario = strlen($username);
        $lenEmail = strlen($email);
        $msg = 'Un dato supera el límite en la base de datos. '
            . "Nombre completo: {$lenNombre}/50, usuario: {$lenUsuario}/25, correo: {$lenEmail}/35. ";
        if ($lenNombre <= 50 && $lenUsuario <= 25 && $lenEmail <= 35) {
            $msg .= 'La columna name en MySQL puede seguir en 30 caracteres; se intentó ampliarla automáticamente. '
                . 'Vuelva a guardar o ejecute database backup/alter_users_name_varchar50.sql';
        }
    }
    medidata_crear_user_alert('Error', $msg, 'error');
}
