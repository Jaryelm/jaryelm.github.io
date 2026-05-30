<!--Ventana Modal Puesto-->
<div class="modal-wrapper">
    <input type="checkbox" id="btns-modal-puesto-<?php echo $d->id; ?>" class="modal-check" style="display:none;">
    <div class="container-modal" id="modal-container-puesto-<?php echo $d->id; ?>">
        <div class="content-modal-large">
            <div class="head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #035c67; margin: 0;">Detalles y Actualización de Puesto</h2>
                <label for="btns-modal-puesto-<?php echo $d->id; ?>" style="cursor: pointer; font-size: 24px; color: #aaa;">&times;</label>
            </div>
            <hr>
            <br>

            <form id="updPuestoForm_<?php echo $d->id; ?>" method="POST" action="../../backend/php/upd_puesto_trabajo.php" autocomplete="off">
                <input type="hidden" name="id" value="<?php echo $d->id; ?>">
                <input type="hidden" name="upd_puesto" value="1">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="id_position_<?php echo $d->id; ?>">Puesto de Trabajo (Base) <span style="color:red;">*</span></label>
                    <select name="id_position" id="id_position_<?php echo $d->id; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff; margin: 5px 0;">
                        <?php if (isset($puestos_list)): ?>
                            <?php foreach ($puestos_list as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $d->id_positions) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="<?php echo $d->id_positions; ?>" selected><?php echo htmlspecialchars($d->name); ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="department_<?php echo $d->id; ?>">Departamento o Área <span style="color:red;">*</span></label>
                        <input type="text" name="department" id="department_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->department); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="immediate_boss_<?php echo $d->id; ?>">Jefe Inmediato <span style="color:red;">*</span></label>
                        <input type="text" name="immediate_boss" id="immediate_boss_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->immediate_boss); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="objective_<?php echo $d->id; ?>">Objetivo del Puesto <span style="color:red;">*</span></label>
                    <textarea name="objective" id="objective_<?php echo $d->id; ?>" rows="2" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->objective); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="main_functions_<?php echo $d->id; ?>">Funciones Principales <span style="color:red;">*</span></label>
                    <textarea name="main_functions" id="main_functions_<?php echo $d->id; ?>" rows="4" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->main_functions); ?></textarea>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="academic_requirements_<?php echo $d->id; ?>">Requisitos Académicos <span style="color:red;">*</span></label>
                        <textarea name="academic_requirements" id="academic_requirements_<?php echo $d->id; ?>" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->academic_requirements); ?></textarea>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="required_experience_<?php echo $d->id; ?>">Experiencia Requerida <span style="color:red;">*</span></label>
                        <textarea name="required_experience" id="required_experience_<?php echo $d->id; ?>" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->required_experience); ?></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="technical_competencies_<?php echo $d->id; ?>">Competencias Técnicas <span style="color:red;">*</span></label>
                        <textarea name="technical_competencies" id="technical_competencies_<?php echo $d->id; ?>" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->technical_competencies); ?></textarea>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="soft_competencies_<?php echo $d->id; ?>">Competencias Blandas <span style="color:red;">*</span></label>
                        <textarea name="soft_competencies" id="soft_competencies_<?php echo $d->id; ?>" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->soft_competencies); ?></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="schedule_<?php echo $d->id; ?>">Horario <span style="color:red;">*</span></label>
                        <input type="text" name="schedule" id="schedule_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->schedule); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="shift_type_<?php echo $d->id; ?>">Tipo de Jornada <span style="color:red;">*</span></label>
                        <input type="text" name="shift_type" id="shift_type_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->shift_type); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 200px;">
                        <label for="salary_range_<?php echo $d->id; ?>">Rango Salarial</label>
                        <input type="text" name="salary_range" id="salary_range_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->salary_range); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="special_conditions_<?php echo $d->id; ?>">Condiciones Especiales</label>
                        <textarea name="special_conditions" id="special_conditions_<?php echo $d->id; ?>" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->special_conditions); ?></textarea>
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="suggested_psychometric_tests_<?php echo $d->id; ?>">Pruebas Psicométricas Sugeridas</label>
                        <textarea name="suggested_psychometric_tests" id="suggested_psychometric_tests_<?php echo $d->id; ?>" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->suggested_psychometric_tests); ?></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="registerbtn" style="flex: 1; margin: 0;">Guardar Cambios</button>
                    <label for="btns-modal-puesto-<?php echo $d->id; ?>" class="pabtn" style="flex: 1; margin: 0; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center;">Cerrar</label>
                </div>
            </form>

            <script type="text/javascript">
            $(document).ready(function() {
                $('#updPuestoForm_<?php echo $d->id; ?>').on('submit', function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                swal("¡Actualizado!", response.message, "success").then(function() {
                                    window.location.reload();
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
        </div>
        <label for="btns-modal-puesto-<?php echo $d->id; ?>" class="cerrar-modal"></label>
    </div>
</div>

<style>
#btns-modal-puesto-<?php echo $d->id; ?>:checked ~ #modal-container-puesto-<?php echo $d->id; ?> {
    display: flex;
}
</style>
