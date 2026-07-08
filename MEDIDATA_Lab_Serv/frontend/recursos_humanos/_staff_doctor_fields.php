<?php
/**
 * Campos específicos de médicos (tabla doctor — columnas legacy).
 * Variables opcionales: $staffDoctorRow (object con nomesp, direcd, comisiona)
 */
require_once __DIR__ . '/../../backend/php/staff_doctor_fields_lib.php';

$staffDoctorRow = $staffDoctorRow ?? null;
$espResolved = medidata_staff_doctor_resolve_especialidad_value($staffDoctorRow->nomesp ?? '');
$doctorDireccion = '';
if ($staffDoctorRow && isset($staffDoctorRow->direcd) && strtoupper(trim($staffDoctorRow->direcd)) !== 'N/A') {
    $doctorDireccion = trim($staffDoctorRow->direcd);
}
$doctorComisiona = ($staffDoctorRow && ($staffDoctorRow->comisiona ?? 'NO') === 'SI');
?>
<div class="staff-form-section staff-form-section--doctor" id="staffDoctorFields">
    <h3 class="staff-form-section__title">Datos profesionales del médico</h3>
    <p class="staff-form-section__hint">La especialidad define la categoría en listas, honorarios y módulos como Radiología (debe contener &quot;RADIOLOGIA&quot; para aparecer en el worklist PACS).</p>
    <div class="staff-form-grid">
        <div>
            <label><b>Especialidad / Categoría</b></label><span class="badge-warning">*</span>
            <select class="select2" name="doctor_especialidad" id="doctor_especialidad" required>
                <option value="">Seleccione especialidad...</option>
                <?php foreach (medidata_staff_doctor_especialidades() as $esp): ?>
                <option value="<?php echo htmlspecialchars($esp); ?>" <?php echo $espResolved['select'] === $esp ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($esp); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="doctor_especialidad_otra_wrap" style="<?php echo $espResolved['select'] === 'OTRA' ? '' : 'display:none;'; ?>">
            <label><b>Especifique especialidad</b></label><span class="badge-warning">*</span>
            <input type="text" name="doctor_especialidad_otra" id="doctor_especialidad_otra"
                   value="<?php echo htmlspecialchars($espResolved['otra']); ?>"
                   placeholder="Ej: CARDIOLOGIA">
        </div>
        <div class="staff-form-field--full">
            <label><b>Dirección</b></label>
            <input type="text" name="doctor_direccion" value="<?php echo htmlspecialchars($doctorDireccion); ?>"
                   placeholder="Opcional — si no indica, se guarda N/A">
        </div>
        <div class="staff-form-field--full">
            <label class="staff-form-check">
                <input type="checkbox" name="doctor_comisiona" value="1" <?php echo $doctorComisiona ? 'checked' : ''; ?>>
                <b>Comisiona honorarios</b> (aplica en módulo de honorarios médicos)
            </label>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var sel = document.getElementById('doctor_especialidad');
    var wrap = document.getElementById('doctor_especialidad_otra_wrap');
    var otra = document.getElementById('doctor_especialidad_otra');
    if (!sel || !wrap) return;
    function toggleOtra() {
        var isOtra = sel.value === 'OTRA';
        wrap.style.display = isOtra ? '' : 'none';
        if (otra) otra.required = isOtra;
    }
    sel.addEventListener('change', toggleOtra);
    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
        jQuery(sel).on('change', toggleOtra);
    }
    toggleOtra();
});
</script>
