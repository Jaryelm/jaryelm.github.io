<?php
declare(strict_types=1);

session_start();
$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_password_request'])) {
    try {
        require_once __DIR__ . '/../../backend/bd/Conexion.php';
        require_once __DIR__ . '/../../backend/php/usuarios/auth_password_lib.php';

        $login = trim((string) ($_POST['login'] ?? ''));
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $result = medidata_auth_password_request($connect, $login, is_string($ip) ? $ip : null);
        $message = $result['message'];
        $messageType = $result['ok'] ? 'success' : 'error';
    } catch (Throwable $e) {
        error_log('recuperar_password.php: ' . $e->getMessage());
        $message = 'No se pudo procesar la solicitud. Verifique que las tablas de recuperación existan en la base de datos o contacte a Soporte TI.';
        $messageType = 'error';
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
        <h1 class="heading" style="font-size:1.25rem;margin-bottom:12px;">Recuperar contraseña</h1>
        <p style="text-align:center;color:#555;font-size:0.9rem;margin-bottom:16px;">
            Ingrese su usuario o correo institucional registrado en MEDIDATA.
        </p>
        <form action="" method="POST" autocomplete="off">
            <input type="text" name="login" class="form-input span-2" placeholder="Usuario o correo" required
                   value="<?php echo htmlspecialchars(trim((string) ($_POST['login'] ?? ''))); ?>">
            <button class="btn submit-btn span-2" name="auth_password_request" type="submit" value="1">Enviar enlace</button>
        </form>
        <p class="btm-line" style="margin-top:16px;">
            <a href="../login.php" style="color:#035c67;font-weight:600;">Volver al inicio de sesión</a>
        </p>
    </div>
    <?php if ($message !== ''): ?>
    <script>
        swal({
            title: <?php echo json_encode($messageType === 'success' ? 'Solicitud enviada' : 'Atención'); ?>,
            text: <?php echo json_encode($message, JSON_UNESCAPED_UNICODE); ?>,
            icon: <?php echo json_encode($messageType === 'success' ? 'success' : 'error'); ?>,
            button: 'OK'
        })<?php if ($messageType === 'success'): ?>.then(function () {
            window.location.href = '../login.php';
        })<?php endif; ?>;
    </script>
    <?php endif; ?>
</body>
</html>
