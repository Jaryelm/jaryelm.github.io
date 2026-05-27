<!--Ventana Modal Puesto-->
<div class="modal-wrapper">
    <input type="checkbox" id="btns-modal-puesto-<?php echo $d->id; ?>" class="modal-check" style="display:none;">
    <div class="container-modal" id="modal-container-puesto-<?php echo $d->id; ?>">
        <div class="content-modal" style="max-height: 90vh; overflow-y: auto; width: 600px;">
            <div class="head" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="color: #035c67;">Detalles y Actualización de Puesto</h2>
            </div>
            <hr>
            <br>

            <form id="updPuestoForm_<?php echo $d->id; ?>" method="POST" action="../../backend/php/upd_puesto_trabajo.php" autocomplete="off">
                <input type="hidden" name="id" value="<?php echo $d->id; ?>">
                <input type="hidden" name="upd_puesto" value="1">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="id_position_<?php echo $d->id; ?>"><b>Puesto de Trabajo</b></label><span class="badge-warning">*</span>
                    <select name="id_position" id="id_position_<?php echo $d->id; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff;">
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

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="description_<?php echo $d->id; ?>"><b>Descripción</b></label><span class="badge-warning">*</span>
                    <textarea name="description" id="description_<?php echo $d->id; ?>" rows="6" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($d->description); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="requirements_<?php echo $d->id; ?>"><b>Requerimientos</b></label><span class="badge-warning">*</span>
                    <textarea name="requirements" id="requirements_<?php echo $d->id; ?>" rows="6" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($d->requirements); ?></textarea>
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
