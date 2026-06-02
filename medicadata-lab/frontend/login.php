<?php 
include_once '../backend/php/login.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MEDIDATA</title>
    <link rel="stylesheet" href="../backend/css/style.css" />
    <link rel="icon" type="image/png" sizes="96x96" href="../backend/img/icon.png">
    <script src="/backend/vendor/sweetalert/sweetalert.min.js"></script>
</head>
<body>
    <div class="form-container">
        <div class="logo-container">
            <img src="../backend/img/logo.png" alt="Logo MEDIDATA" class="logo">
        </div>
        
        <!-- oculto 
        <h1 class="heading">INICIA SESIÓN</h1>
        -->

        <form action="" method="POST" autocomplete="off">
            <input type="text" name="username" value="<?php if(isset($_POST['username'])) echo $_POST['username'] ?>" 
                class="form-input span-2" placeholder="Nombre de usuario" required />
            <input type="password" name="password" class="form-input span-2" placeholder="Contraseña" required />
            <button class="btn submit-btn span-2" name='login' type="submit">Iniciar sesión</button>
        </form>

        <p class="btm-line">
            Al unirte, aceptas nuestros Términos de servicio y Política de privacidad
        </p>
    </div>

    <?php if (isset($_SESSION['errMsg'])): ?>
        <script>
            swal({
                title: "Error",
                text: "<?php echo $_SESSION['errMsg']; ?>",
                icon: "error",
                button: "OK",
            });
        </script>
        <?php unset($_SESSION['errMsg']); ?>
    <?php endif; ?>
</body>
</html>
