<?php
include_once '../../backend/registros/session_check.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$editarFlashError = $_SESSION['errorMsg'] ?? null;
unset($_SESSION['errorMsg']);
if (function_exists('session_write_close')) {
    session_write_close();
}
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
<?php include __DIR__ . '/_usuarios_select2_head.php'; ?>
    <title>MEDIDATA - Editar Usuario</title>
</head>
<body>

<?php include_once '../admin/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once '../admin/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . $name . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, '../usuarios/crear_user.php')">Crear Usuarios</button>
        <button class="button" onclick="cambiarColor(this, '../usuarios/mostrar.php')">Lista de Usuarios</button>

        <br>

        <?php
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            echo '<div class="containerss"><p class="alert alert-warning">ID de usuario no válido.</p><a href="mostrar.php">Volver a la lista</a></div>';
            exit;
        }
        $stmt = $connect->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$user) {
            echo '<div class="containerss"><p class="alert alert-warning">Usuario no encontrado.</p><a href="mostrar.php">Volver a la lista</a></div>';
            exit;
        }
        ?>

        <form action="../../backend/php/actualizar_user.php" method="POST" autocomplete="off">
            <div class="containerss">
                <h1>Editar Usuario</h1>
                <br>
                <hr>

                <input type="hidden" name="user_id" value="<?php echo (int)$user->id; ?>">

                <label for="username"><b>Nombre de Usuario</b></label><span class="badge-warning">*</span>
                <input type="text" name="username" maxlength="25" value="<?php echo htmlspecialchars($user->username); ?>" required>

                <label for="nombre_completo"><b>Nombre Completo</b></label><span class="badge-warning">*</span>
                <input type="text" id="nombre_completo" name="nombre_completo" maxlength="50" value="<?php echo htmlspecialchars($user->name); ?>" required>

                <?php $sexoActual = (string) ($user->sexo ?? ''); ?>
                <label for="cedula"><b>Cédula</b></label><span class="badge-warning">*</span>
                <input type="text" id="cedula" name="cedula" maxlength="30" value="<?php echo htmlspecialchars((string) ($user->cedula ?? '')); ?>" required>

                <label for="sexo"><b>Sexo</b></label><span class="badge-warning">*</span>
                <select name="sexo" id="sexo" required>
                    <option value="">Seleccione...</option>
                    <option value="Masculino" <?php echo ($sexoActual === 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo ($sexoActual === 'Femenino') ? 'selected' : ''; ?>>Femenino</option>
                </select>

                <label for="email"><b>Correo Electrónico</b></label><span class="badge-warning">*</span>
                <input type="email" name="email" maxlength="35" value="<?php echo htmlspecialchars($user->email); ?>" required>

                <label for="rol"><b>Rol del Usuario</b></label><span class="badge-warning">*</span>
                <select name="rol" id="rol" class="select2" data-placeholder="Seleccione un rol..." required>
                    <option value="">Seleccione...</option>
                    <option value="Administrador" <?php echo ($user->rol === 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="Caja" <?php echo ($user->rol === 'Caja') ? 'selected' : ''; ?>>Caja</option>
                    <option value="Contabilidad" <?php echo ($user->rol === 'Contabilidad') ? 'selected' : ''; ?>>Contabilidad</option>
                    <option value="Auxiliar Contable" <?php echo ($user->rol === 'Auxiliar Contable') ? 'selected' : ''; ?>>Auxiliar Contable</option>
                    <option value="Facturación" <?php echo ($user->rol === 'Facturación') ? 'selected' : ''; ?>>Facturación</option>
                    <option value="Recursos_Humanos" <?php echo ($user->rol === 'Recursos_Humanos') ? 'selected' : ''; ?>>Recursos Humanos</option>
                    <option value="Mantenimiento" <?php echo ($user->rol === 'Mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                    <option value="Médico" <?php echo ($user->rol === 'Médico') ? 'selected' : ''; ?>>Médico</option>
                    <option value="Enfermero" <?php echo ($user->rol === 'Enfermero') ? 'selected' : ''; ?>>Enfermero/a</option>
                    <option value="Paciente" <?php echo ($user->rol === 'Paciente') ? 'selected' : ''; ?>>Paciente</option>
                    <option value="Proveedor" <?php echo ($user->rol === 'Proveedor') ? 'selected' : ''; ?>>Proveedor Comercial</option>
                    <option value="Servicio al Cliente" <?php echo ($user->rol === 'Servicio al Cliente') ? 'selected' : ''; ?>>Servicio al Cliente</option>
                    <option value="Almacen" <?php echo ($user->rol === 'Almacen') ? 'selected' : ''; ?>>Almacen</option>
                    <option value="Almacen Hospitalario" <?php echo ($user->rol === 'Almacen Hospitalario') ? 'selected' : ''; ?>>Almacen Hospitalario</option>
                    <option value="Radiologo" <?php echo ($user->rol === 'Radiologo') ? 'selected' : ''; ?>>Medico Radiologo</option>
                    <option value="Tecnico" <?php echo ($user->rol === 'Tecnico') ? 'selected' : ''; ?>>Técnico</option>
                    <option value="Medifarma Almacen" <?php echo ($user->rol === 'Medifarma Almacen') ? 'selected' : ''; ?>>Medifarma Almacen</option>
                    <option value="Aseo" <?php echo ($user->rol === 'Aseo') ? 'selected' : ''; ?>>Aseo</option>
                    <option value="Estacionamiento" <?php echo ($user->rol === 'Estacionamiento') ? 'selected' : ''; ?>>Estacionamiento</option>
                    <option value="Farmacia" <?php echo ($user->rol === 'Farmacia') ? 'selected' : ''; ?>>Farmacia</option>
                    <option value="Administracion" <?php echo ($user->rol === 'Administracion') ? 'selected' : ''; ?>>Administración</option>
                    <option value="Optica" <?php echo ($user->rol === 'Optica') ? 'selected' : ''; ?>>Óptica</option>
                    <option value="Odontologo" <?php echo ($user->rol === 'Odontologo') ? 'selected' : ''; ?>>Odontólogo</option>
                    <option value="Servicios Generales" <?php echo ($user->rol === 'Servicios Generales') ? 'selected' : ''; ?>>Servicios Generales</option>
                </select>

                <label for="uid_biometrico"><b>ID Biométrico (MB360)</b></label>
                <input type="text" name="uid_biometrico" value="<?php echo htmlspecialchars((string)($user->uid_biometrico ?? '')); ?>" maxlength="20">

                <p style="color:#666;font-size:13px;"><i class='bx bx-info-circle'></i> Para cambiar la contraseña use el botón "Cambiar Contraseña" en la lista de usuarios.</p>

                <hr>
                <button type="submit" name="actualizar_user" class="registerbtn">Guardar Cambios</button>
                <a href="mostrar.php" class="registerbtn registerbtn-secondary-link">Cancelar</a>
            </div>
        </form>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_usuarios_select2_foot.php'; ?>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php if ($editarFlashError !== null): ?>
<script>
Swal.fire({
    title: 'Error',
    text: <?php echo json_encode($editarFlashError, JSON_UNESCAPED_UNICODE); ?>,
    icon: 'error',
    confirmButtonText: 'Aceptar'
});
</script>
<?php endif; ?>
</body>
</html>
