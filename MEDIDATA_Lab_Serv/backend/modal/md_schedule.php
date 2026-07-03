<!-- Modal para Horarios -->
<div class="modal-wrapper">
    <input type="checkbox" id="btns-modal-schedule-<?php echo isset($d) ? $d->id : 'new'; ?>" class="modal-check" style="display:none;">
    <div class="container-modal" id="modal-container-schedule-<?php echo isset($d) ? $d->id : 'new'; ?>">
        <div class="content-modal-large">
            <div class="head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="color: #035c67; margin: 0;"><?php echo isset($d) ? 'Editar' : 'Registrar'; ?> Horario Laboral</h2>
                <label for="btns-modal-schedule-<?php echo isset($d) ? $d->id : 'new'; ?>" style="cursor: pointer; font-size: 24px; color: #aaa;">&times;</label>
            </div>
            <hr><br>

            <form id="scheduleForm_<?php echo isset($d) ? $d->id : 'new'; ?>" method="POST" action="../../backend/php/<?php echo isset($d) ? 'upd' : 'add'; ?>_schedule.php" autocomplete="off">
                <?php if (isset($d)): ?>
                    <input type="hidden" name="id" value="<?php echo $d->id; ?>">
                    <input type="hidden" name="upd_schedule" value="1">
                <?php else: ?>
                    <input type="hidden" name="add_schedule" value="1">
                <?php endif; ?>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Nombre del Horario <span style="color:red;">*</span></label>
                    <input type="text" name="name" value="<?php echo isset($d) ? htmlspecialchars($d->name) : ''; ?>" required placeholder="Ej: Administrativo Tegucigalpa, Turno Nocturno" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
                </div>

                <h4 style="margin: 15px 0 10px; color: #035c67;">Detalle por Días</h4>
                <div class="responsive-table">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background-color: #f2f2f2;">
                                <th style="padding: 10px; border: 1px solid #ddd;">Día</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Activo</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Entrada</th>
                                <th style="padding: 10px; border: 1px solid #ddd;">Salida</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $days = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];
                            $existing_details = [];
                            if (isset($d) && isset($pdoRrhh)) {
                                $stmtD = $pdoRrhh->prepare("SELECT day, entry_time, exit_time FROM schedule_details WHERE id_schedule = ?");
                                $stmtD->execute([$d->id]);
                                while ($rowD = $stmtD->fetch(PDO::FETCH_ASSOC)) {
                                    $existing_details[$rowD['day']] = $rowD;
                                }
                            }
                            
                            foreach ($days as $index => $dayName): 
                                $checked = isset($existing_details[$dayName]) ? 'checked' : '';
                                $entry = isset($existing_details[$dayName]) ? $existing_details[$dayName]['entry_time'] : '08:00';
                                $exit = isset($existing_details[$dayName]) ? $existing_details[$dayName]['exit_time'] : '17:00';
                            ?>
                            <tr>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"><?php echo $dayName; ?></td>
                                <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                    <input type="checkbox" class="day-check" name="details[<?php echo $index; ?>][active]" value="1" <?php echo $checked; ?>>
                                    <input type="hidden" name="details[<?php echo $index; ?>][day]" value="<?php echo $dayName; ?>">
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <input type="time" name="details[<?php echo $index; ?>][entry_time]" value="<?php echo $entry; ?>" style="width: 100%; padding: 5px;">
                                </td>
                                <td style="padding: 10px; border: 1px solid #ddd;">
                                    <input type="time" name="details[<?php echo $index; ?>][exit_time]" value="<?php echo $exit; ?>" style="width: 100%; padding: 5px;">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="registerbtn" style="flex: 1; margin: 0;">Guardar Horario</button>
                    <label for="btns-modal-schedule-<?php echo isset($d) ? $d->id : 'new'; ?>" class="pabtn" style="flex: 1; margin: 0; text-align: center; cursor: pointer; display: flex; align-items: center; justify-content: center;">Cerrar</label>
                </div>
            </form>

            <script type="text/javascript">
            $(document).ready(function() {
                $('#scheduleForm_<?php echo isset($d) ? $d->id : 'new'; ?>').on('submit', function(e) {
                    e.preventDefault();
                    // Optional: Validation to ensure at least one day is checked
                    if ($('.day-check:checked').length === 0) {
                        swal("Aviso", "Debe activar al menos un día para el horario.", "warning");
                        return;
                    }
                    
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
