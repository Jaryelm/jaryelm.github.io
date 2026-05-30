<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/backend/registros/session_check.php';

$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id_edit > 0;
$edit_data = null;

if ($is_edit) {
    try {
        $stmt = $connect_rrhh->prepare("SELECT * FROM positions_details WHERE id = :id AND deleted = 0");
        $stmt->bindParam(':id', $id_edit, PDO::PARAM_INT);
        $stmt->execute();
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_data) {
            $is_edit = false; // Not found
        }
    } catch (Exception $e) {
        $is_edit = false;
    }
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
    <title>MEDIDATA - <?php echo $is_edit ? 'Actualizar' : 'Registrar'; ?> Puesto de Trabajo</title>
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

        <button class="button" onclick="cambiarColor(this, 'puestos_trabajo_usr.php')">Listar Puestos de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_puesto_trabajo_usr.php')">Registrar Puesto de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3><?php echo $is_edit ? 'Actualizar' : 'Registrar Nuevo'; ?> Puesto de Trabajo</h3>
                </div>
                
                <form id="puestoForm" action="../../backend/php/<?php echo $is_edit ? 'upd' : 'add'; ?>_puesto_trabajo.php" method="POST" autocomplete="off">
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?php echo $id_edit; ?>">
                        <input type="hidden" name="upd_puesto" value="1">
                    <?php else: ?>
                        <input type="hidden" name="add_puesto" value="1">
                    <?php endif; ?>
                    
                    <div class="containerss">
                        <div class="alert-danger" style="margin-bottom: 20px;">
                            <span class="closebtn" onclick="this.parentElement.style.display='none';">&times;</span>
                            <strong>Importante:</strong> Los campos marcados con <span style="color:red;">*</span> son obligatorios.
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="id_position">Puesto de Trabajo (Base) <span style="color:red;">*</span></label>
                            <select name="id_position" id="id_position" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
                                <option value="" disabled <?php echo !$is_edit ? 'selected' : ''; ?>>Seleccione un puesto base...</option>
                                <?php 
                                try {
                                    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
                                    $stmt_p->execute();
                                    while ($row = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
                                        $selected = ($is_edit && $edit_data['id_positions'] == $row['id']) ? 'selected' : '';
                                        echo '<option value="' . $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                } catch(Exception $e) {}
                                ?>
                            </select>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="department">Departamento o Área <span style="color:red;">*</span></label>
                                <input type="text" name="department" id="department" value="<?php echo $is_edit ? htmlspecialchars($edit_data['department']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="immediate_boss">Jefe Inmediato <span style="color:red;">*</span></label>
                                <input type="text" name="immediate_boss" id="immediate_boss" value="<?php echo $is_edit ? htmlspecialchars($edit_data['immediate_boss']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="objective">Objetivo del Puesto <span style="color:red;">*</span></label>
                            <textarea name="objective" id="objective" rows="2" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['objective']) : ''; ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="main_functions">Funciones Principales <span style="color:red;">*</span></label>
                            <textarea name="main_functions" id="main_functions" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['main_functions']) : ''; ?></textarea>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="academic_requirements">Requisitos Académicos <span style="color:red;">*</span></label>
                                <textarea name="academic_requirements" id="academic_requirements" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['academic_requirements']) : ''; ?></textarea>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="required_experience">Experiencia Requerida <span style="color:red;">*</span></label>
                                <textarea name="required_experience" id="required_experience" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['required_experience']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="technical_competencies">Competencias Técnicas <span style="color:red;">*</span></label>
                                <textarea name="technical_competencies" id="technical_competencies" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['technical_competencies']) : ''; ?></textarea>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="soft_competencies">Competencias Blandas <span style="color:red;">*</span></label>
                                <textarea name="soft_competencies" id="soft_competencies" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['soft_competencies']) : ''; ?></textarea>
                            </div>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="schedule">Horario <span style="color:red;">*</span></label>
                                <input type="text" name="schedule" id="schedule" value="<?php echo $is_edit ? htmlspecialchars($edit_data['schedule']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="shift_type">Tipo de Jornada <span style="color:red;">*</span></label>
                                <input type="text" name="shift_type" id="shift_type" value="<?php echo $is_edit ? htmlspecialchars($edit_data['shift_type']) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="salary_range">Rango Salarial</label>
                                <input type="text" name="salary_range" id="salary_range" value="<?php echo $is_edit ? htmlspecialchars($edit_data['salary_range'] ?? '') : ''; ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="special_conditions">Condiciones Especiales</label>
                            <textarea name="special_conditions" id="special_conditions" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['special_conditions'] ?? '') : ''; ?></textarea>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="suggested_psychometric_tests">Pruebas Psicométricas Sugeridas</label>
                            <textarea name="suggested_psychometric_tests" id="suggested_psychometric_tests" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo $is_edit ? htmlspecialchars($edit_data['suggested_psychometric_tests'] ?? '') : ''; ?></textarea>
                        </div>

                        <input type="hidden" name="<?php echo $is_edit ? 'updated_by' : 'created_by'; ?>" value="<?php echo htmlspecialchars($name); ?>">

                        <div style="display: flex; gap: 10px; margin-top: 20px; align-items: center;">
                            <button type="submit" class="registerbtn" style="flex: 1; margin: 0;"><?php echo $is_edit ? 'Actualizar Puesto' : 'Guardar Puesto'; ?></button>
                            <a href="puestos_trabajo_usr.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancelar</a>
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
                    swal("<?php echo $is_edit ? '¡Actualizado!' : '¡Agregado!'; ?>", response.message, "success").then(function() {
                        window.location = "puestos_trabajo_usr.php";
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
