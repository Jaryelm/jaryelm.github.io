<?php
/**
 * Campos del formulario de alta de colaborador / médico / medifarma.
 * Variables: $esMedico, $esMedifarma, $cargos, $prefillColab (array opcional), $staffFormIsAdmin (bool)
 */
require_once __DIR__ . '/../../backend/php/staff_areas_lib.php';

$prefillColab = is_array($prefillColab ?? null) ? $prefillColab : [];
$staffFv = static function (string $key, string $default = '') use ($prefillColab): string {
    $val = $prefillColab[$key] ?? $default;
    return htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8');
};
$staffAreaSelect = medidata_staff_areas_for_colaborador_select();
$staffHelpContext = $esMedico ? 'medicos' : ($esMedifarma ? 'medifarma' : 'colaboradores');
?>
<div class="staff-form-alert">
    <strong>Importante:</strong> Complete los campos marcados con <span class="badge-warning">*</span>
</div>

<?php include __DIR__ . '/_staff_area_type_help.php'; ?>

<?php if (!$esMedico && !$esMedifarma): ?>
<div class="staff-form-section">
    <h3 class="staff-form-section__title">Tipo de colaborador</h3>
    <div class="staff-form-grid staff-form-grid--single">
        <div>
            <label><b>Área o tipo de colaborador</b></label><span class="badge-warning">*</span>
            <select class="select2" name="area_colaborador" required>
                <option value="">Seleccione un área...</option>
                <?php foreach ($staffAreaSelect as $value => $label): ?>
                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</div>
<?php elseif ($esMedico): ?>
    <input type="hidden" name="area_colaborador" value="doctor">
<?php else: ?>
    <input type="hidden" name="area_colaborador" value="staff_medifarma">
<?php endif; ?>

<div class="staff-form-section">
    <h3 class="staff-form-section__title">Datos personales</h3>
    <div class="staff-form-grid">
        <?php if ($esMedico || $esMedifarma): ?>
        <div>
            <label><b>Área</b></label>
            <input type="text" value="<?php echo htmlspecialchars(medidata_staff_area_label($esMedico ? 'doctor' : 'staff_medifarma')); ?>" readonly class="staff-form-readonly">
        </div>
        <?php endif; ?>
        <div>
            <label><b>N° de Empleado (Institucional)</b></label>
            <input type="text" name="num_empleado" placeholder="ejm: EMP-001 (o dejar en blanco para automático)">
        </div>
        <div>
            <label><b>N° de identificación (DNI)</b></label><span class="badge-warning">*</span>
            <input type="text" name="identificacion" maxlength="14" placeholder="ejm: 0801199012345" required value="<?php echo $staffFv('identificacion'); ?>">
        </div>
        <div>
            <label><b>Nombres</b></label><span class="badge-warning">*</span>
            <input type="text" name="nombres" placeholder="ejm: Juan Raúl" required value="<?php echo $staffFv('nombres'); ?>">
        </div>
        <div>
            <label><b>Apellidos</b></label><span class="badge-warning">*</span>
            <input type="text" name="apellidos" placeholder="ejm: Ramírez Requena" required value="<?php echo $staffFv('apellidos'); ?>">
        </div>
        <div>
            <label><b>Fecha de nacimiento</b></label><span class="badge-warning">*</span>
            <input type="date" name="fecha_nacimiento" required value="<?php echo $staffFv('fecha_nacimiento'); ?>">
        </div>
        <div>
            <label><b>Género</b></label><span class="badge-warning">*</span>
            <select class="select2" name="genero" required>
                <option value="">Seleccione</option>
                <option value="Masculino">Masculino</option>
                <option value="Femenino">Femenino</option>
            </select>
        </div>
    </div>
</div>

<?php if ($esMedico): ?>
<?php include __DIR__ . '/_staff_doctor_fields.php'; ?>
<?php endif; ?>

