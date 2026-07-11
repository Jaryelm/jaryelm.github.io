<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/usuarios/auth_password_lib.php';

session_start();
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$message = '';
$messageType = 'info';
$validToken = false;

if ($token !== '') {
    $validToken = medidata_auth_password_user_by_token($connect, $token) !== null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_password_reset'])) {
    $token = trim((string) ($_POST['token'] ?? ''));
    $pass1 = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password_confirm'] ?? '');
    if ($pass1 !== $pass2) {
        $message = 'Las contraseñas no coinciden.';
        $messageType = 'error';
        $validToken = medidata_auth_password_user_by_token($connect, $token) !== null;
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $result = medidata_auth_password_reset_apply($connect, $token, $pass1, is_string($ip) ? $ip : null);
        $message = $result['message'];
        $messageType = $result['ok'] ? 'success' : 'error';
        if ($result['ok']) {
            $validToken = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MEDIDATA</title>
    <link rel="stylesheet" href="../../backend/css/style.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <script src="/backend/vendor/sweetalert/sweetalert.min.js"></script>
</head>
<body>
    <div class="form-container">
        <div class="logo-container">
            <img src="../../backend/img/logo.png" alt="Logo MEDIDATA" class="logo">
        </div>
        <h1 class="heading" style="font-size:1.25rem;margin-bottom:12px;">Nueva contraseña</h1>

        <?php if ($validToken): ?>
        <form action="" method="POST" autocomplete="off">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="password" class="form-input span-2" placeholder="Nueva contraseña" required minlength="6">
            <input type="password" name="password_confirm" class="form-input span-2" placeholder="Confirmar contraseña" required minlength="6">
            <button class="btn submit-btn span-2" name="auth_password_reset" type="submit" value="1">Guardar contraseña</button>
        </form>
        <?php elseif ($messageType !== 'success'): ?>
        <p style="text-align:center;color:#c0392b;">El enlace no es válido o expiró.</p>
        <p class="btm-line"><a href="recuperar_password.php" style="color:#035c67;font-weight:600;">Solicitar nuevo enlace</a></p>
        <?php endif; ?>

        <p class="btm-line" style="margin-top:16px;">
            <a href="../login.php" style="color:#035c67;font-weight:600;">Volver al inicio de sesión</a>
        </p>
    </div>
    <?php if ($message !== ''): ?>
    <script>
        swal({
            title: <?php echo json_encode($messageType === 'success' ? 'Listo' : 'Atención'); ?>,
            text: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
            icon: <?php echo json_encode($messageType === 'success' ? 'success' : 'error'); ?>,
            button: 'OK'
        })<?php if ($messageType === 'success'): ?>.then(function(){ window.location.href = '../login.php'; })<?php endif; ?>;
    </script>
    <?php endif; ?>
</body>
</html>
