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
    <title>MEDIDATA - Registrar Puesto de Trabajo</title>
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

        <button class="button" onclick="cambiarColor(this, 'puestos_trabajo.php')">Listar Puestos de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_puesto_trabajo.php')">Registrar Puesto de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3>Registrar Nuevo Puesto de Trabajo</h3>
                </div>
                
                <form id="puestoForm" action="../../backend/php/add_puesto_trabajo.php" method="POST" autocomplete="off">
                    <div class="containerss">
                        <div class="alert-danger" style="margin-bottom: 20px;">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                            <strong>Importante:</strong> Los campos marcados con <span style="color:red;">*</span> son obligatorios.
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="id_position">Puesto de Trabajo (Base) <span style="color:red;">*</span></label>
                            <select name="id_position" id="positions_datos" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                                <option value="" disabled selected>Seleccione un puesto base...</option>
                            </select>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="department">Departamento o Área <span style="color:red;">*</span></label>
                                <input type="text" name="department" id="department" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="immediate_boss">Jefe Inmediato <span style="color:red;">*</span></label>
                                <input type="text" name="immediate_boss" id="immediate_boss" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="objective">Objetivo del Puesto <span style="color:red;">*</span></label>
                            <textarea name="objective" id="objective" rows="2" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="main_functions">Funciones Principales <span style="color:red;">*</span></label>
                            <textarea name="main_functions" id="main_functions" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="academic_requirements">Requisitos Académicos <span style="color:red;">*</span></label>
                                <textarea name="academic_requirements" id="academic_requirements" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="required_experience">Experiencia Requerida <span style="color:red;">*</span></label>
                                <textarea name="required_experience" id="required_experience" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="technical_competencies">Competencias Técnicas <span style="color:red;">*</span></label>
                                <textarea name="technical_competencies" id="technical_competencies" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="soft_competencies">Competencias Blandas <span style="color:red;">*</span></label>
                                <textarea name="soft_competencies" id="soft_competencies" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="schedule">Horario <span style="color:red;">*</span></label>
                                <input type="text" name="schedule" id="schedule" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="shift_type">Tipo de Jornada <span style="color:red;">*</span></label>
                                <input type="text" name="shift_type" id="shift_type" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="salary_range">Rango Salarial</label>
                                <input type="text" name="salary_range" id="salary_range" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="special_conditions">Condiciones Especiales</label>
                            <textarea name="special_conditions" id="special_conditions" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="suggested_psychometric_tests">Pruebas Psicométricas Sugeridas</label>
                            <textarea name="suggested_psychometric_tests" id="suggested_psychometric_tests" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"></textarea>
                        </div>

                        <input type="hidden" name="created_by" value="<?php echo htmlspecialchars($name); ?>">
                        <input type="hidden" name="add_puesto" value="1">

                        <div style="display: flex; gap: 10px; margin-top: 20px; align-items: center;">
                            <button type="submit" class="registerbtn" style="flex: 1; margin: 0;">Guardar Puesto</button>
                            <a href="puestos_trabajo.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancelar</a>
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
<script src="../../backend/js/cat_positions.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#puestoForm').on('submit', function(e) {
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
                        window.location = "puestos_trabajo.php";
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