<div class="staff-form-section">
    <h3 class="staff-form-section__title">Información laboral</h3>
    <div class="staff-form-grid">
        <div>
            <label><b>Tipo de Empleado</b></label><span class="badge-warning">*</span>
            <select class="select2" name="tipo_empleado" id="tipo_empleado" required onchange="document.getElementById('duracion_contrato_div').style.display = (this.value === 'Temporal' || this.value === 'Tiempo parcial') ? 'block' : 'none';">
                <option value="Permanente">Permanente</option>
                <option value="Temporal">Temporal</option>
                <option value="Tiempo parcial">Tiempo parcial</option>
            </select>
        </div>
        <div id="duracion_contrato_div" class="staff-form-field--full" style="display:none;">
            <label><b>Duración de Contrato</b></label>
            <input type="text" name="duracion_contrato" placeholder="Ej: 6 meses">
        </div>
        <div>
            <label><b>Fecha de Ingreso</b></label><span class="badge-warning">*</span>
            <input type="date" name="fecha_ingreso" required>
        </div>
        <div>
            <label><b>Departamento</b></label><span class="badge-warning">*</span>
            <select class="select2" name="id_departamento" id="id_departament" required>
                <option value="" disabled selected>Seleccione...</option>
            </select>
        </div>
        <div>
            <label><b>Cargo / Posición</b></label><span class="badge-warning">*</span>
            <select class="select2" name="id_cargo" required>
                <option value="" disabled selected>Seleccione...</option>
                <?php foreach ($cargos as $cargo): ?>
                <option value="<?php echo (int) $cargo['id']; ?>"><?php echo htmlspecialchars($cargo['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label><b>Horario</b></label><span class="badge-warning">*</span>
            <select class="select2" name="id_horario" id="id_schedule" required>
                <option value="" disabled selected>Seleccione...</option>
            </select>
        </div>
        <div class="staff-form-field--full">
            <label class="staff-form-check">
                <input type="checkbox" name="por_honorarios" id="por_honorarios" value="1">
                <b>Pago por honorarios</b>
            </label>
        </div>
        <div>
            <label><b>Nivel Salarial</b></label><span class="badge-warning">*</span>
            <select class="select2" name="id_salary_level" id="id_salary_level" required>
                <option value="" disabled selected>Seleccione...</option>
            </select>
        </div>
        <div>
            <label><b>Salario Base</b></label>
            <input type="number" step="0.01" name="salario" placeholder="Ej: 15000.00">
        </div>
        <div>
            <label><b>N° Cuenta de BAC</b></label>
            <input type="text" name="cuenta_bac" placeholder="Número de cuenta de banco BAC">
        </div>
    </div>
</div>

<div class="staff-form-section">
    <h3 class="staff-form-section__title">Contacto y accesos</h3>
    <div class="staff-form-grid">
        <div>
            <label><b>Teléfono Celular</b></label>
            <input type="text" name="telefono" placeholder="Ej: 99887766" value="<?php echo $staffFv('telefono'); ?>">
        </div>
        <div>
            <label><b>Correo Personal</b></label>
            <input type="email" name="correo_personal" placeholder="Correo electrónico personal" value="<?php echo $staffFv('correo_personal'); ?>">
        </div>
        <div>
            <label><b>Correo Institucional</b></label>
            <input type="email" name="correo_institucional" placeholder="Correo electrónico de Medicasa">
        </div>
        <div>
            <label><b>N° de Locker Asignado</b></label>
            <input type="text" name="num_locker" placeholder="Ej: L-10">
        </div>
        <div>
            <label><b>ID Empleado (Reloj Biométrico)</b></label>
            <input type="number" name="id_biometrico" placeholder="Ej: 123">
        </div>
        <div class="staff-form-field--full">
            <?php
            $staffUserFieldName = 'id_user';
            $staffSelectedUserId = 0;
            include __DIR__ . '/_staff_user_select.php';
            ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/_staff_add_documentos_section.php'; ?>
