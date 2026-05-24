<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';

// Fetch job positions to populate the select dropdown
try {
    $stmt_puestos = $connect_rrhh->prepare("SELECT id, name FROM puestos_trabajo WHERE deleted = 0 ORDER BY name ASC");
    $stmt_puestos->execute();
    $puestos = $stmt_puestos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $puestos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../../backend/css/admin.css">
    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <title>MEDIDATA - Registrar Vacante de Trabajo</title>
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
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <button class="button" onclick="cambiarColor(this, 'vacantes_trabajo_usr.php')">Listar Vacantes de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_vacantes_trabajo_usr.php')">Registrar Vacante de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Registrar Nueva Vacante de Trabajo</h3>
                </div>
                
                <form action="../../backend/php/recursos_humanos/add_vacante_trabajo.php" method="POST" autocomplete="off">
                    <div class="containerss">
                        <div class="alert-danger" style="margin-bottom: 20px;">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                            <strong>Importante:</strong> Los campos marcados con <span style="color:red;">*</span> son obligatorios.
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="id_position">Puesto de Trabajo <span style="color:red;">*</span></label>
                            <select name="id_position" id="id_position" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                                <option value="" disabled selected>Seleccione un puesto...</option>
                                <?php foreach ($puestos as $puesto): ?>
                                    <option value="<?php echo htmlspecialchars($puesto['id']); ?>"><?php echo htmlspecialchars($puesto['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="init_date">Fecha de Inicio <span style="color:red;">*</span></label>
                                <input type="date" name="init_date" id="init_date" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="end_date">Fecha de Fin <span style="color:red;">*</span></label>
                                <input type="date" name="end_date" id="end_date" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="benefits">Beneficios <span style="color:red;">*</span></label>
                            <textarea name="benefits" id="benefits" rows="4" placeholder="Lista de beneficios ofrecidos para esta vacante..." required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <input type="hidden" name="created_by" value="<?php echo htmlspecialchars($name); ?>">

                        <div style="display: flex; gap: 10px; margin-top: 20px; align-items: center;">
                            <button type="submit" name="add_vacante" class="registerbtn" style="flex: 1; margin: 0;">Guardar Vacante</button>
                            <a href="vacantes_trabajo_usr.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

</body>
</html>
