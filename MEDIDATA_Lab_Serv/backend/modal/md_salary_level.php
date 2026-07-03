<!-- Modal para Niveles Salariales -->
<div class="modal-wrapper">
    <input type="checkbox" id="btns-modal-salary-<?php echo isset($d) ? $d->id : 'new'; ?>" class="modal-check" style="display:none;">
    <div class="container-modal" id="modal-container-salary-<?php echo isset($d) ? $d->id : 'new'; ?>">
        <div class="content-modal">
            <div class="head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #035c67; margin: 0;"><?php echo isset($d) ? 'Editar' : 'Registrar'; ?> Nivel Salarial</h2>
                <label for="btns-modal-salary-<?php echo isset($d) ? $d->id : 'new'; ?>" style="cursor: pointer; font-size: 24px; color: #aaa;">&times;</label>
            </div>
            <hr><br>

            <form id="salaryLevelForm_<?php echo isset($d) ? $d->id : 'new'; ?>" method="POST" action="../../backend/php/<?php echo isset($d) ? 'upd' : 'add'; ?>_salary_level.php" autocomplete="off">
                <?php if (isset($d)): ?>
                    <input type="hidden" name="id" value="<?php echo $d->id; ?>">
                    <input type="hidden" name="upd_salary_level" value="1">
                <?php else: ?>
                    <input type="hidden" name="add_salary_level" value="1">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Nombre del Nivel <span style="color:red;">*</span></label>
                    <input type="text" name="level_name" value="<?php echo isset($d) ? htmlspecialchars($d->level_name) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Categoría del Cargo <span style="color:red;">*</span></label>
                    <input type="text" name="position_category" value="<?php echo isset($d) ? htmlspecialchars($d->position_category) : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;" placeholder="Ej: Administrativo, Operativo, Gerencial">
                </div>

                <div style="display: flex; gap: 15px; margin-bottom: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Salario Mínimo <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" name="min_salary" value="<?php echo isset($d) ? $d->min_salary : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Salario Máximo <span style="color:red;">*</span></label>
                        <input type="number" step="0.01" name="max_salary" value="<?php echo isset($d) ? $d->max_salary : ''; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="registerbtn" style="flex: 1; margin: 0;">Guardar</button>
                    <label for="btns-modal-salary-<?php echo isset($d) ? $d->id : 'new'; ?>" class="pabtn" style="flex: 1; margin: 0; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center;">Cerrar</label>
                </div>
            </form>

            <script type="text/javascript">
            $(document).ready(function() {
                $('#salaryLevelForm_<?php echo isset($d) ? $d->id : 'new'; ?>').on('submit', function(e) {
                    e.preventDefault();
                    $.ajax({
                        type: 'POST',
                        url: $(this).attr('action'),
                        data: $(this).serialize(),
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                swal("¡Éxito!", response.message, "success").then(function() {
                                    window.location.reload();
                                });
                            } else {
                                swal("Error", response.message, "error");
                            }
                        }
                    });
                });
            });
            </script>
        </div>
    </div>
</div>
