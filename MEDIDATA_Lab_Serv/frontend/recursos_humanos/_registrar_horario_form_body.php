<?php
/**
 * Contenido interno del formulario de horario (admin + RRHH).
 * Requiere: $is_edit, $edit_data, $pdoRrhh, $id_edit, $cancelUrl, $name
 */
require_once __DIR__ . '/../../backend/php/schedule_lib.php';

if (isset($pdoRrhh) && $pdoRrhh instanceof PDO) {
    medidata_schedule_ensure_schema($pdoRrhh);
}

$breakMinutes = (int) ($edit_data['break_minutes'] ?? 0);
$breakOptions = medidata_schedule_break_options();
$btnLabel = $is_edit ? 'Actualizar Horario' : 'Guardar Horario';
?>
                    <div class="containerss rrhh-horario-form">
                        <div class="form-group rrhh-horario-field-block">
                            <label for="schedule_name">Nombre descriptivo del Horario <span style="color:red;">*</span></label>
                            <input type="text" id="schedule_name" class="rrhh-horario-input" name="name" value="<?php echo $is_edit ? htmlspecialchars($edit_data['name']) : ''; ?>" required placeholder="Ej: Administrativo Tegucigalpa, Turno Nocturno B">
                        </div>

                        <div class="form-group rrhh-horario-field-block">
                            <label for="break_minutes">Horario de descanso (por día laboral)</label>
                            <div class="rrhh-horario-break-wrap">
                                <select name="break_minutes" id="break_minutes" class="rrhh-horario-input">
                                <?php foreach ($breakOptions as $val => $label): ?>
                                <option value="<?php echo (int) $val; ?>" <?php echo $breakMinutes === (int) $val ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                                </select>
                            </div>
                            <p class="rrhh-horario-field-hint">Indique la duración del descanso y marque en qué días aplica en la tabla inferior. Desmarque el descanso en jornadas cortas (por ejemplo, sábado o viernes del Edecan).</p>
                        </div>

                        <div id="horario-resumen" style="background:#f0f9fa;border:1px solid #06adbf;border-radius:8px;padding:16px 20px;margin-bottom:22px;">
                            <h4 style="margin:0 0 12px;color:#035c67;">Resumen semanal</h4>
                            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;font-size:.95rem;">
                                <div><strong>Días activos:</strong> <span id="horario-active-days">0</span></div>
                                <div><strong>Tiempo bruto:</strong> <span id="horario-gross">0 hrs</span></div>
                                <div><strong>Descanso total:</strong> <span id="horario-break-total">No aplica</span></div>
                                <div><strong>Tiempo efectivo:</strong> <span id="horario-effective" style="color:#035c67;font-weight:700;">0 hrs</span></div>
                            </div>
                        </div>

                        <input type="hidden" name="weekly_effective_hours" id="weekly_effective_hours" value="<?php echo htmlspecialchars((string) ($edit_data['weekly_effective_hours'] ?? '0')); ?>">

                        <h4 style="margin: 25px 0 15px; color: #035c67; border-bottom: 2px solid #eee; padding-bottom: 5px;">Detalle de Días Laborales</h4>
                        <p style="color: #666; margin-bottom: 20px; font-size: 0.9rem;">Marque los días activos, horas de entrada/salida y si ese día corresponde descontar descanso.</p>

                        <div class="responsive-table">
                            <table style="width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                                <thead>
                                    <tr style="background-color: #035c67; color: #fff;">
                                        <th style="padding: 15px; border: 1px solid #035c67;">Día</th>
                                        <th style="padding: 15px; border: 1px solid #035c67; width: 100px;">¿Activo?</th>
                                        <th style="padding: 15px; border: 1px solid #035c67;">Entrada</th>
                                        <th style="padding: 15px; border: 1px solid #035c67;">Salida</th>
                                        <th style="padding: 15px; border: 1px solid #035c67; width: 110px;">¿Descanso?</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $days = ['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do'];
                                    $existing_details = [];
                                    if ($is_edit && $pdoRrhh) {
                                        foreach (medidata_schedule_details_from_db($pdoRrhh, $id_edit) as $rowD) {
                                            $existing_details[$rowD['day']] = $rowD;
                                        }
                                    }

                                    foreach ($days as $index => $dayName):
                                        $checked = isset($existing_details[$dayName]) ? 'checked' : '';
                                        $entry = isset($existing_details[$dayName]) ? $existing_details[$dayName]['entry_time'] : '08:00';
                                        $exit = isset($existing_details[$dayName]) ? $existing_details[$dayName]['exit_time'] : '17:00';
                                        $applyBreakChecked = isset($existing_details[$dayName])
                                            ? ((int) ($existing_details[$dayName]['apply_break'] ?? 1) === 1 ? 'checked' : '')
                                            : 'checked';
                                    ?>
                                    <tr style="<?php echo $index % 2 === 0 ? 'background-color: #f9f9f9;' : ''; ?>">
                                        <td style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; color: #333;"><?php echo $dayName; ?></td>
                                        <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                                            <input type="checkbox" class="day-check" name="details[<?php echo $index; ?>][active]" value="1" <?php echo $checked; ?> style="transform: scale(1.5); cursor: pointer;">
                                            <input type="hidden" name="details[<?php echo $index; ?>][day]" value="<?php echo $dayName; ?>">
                                        </td>
                                        <td style="padding: 12px; border: 1px solid #ddd;">
                                            <input type="time" name="details[<?php echo $index; ?>][entry_time]" value="<?php echo htmlspecialchars($entry); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" <?php echo $checked ? '' : 'disabled'; ?>>
                                        </td>
                                        <td style="padding: 12px; border: 1px solid #ddd;">
                                            <input type="time" name="details[<?php echo $index; ?>][exit_time]" value="<?php echo htmlspecialchars($exit); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" <?php echo $checked ? '' : 'disabled'; ?>>
                                        </td>
                                        <td style="padding: 12px; border: 1px solid #ddd; text-align: center;">
                                            <input type="checkbox" class="day-break-check" name="details[<?php echo $index; ?>][apply_break]" value="1" <?php echo $applyBreakChecked; ?> <?php echo $checked ? '' : 'disabled'; ?> style="transform: scale(1.5); cursor: pointer;" title="Desmarque si este día no tiene descanso (jornada corta)">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <input type="hidden" name="<?php echo $is_edit ? 'updated_by' : 'created_by'; ?>" value="<?php echo htmlspecialchars($name); ?>">

                        <div style="display: flex; gap: 15px; margin-top: 30px; align-items: center;">
                            <button type="submit" class="registerbtn" id="btnGuardarHorario" style="flex: 1; margin: 0; padding: 15px;" <?php echo medidata_rrhh_disponible() ? '' : 'disabled'; ?>><?php echo htmlspecialchars($btnLabel); ?></button>
                            <a href="<?php echo htmlspecialchars($cancelUrl); ?>" class="pabtn" style="flex: 1; margin: 0; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; padding: 15px;">Cancelar</a>
                        </div>
                    </div>
