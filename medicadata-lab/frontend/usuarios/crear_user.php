<?php
include_once '../../backend/registros/session_check.php';
// incuir el archivo de sesion login
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">

    <title>MEDIDATA</title>
</head>
<body>
    
<?php
include_once '../admin/menu.php';
// incuir el archivo menu principal
?>

<!-- NAVBAR -->
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
        // Obtener la hora actual
        $hora_actual = date('H'); // Obtiene la hora en formato de 24 horas (0-23)

        if ($hora_actual >= 6 && $hora_actual < 12) {
            $saludo = "Buenos Días";
        } elseif ($hora_actual >= 12 && $hora_actual < 18) {
            $saludo = "Buenas Tardes";
        } else {
            $saludo = "Buenas Noches";
        }
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, '../usuarios/crear_user.php')">Crear Usuarios</button>
        <button class="button" onclick="cambiarColor(this, '../usuarios/mostrar.php')">Lista de Usuarios</button>

        <br>
        <form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validacion()">
            <div class="containerss">
                <h1>Administrar Usuarios</h1>
                <br>
                <hr>

                <label for="username"><b>Nombre de Usuario</b></label><span class="badge-warning">*</span>
                <input type="text" placeholder="ejm: moises.castillo" name="username" required>

                <label for="name"><b>Nombre Completo</b></label><span class="badge-warning">*</span>
                <input type="text" placeholder="ejm: Juan Pérez" name="name" required>

                <label for="email"><b>Correo Electrónico</b></label><span class="badge-warning">*</span>
                <input type="email" placeholder="ejm: moises.castillo@medicasa.hn" name="email" required>

                <label for="password"><b>Contraseña</b></label><span class="badge-warning">*</span>
                <input type="password" name="password" required>

                <label for="rol"><b>Rol del Usuario</b></label><span class="badge-warning">*</span>
                <select name="rol" required>
                    <option value="">Seleccione...</option>
                    <option value="Administrador">Administrador</option>
                    <option value="Caja">Caja</option>
                    <option value="Contabilidad">Contabilidad</option>
                    <option value="Auxiliar Contable">Auxiliar Contable</option>
                    <option value="Facturación">Facturación</option>
                    <option value="Recursos_Humanos">Recursos Humanos</option>
                    <option value="Mantenimiento">Mantenimiento</option>
                    <option value="Médico">Médico</option>
                    <option value="Enfermero">Enfermero/a</option>
                    <option value="Paciente">Paciente</option>
                    <option value="Proveedor">Proveedor Comercial</option>
                    <option value="Servicio al Cliente">Servicio al Cliente</option>
                    <option value="Almacen">Almacen</option>
                    <option value="Almacen Hospitalario">Almacen Hospitalario</option>
                    <option value="Radiologo">Medico Radiologo</option>
                    <option value="Tecnico">Técnico</option>
                    <option value="Medifarma Almacen">Medifarma Almacen</option>
                </select>

                <hr>
                <button type="submit" name="add_user" class="registerbtn">Guardar Usuario</button>
            </div>
        </form>
    </main>
    <!-- MAIN -->
</section>

<!-- NAVBAR -->
<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<!-- SubMenu -->
<script src='../../backend/js/submenu.js'></script>
<!-- Script para manejar el cambio de color en los botones -->
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<?php include_once '../../backend/php/crear_user.php' ?>

</body>
</html>



