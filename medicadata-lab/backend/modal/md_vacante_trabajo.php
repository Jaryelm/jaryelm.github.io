<!--Ventana Modal Vacante-->
<div class="modal-wrapper">
    <input type="checkbox" id="btns-modal-vacante-<?php echo $d->id; ?>" class="modal-check" style="display:none;">
    <div class="container-modal" id="modal-container-vacante-<?php echo $d->id; ?>">
        <div class="content-modal-large">
            <div class="head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #035c67; margin: 0;">Detalles y Actualización de Vacante</h2>
                <label for="btns-modal-vacante-<?php echo $d->id; ?>" style="cursor: pointer; font-size: 24px; color: #aaa;">&times;</label>
            </div>
            <hr>
            <br>

            <form id="updVacanteForm_<?php echo $d->id; ?>" method="POST" action="../../backend/php/upd_vacante_trabajo.php" autocomplete="off">
                <input type="hidden" name="id" value="<?php echo $d->id; ?>">
                <input type="hidden" name="upd_vacante" value="1">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="id_position_v_<?php echo $d->id; ?>">Puesto de Trabajo (Detallado) <span style="color:red;">*</span></label>
                    <select name="id_position" id="id_position_v_<?php echo $d->id; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff; margin: 5px 0;">
                        <?php if (isset($puestos_detalles_list)): ?>
                            <?php foreach ($puestos_detalles_list as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($p['id'] == $d->id_position) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="<?php echo $d->id_position; ?>" selected><?php echo htmlspecialchars($d->position_name); ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 2; min-width: 250px;">
                        <label for="vacant_name_<?php echo $d->id; ?>">Nombre de la Vacante <span style="color:red;">*</span></label>
                        <input type="text" name="vacant_name" id="vacant_name_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->vacant_name); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 150px;">
                        <label for="available_slots_<?php echo $d->id; ?>">Plazas Disponibles <span style="color:red;">*</span></label>
                        <input type="number" name="available_slots" id="available_slots_<?php echo $d->id; ?>" value="<?php echo $d->available_slots; ?>" min="1" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="requesting_department_<?php echo $d->id; ?>">Departamento Solicitante <span style="color:red;">*</span></label>
                        <input type="text" name="requesting_department" id="requesting_department_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->requesting_department); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="requesting_boss_<?php echo $d->id; ?>">Jefe Solicitante</label>
                        <input type="text" name="requesting_boss" id="requesting_boss_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->requesting_boss); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="reason_<?php echo $d->id; ?>">Motivo de la Vacante <span style="color:red;">*</span></label>
                    <textarea name="reason" id="reason_<?php echo $d->id; ?>" rows="2" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->reason); ?></textarea>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 180px;">
                        <label for="init_date_<?php echo $d->id; ?>">Fecha de Apertura <span style="color:red;">*</span></label>
                        <input type="date" name="init_date" id="init_date_<?php echo $d->id; ?>" value="<?php echo $d->init_date; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 180px;">
                        <label for="end_date_<?php echo $d->id; ?>">Fecha Tentativa de Cierre <span style="color:red;">*</span></label>
                        <input type="date" name="end_date" id="end_date_<?php echo $d->id; ?>" value="<?php echo $d->end_date; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 180px;">
                        <label for="priority_<?php echo $d->id; ?>">Prioridad <span style="color:red;">*</span></label>
                        <select name="priority" id="priority_<?php echo $d->id; ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; background-color: #fff; margin: 5px 0;">
                            <option value="Baja" <?php echo ($d->priority == 'Baja') ? 'selected' : ''; ?>>Baja</option>
                            <option value="Media" <?php echo ($d->priority == 'Media') ? 'selected' : ''; ?>>Media</option>
                            <option value="Alta" <?php echo ($d->priority == 'Alta') ? 'selected' : ''; ?>>Alta</option>
                            <option value="Urgente" <?php echo ($d->priority == 'Urgente') ? 'selected' : ''; ?>>Urgente</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap;">
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="rrhh_responsible_<?php echo $d->id; ?>">Responsable en RRHH</label>
                        <input type="text" name="rrhh_responsible" id="rrhh_responsible_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->rrhh_responsible); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                    <div class="form-group" style="flex: 1; min-width: 250px;">
                        <label for="publication_channel_<?php echo $d->id; ?>">Canal de Publicación</label>
                        <input type="text" name="publication_channel" id="publication_channel_<?php echo $d->id; ?>" value="<?php echo htmlspecialchars($d->publication_channel); ?>" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="benefits_<?php echo $d->id; ?>">Beneficios (Generales / Adicionales al Puesto) <span style="color:red;">*</span></label>
                    <textarea name="benefits" id="benefits_<?php echo $d->id; ?>" rows="3" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->benefits); ?></textarea>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="internal_observations_<?php echo $d->id; ?>">Observaciones Internas</label>
                    <textarea name="internal_observations" id="internal_observations_<?php echo $d->id; ?>" rows="2" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; margin: 5px 0;"><?php echo htmlspecialchars($d->internal_observations); ?></textarea>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="registerbtn" style="flex: 1; margin: 0;">Guardar Cambios</button>
                    <label for="btns-modal-vacante-<?php echo $d->id; ?>" class="pabtn" style="flex: 1; margin: 0; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center;">Cerrar</label>
                </div>
            </form>

            <script type="text/javascript">
            $(document).ready(function() {
                $('#updVacanteForm_<?php echo $d->id; ?>').on('submit', function(e) {
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
        <label for="btns-modal-vacante-<?php echo $d->id; ?>" class="cerrar-modal"></label>
    </div>
</div>

<style>
#btns-modal-vacante-<?php echo $d->id; ?>:checked ~ #modal-container-vacante-<?php echo $d->id; ?> {
    display: flex;
}
</style>
