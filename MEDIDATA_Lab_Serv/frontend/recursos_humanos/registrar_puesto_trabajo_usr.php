<?php
include_once '../../backend/registros/session_check.php';
require_once '../../backend/registros/rrhh_guard.php';

$id_edit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id_edit > 0;
$edit_data = null;
$pdoRrhh = medidata_rrhh_pdo();

if ($is_edit && $pdoRrhh) {
    try {
        $stmt = $pdoRrhh->prepare("SELECT * FROM positions_details WHERE id = :id AND deleted = 0");
        $stmt->bindParam(':id', $id_edit, PDO::PARAM_INT);
        $stmt->execute();
        $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_data) {
            $is_edit = false;
        }
    } catch (Throwable $e) {
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
<?php include __DIR__ . '/_rrhh_select2_head.php'; ?>

    <link rel="icon" type="image/png" sizes="96x96" href="../../backend/img/icon.png">
    <link rel="stylesheet" href="../../backend/vendor/sweetalert2/sweetalert2.min.css">
    <title>MEDIDATA</title>
</head>
<body>

<?php include_once './menu.php'; ?>

<section id="content">
    <nav>
        <i class='bx bx-menu toggle-sidebar'></i>
        <form action="#"><div class="form-group"></div></form>
        <span class="divider"></span>
        <?php include_once './perfil.php'; ?>
    </nav>

    <main>
        <?php
        $hora_actual = date('H');
        $saludo = ($hora_actual >= 6 && $hora_actual < 12) ? "Buenos Días" : (($hora_actual >= 12 && $hora_actual < 18) ? "Buenas Tardes" : "Buenas Noches");
        ?>
        <h1 class="title"><?php echo $saludo . ', <strong>' . htmlspecialchars($name) . '</strong>'; ?></h1>

        <?php if (!medidata_rrhh_disponible()): ?>
        <div class="alert-danger" style="margin-bottom: 20px;">
            <strong>Base de datos RRHH no disponible.</strong>
            No se puede guardar hasta que esté activa <code>medic9ue_medi_rrhh_interviews</code>.
            <?php if ($err = medidata_rrhh_last_error()): ?>
            <br><small><?php echo htmlspecialchars($err); ?></small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <button class="button" onclick="cambiarColor(this, 'puestos_trabajo_usr.php')">Listar Puestos de Trabajo</button>
        <button class="button" onclick="cambiarColor(this, 'registrar_puesto_trabajo_usr.php')">Registrar Puesto de Trabajo</button>

        <div class="data">
            <div class="content-data">
                <div class="head">
                    <h3><?php echo $is_edit ? 'Actualizar' : 'Registrar Nuevo'; ?> Puesto de Trabajo</h3>
                </div>
                
                <form id="puestoForm" action="../../backend/php/<?php echo $is_edit ? 'upd' : 'add'; ?>_puesto_trabajo.php" method="POST" autocomplete="off" enctype="multipart/form-data">
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
                            <select class="select2" name="id_position" id="id_position" required>
                                <option value="" disabled <?php echo !$is_edit ? 'selected' : ''; ?>>Seleccione un puesto base...</option>
                                <?php 
                                $positionsCount = 0;
                                try {
                                    $stmt_p = $connect->prepare("SELECT id, name FROM positions ORDER BY name ASC");
                                    $stmt_p->execute();
                                    while ($row = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
                                        $positionsCount++;
                                        $selected = ($is_edit && $edit_data['id_positions'] == $row['id']) ? 'selected' : '';
                                        echo '<option value="' . (int) $row['id'] . '" ' . $selected . '>' . htmlspecialchars($row['name']) . '</option>';
                                    }
                                } catch (Exception $e) {}
                                ?>
                            </select>
                            <?php if ($positionsCount === 0): ?>
                            <p style="color:#c0392b;margin-top:8px;font-size:0.9rem;">
                                No hay posiciones base registradas. Cree una en <a href="positions_usr.php">Posiciones de Trabajo</a> antes de continuar.
                            </p>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label for="id_departament">Departamento o Área <span style="color:red;">*</span></label>
                                <select class="select2" name="id_departament" id="id_departament" required>
                                    <?php if ($is_edit): ?>
                                        <option value="<?php echo (int)$edit_data['id_departament']; ?>" selected>Cargando...</option>
                                    <?php else: ?>
                                        <option value="" disabled selected>Seleccione un departamento...</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label for="immediate_boss">Jefe Inmediato <span style="color:red;">*</span></label>
                                <input type="text" name="immediate_boss" id="immediate_boss" value="<?php echo $is_edit ? htmlspecialchars($edit_data['immediate_boss']) : ''; ?>" required readonly style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #f9f9f9; cursor: not-allowed;">
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
                                <label for="id_salary_level">Nivel Salarial <span style="color:red;">*</span></label>
                                <select class="select2" name="id_salary_level" id="id_salary_level" required>
                                    <?php if ($is_edit): ?>
                                        <option value="<?php echo (int)($edit_data['id_salary_level'] ?? 0); ?>" selected>Cargando...</option>
                                    <?php else: ?>
                                        <option value="" disabled selected>Seleccione un nivel...</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 15px;">
                            <label for="job_profile_file">Documento de Perfil de Puesto (Opcional, Máx 64KB)</label>
                            <input type="file" name="job_profile_file" id="job_profile_file" accept=".pdf,.doc,.docx,.jpg,.png" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                            <?php if ($is_edit && !empty($edit_data['job_profile_file'])): ?>
                                <p style="margin-top: 5px; font-size: 0.85rem; color: #27ae60;">
                                    <i class='bx bx-file'></i> Documento actual guardado. Subir uno nuevo lo reemplazará.
                                </p>
                            <?php endif; ?>
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
                            <button type="submit" class="registerbtn" id="btnGuardarPuesto" style="flex: 1; margin: 0;" <?php echo medidata_rrhh_disponible() ? '' : 'disabled'; ?>><?php echo $is_edit ? 'Actualizar Puesto' : 'Guardar Puesto'; ?></button>
                            <a href="puestos_trabajo_usr.php" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancelar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
</section>

<script src="../../backend/js/jquery.min.js"></script>
<?php include __DIR__ . '/_rrhh_select2_foot.php'; ?>

<script src="../../backend/js/script.js"></script>
<script src="../../backend/js/submenu.js"></script>
<script src="../../backend/registros/script/botones_color.js"></script>
<script src="../../backend/vendor/sweetalert2/sweetalert2.min.js"></script>
<script src="../../backend/js/cat_departaments.js"></script>
<script src="../../backend/js/cat_salary_levels.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    $('#puestoForm').on('submit', function(e) {
        e.preventDefault();
        
        var $btn = $('#btnGuardarPuesto');
        if ($btn.prop('disabled')) {
            return;
        }

        $btn.prop('disabled', true).text('Guardando...');

        var formData = new FormData(this);
        
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire("<?php echo $is_edit ? '¡Actualizado!' : '¡Agregado!'; ?>", response.message, "success").then(function() {
                        window.location = "puestos_trabajo_usr.php";
                    });
                } else {
                    Swal.fire("Error", response.message, "error");
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text(<?php echo json_encode($is_edit ? 'Actualizar Puesto' : 'Guardar Puesto', JSON_UNESCAPED_UNICODE); ?>);
            },
            error: function(xhr) {
                var msg = "Ocurrió un error al procesar la solicitud";
                try { var r = JSON.parse(xhr.responseText); if (r.message) msg = r.message; } catch (e) {}
                Swal.fire("Error", msg, "error");
            }
        });
    });
});
</script>

</body>
</html>
