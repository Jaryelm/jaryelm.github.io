<?php
include_once '../../backend/registros/session_check.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='/backend/vendor/boxicons/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="stylesheet" href="../../backend/css/cards.css">
<?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="/backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once '../recursos_humanos/menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
<?php include_once '../recursos_humanos/perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = (int) date('H');
        if ($hora_actual >= 6 && $hora_actual < 12) {
            $saludo = 'Buenos Días';
        } elseif ($hora_actual >= 12 && $hora_actual < 18) {
            $saludo = 'Buenas Tardes';
        } else {
            $saludo = 'Buenas Noches';
        }
        ?>

        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'enfermera_nuevo_usr.php')">Registrar Enfermería</button>
        <button class="button" onclick="cambiarColor(this, 'enfermera_usr.php')">Enfermería</button>

        <form action="" enctype="multipart/form-data" method="POST" autocomplete="off" onsubmit="return validacion()">
            <div class="containerss">
                <h1>Nueva Enfermero/a</h1>

                <div class="alert-danger">
                    <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                    <strong>Importante!</strong> Es importante rellenar los campos con &nbsp;<span class="badge-warning">*</span>
                </div>
                <hr>

                <label for="nuriden"><b>N° de identificación de la enfermera/o</b></label><span class="badge-warning">*</span>
                <input type="text" id="nuriden" placeholder="ejm: 09741478" name="nuriden" maxlength="14" required>

                <label for="nurnam"><b>Nombre de la enfermera/o</b></label><span class="badge-warning">*</span>
                <input type="text" id="nurnam" placeholder="ejm: Juan Raul" name="nurnam" required>

                <label for="nurape"><b>Apellido de la enfermera/o</b></label><span class="badge-warning">*</span>
                <input type="text" id="nurape" placeholder="ejm: Ramirez Requena" name="nurape" required>

                <label for="nurdat"><b>Fecha de nacimiento de la enfermera/o</b></label><span class="badge-warning">*</span>
                <input type="date" id="nurdat" name="nurdat" required>

                <label for="gep"><b>Género de la enfermera/o</b></label><span class="badge-warning">*</span>
                <select class="select2" required name="nurge" id="gep">
                    <option value="">Seleccione</option>
                    <option value="Masculino">Masculino</option>
                    <option value="Femenino">Femenino</option>
                </select>

                <hr>
                <button type="submit" name="add_nurse" class="registerbtn">Guardar</button>
            </div>
        </form>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/../recursos_humanos/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/multistep.js"></script>
<script src="../../backend/js/vpat.js"></script>
<script src="/backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<?php include_once '../../backend/php/add_nurse.php'; ?>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
</body>
</html>
