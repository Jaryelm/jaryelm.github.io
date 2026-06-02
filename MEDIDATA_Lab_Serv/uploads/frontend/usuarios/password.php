<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='../../backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

<section id="content">
    <!-- NAVBAR -->
    <nav>
        <i class='bx bx-menu toggle-sidebar' ></i>
        <form action="#">
            <div class="form-group">
            </div>
        </form>
        <span class="divider"></span>
<?php
include_once '../admin/perfil.php';
// incuir el archivo menu principal
?>
    </nav>
    <!-- NAVBAR -->

    <!-- MAIN -->
    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, '../usuarios/crear_user.php')">Crear Usuarios</button>
        <button class="button" onclick="cambiarColor(this, '../usuarios/mostrar.php')">Lista de Usuarios</button>
        
        <?php 
        $id = $_GET['id'];
        $sentencia = $connect->prepare("SELECT * FROM users WHERE id= :id;");
        $sentencia->bindParam(':id', $id);
        $sentencia->execute();
        $data = $sentencia->fetchAll(PDO::FETCH_OBJ);
        ?>
        
        <?php if($data):?>
            <form action="../../backend/php/upd_pass.php" method="POST" autocomplete="off">
                <div class="containerss">
                    <h1>Cambiar contraseña del Usuario</h1>
                    <br>
                    <hr>
                    <label for="username"><b>Nombre de Usuario</b></label><span class="badge-warning">*</span>
                    <input type="text" value="<?php echo $data[0]->username; ?>" readonly>
                    <input type="hidden" name="newid" value="<?php echo $data[0]->id; ?>">
                    <label for="newpass"><b>Nueva Contraseña</b></label><span class="badge-warning">*</span>
                    <input type="password" name="newpass" required placeholder="*******">
                    <hr>
                    <button type="submit" name="upd_profile_pass" class="registerbtn">Guardar</button>
                </div>
            </form>
        <?php else:?>
            <p class="alert alert-warning">No hay datos</p>
        <?php endif; ?>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<!-- SubMenu -->
<script src='../../backend/js/submenu.js'></script>

<!-- Script para manejar el cambio de color en los botones -->
<script src="../../backend/registros/script/botones_color.js"></script>

<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>

</body>
</html>
