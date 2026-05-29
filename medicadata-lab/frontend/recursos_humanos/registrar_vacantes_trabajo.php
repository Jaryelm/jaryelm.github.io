<?php
include_once '../../backend/registros/session_check.php';
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

        <button class="button" onclick="cambiarColor(this, 'vacantes_trabajo.php')">Listar Vacantes de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_vacantes_trabajo.php')">Registrar Vacante de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Registrar Nueva Vacante de Trabajo</h3>
                </div>
                
                <form id="vacanteForm" action="../../backend/php/add_vacante_trabajo.php" method="POST" autocomplete="off">
                    <div class="containerss">
                        <div class="alert-danger" style="margin-bottom: 20px;">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                            <strong>Importante:</strong> Los campos marcados con <span style="color:red;">*</span> son obligatorios.
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="id_position">Puesto de Trabajo (Detallado) <span style="color:red;">*</span></label>
                            <select name="id_position" id="positions_datos" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                                <option value="" disabled selected>Seleccione un puesto detallado...</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 2;">
                                <label for="vacant_name">Nombre de la Vacante <span style="color:red;">*</span></label>
                                <input type="text" name="vacant_name" id="vacant_name" required placeholder="Ej: Enfermera de Noche" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="available_slots">Plazas Disponibles <span style="color:red;">*</span></label>
                                <input type="number" name="available_slots" id="available_slots" min="1" value="1" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="requesting_department">Departamento Solicitante <span style="color:red;">*</span></label>
                                <input type="text" name="requesting_department" id="requesting_department" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="requesting_boss">Jefe Solicitante</label>
                                <input type="text" name="requesting_boss" id="requesting_boss" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="reason">Motivo de la Vacante <span style="color:red;">*</span></label>
                            <textarea name="reason" id="reason" rows="2" placeholder="Ej: Renuncia, Nuevo Puesto, Expansión..." required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="init_date">Fecha de Apertura <span style="color:red;">*</span></label>
                                <input type="date" name="init_date" id="init_date" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="end_date">Fecha Tentativa de Cierre <span style="color:red;">*</span></label>
                                <input type="date" name="end_date" id="end_date" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="priority">Prioridad <span style="color:red;">*</span></label>
                                <select name="priority" id="priority" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                                    <option value="Baja">Baja</option>
                                    <option value="Media" selected>Media</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Urgente">Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="rrhh_responsible">Responsable en RRHH</label>
                                <input type="text" name="rrhh_responsible" id="rrhh_responsible" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="publication_channel">Canal de Publicación</label>
                                <input type="text" name="publication_channel" id="publication_channel" placeholder="Ej: LinkedIn, Computrabajo" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="benefits">Beneficios (Generales / Adicionales al Puesto) <span style="color:red;">*</span></label>
                            <textarea name="benefits" id="benefits" rows="3" placeholder="Lista de beneficios ofrecidos para esta vacante..." required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="internal_observations">Observaciones Internas</label>
                            <textarea name="internal_observations" id="internal_observations" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <input type="hidden" name="created_by" value="<?php echo htmlspecialchars($name); ?>">
                        <input type="hidden" name="add_vacante" value="1">

                        <div style="display: flex; gap: 10px; margin-top: 20px; align-items: center;">
                            <button type="submit" class="registerbtn" style="flex: 1; margin: 0;">Guardar Vacante</button>
                            <a href="vacantes_trabajo.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancelar</a>
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
<script src="../../backend/js/cat_vacant_positions.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#vacanteForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    swal("¡Agregado!", response.message, "success").then(function() {
                        window.location = "vacantes_trabajo.php";
                    });
                } else {
                    swal("Error", response.message, "error");
                }
            },
            error: function() {
                swal("Error", "Ocurrió un error al procesar la solicitud", "error");
            }
        });
    });
});
</script>

</body>
</html>
