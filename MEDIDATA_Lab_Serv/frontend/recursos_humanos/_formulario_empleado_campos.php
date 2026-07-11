<?php
/** @var array<string, string> $prefill */
if (!function_exists('fe_val')) {
    function fe_val(array $prefill, string $key): string
    {
        return htmlspecialchars((string) ($prefill[$key] ?? ''), ENT_QUOTES, 'UTF-8');
    }
}
$civil = ['Soltero/a', 'Casado/a', 'Unión libre', 'Divorciado/a', 'Viudo/a', 'Separado/a'];
$yn = ['Sí', 'No'];
?>
<div class="fe-section"><h2>1. Información personal</h2>
<p class="fe-hint">Toda la información es estrictamente confidencial.</p>
<div class="fe-grid">
    <div class="full"><label>Nombre completo</label><input type="text" name="fullname" value="<?php echo fe_val($prefill, 'fullname'); ?>" required></div>
    <div><label>Dirección (calle / avenida)</label><input type="text" name="address_street" value="<?php echo fe_val($prefill, 'address_street'); ?>"></div>
    <div><label>Colonia</label><input type="text" name="colony" value="<?php echo fe_val($prefill, 'colony'); ?>"></div>
    <div><label>Ciudad</label><input type="text" name="city" value="<?php echo fe_val($prefill, 'city'); ?>"></div>
    <div><label>Teléfono fijo</label><input type="text" name="phone_home" value="<?php echo fe_val($prefill, 'phone_home'); ?>"></div>
    <div><label>Teléfono celular</label><input type="text" name="phone_cell" value="<?php echo fe_val($prefill, 'phone_cell'); ?>"></div>
    <div><label>Fecha de nacimiento</label><input type="date" name="birthdate" value="<?php echo fe_val($prefill, 'birthdate'); ?>"></div>
    <div><label>Edad</label><input type="text" name="age" value="<?php echo fe_val($prefill, 'age'); ?>"></div>
    <div><label>Lugar de nacimiento</label><input type="text" name="birth_place" value="<?php echo fe_val($prefill, 'birth_place'); ?>"></div>
    <div><label>Nacionalidad</label><input type="text" name="nationality" value="<?php echo fe_val($prefill, 'nationality'); ?>"></div>
    <div><label>Sexo</label><input type="text" name="gender" value="<?php echo fe_val($prefill, 'gender'); ?>"></div>
    <div><label>Estatura</label><input type="text" name="height" value="<?php echo fe_val($prefill, 'height'); ?>"></div>
    <div><label>Correo electrónico</label><input type="email" name="email" value="<?php echo fe_val($prefill, 'email'); ?>"></div>
    <div><label>Religión</label><input type="text" name="religion" value="<?php echo fe_val($prefill, 'religion'); ?>"></div>
    <div><label>No. cédula de identidad</label><input type="text" name="id_number" value="<?php echo fe_val($prefill, 'id_number'); ?>"></div>
    <div><label>No. afiliación IHSS</label><input type="text" name="ihss_number" value="<?php echo fe_val($prefill, 'ihss_number'); ?>"></div>
    <div><label>R.T.N.</label><input type="text" name="rtn" value="<?php echo fe_val($prefill, 'rtn'); ?>"></div>
    <div><label>Licencia No.</label><input type="text" name="license_number" value="<?php echo fe_val($prefill, 'license_number'); ?>"></div>
    <div><label>Tipo de vehículo</label><input type="text" name="vehicle_type" value="<?php echo fe_val($prefill, 'vehicle_type'); ?>"></div>
    <div><label>Estado civil</label>
        <select name="marital_status"><option value="">Seleccione...</option>
        <?php foreach ($civil as $opt): ?><option value="<?php echo htmlspecialchars($opt); ?>" <?php echo fe_val($prefill, 'marital_status') === $opt ? 'selected' : ''; ?>><?php echo htmlspecialchars($opt); ?></option><?php endforeach; ?>
        </select></div>
    <div><label>Fecha de matrimonio</label><input type="date" name="marriage_date" value="<?php echo fe_val($prefill, 'marriage_date'); ?>"></div>
    <div><label>Vive con</label><input type="text" name="lives_with" placeholder="Padres, familia, solo..." value="<?php echo fe_val($prefill, 'lives_with'); ?>"></div>
    <div><label>Personas que dependen de usted</label><input type="text" name="dependents_count" value="<?php echo fe_val($prefill, 'dependents_count'); ?>"></div>
    <div class="full"><label>Dependencia (total / parcial)</label><input type="text" name="dependents_dependency" value="<?php echo fe_val($prefill, 'dependents_dependency'); ?>"></div>
</div></div>

<div class="fe-section"><h2>2. Información familiar</h2>
<div class="fe-grid">
    <div class="full"><label>Padre — nombre y domicilio</label><input type="text" name="father_name" value="<?php echo fe_val($prefill, 'father_name'); ?>"></div>
    <div><label>Padre — trabajo / ocupación</label><input type="text" name="father_work" value="<?php echo fe_val($prefill, 'father_work'); ?>"></div>
    <div><label>Padre — teléfono</label><input type="text" name="father_phone" value="<?php echo fe_val($prefill, 'father_phone'); ?>"></div>
    <div class="full"><label>Madre — nombre y domicilio</label><input type="text" name="mother_name" value="<?php echo fe_val($prefill, 'mother_name'); ?>"></div>
    <div><label>Madre — trabajo / ocupación</label><input type="text" name="mother_work" value="<?php echo fe_val($prefill, 'mother_work'); ?>"></div>
    <div><label>Madre — teléfono</label><input type="text" name="mother_phone" value="<?php echo fe_val($prefill, 'mother_phone'); ?>"></div>
    <div class="full"><label>Cónyuge — nombre y domicilio</label><input type="text" name="spouse_name" value="<?php echo fe_val($prefill, 'spouse_name'); ?>"></div>
    <div><label>Cónyuge — trabajo</label><input type="text" name="spouse_work" value="<?php echo fe_val($prefill, 'spouse_work'); ?>"></div>
    <div><label>Cónyuge — teléfono</label><input type="text" name="spouse_phone" value="<?php echo fe_val($prefill, 'spouse_phone'); ?>"></div>
    <div class="full"><label>Hijos o hermanos (nombres, ocupación, fecha nac., teléfono)</label><textarea name="children_siblings_info" rows="3"><?php echo fe_val($prefill, 'children_siblings_info'); ?></textarea></div>
</div></div>

<div class="fe-section"><h2>3. Información de estudios</h2>
<?php
$eduLevels = [
    'secondary' => 'Secundaria',
    'bachillerato' => 'Bachillerato',
    'university' => 'Universidad',
    'maestria' => 'Maestría',
    'otros' => 'Otros',
];
foreach ($eduLevels as $key => $label):
?>
<div class="fe-subsection"><h3><?php echo htmlspecialchars($label); ?></h3>
<div class="fe-grid">
    <div><label>Institución</label><input type="text" name="edu_<?php echo $key; ?>_inst" value="<?php echo fe_val($prefill, 'edu_' . $key . '_inst'); ?>"></div>
    <div><label>Título obtenido</label><input type="text" name="edu_<?php echo $key; ?>_title" value="<?php echo fe_val($prefill, 'edu_' . $key . '_title'); ?>"></div>
    <div><label>Desde</label><input type="text" name="edu_<?php echo $key; ?>_from" value="<?php echo fe_val($prefill, 'edu_' . $key . '_from'); ?>"></div>
    <div><label>Hasta</label><input type="text" name="edu_<?php echo $key; ?>_to" value="<?php echo fe_val($prefill, 'edu_' . $key . '_to'); ?>"></div>
    <div><label>¿Diploma?</label><input type="text" name="edu_<?php echo $key; ?>_diploma" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'edu_' . $key . '_diploma'); ?>"></div>
</div></div>
<?php endforeach; ?>
<div class="fe-grid">
    <div><label>¿Estudia actualmente?</label><input type="text" name="studying_now" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'studying_now'); ?>"></div>
    <div><label>¿Qué estudios realiza?</label><input type="text" name="current_studies" value="<?php echo fe_val($prefill, 'current_studies'); ?>"></div>
    <div><label>Institución</label><input type="text" name="current_studies_inst" value="<?php echo fe_val($prefill, 'current_studies_inst'); ?>"></div>
    <div><label>Horario</label><input type="text" name="current_studies_schedule" value="<?php echo fe_val($prefill, 'current_studies_schedule'); ?>"></div>
</div>
<div class="fe-subsection"><h3>Idiomas — Inglés</h3>
<div class="fe-grid">
    <div><label>Hablo</label><input type="text" name="lang_english_speak" value="<?php echo fe_val($prefill, 'lang_english_speak'); ?>"></div>
    <div><label>Leo</label><input type="text" name="lang_english_read" value="<?php echo fe_val($prefill, 'lang_english_read'); ?>"></div>
    <div><label>Escribo</label><input type="text" name="lang_english_write" value="<?php echo fe_val($prefill, 'lang_english_write'); ?>"></div>
    <div><label>Años de estudio</label><input type="text" name="lang_english_years" value="<?php echo fe_val($prefill, 'lang_english_years'); ?>"></div>
</div></div>
<div class="fe-subsection"><h3>Otro idioma</h3>
<div class="fe-grid">
    <div><label>Idioma</label><input type="text" name="lang_other_name" value="<?php echo fe_val($prefill, 'lang_other_name'); ?>"></div>
    <div><label>Hablo / Leo / Escribo / Años</label><input type="text" name="lang_other_speak" placeholder="Hablo" value="<?php echo fe_val($prefill, 'lang_other_speak'); ?>"></div>
    <div><label></label><input type="text" name="lang_other_read" placeholder="Leo" value="<?php echo fe_val($prefill, 'lang_other_read'); ?>"></div>
    <div><label></label><input type="text" name="lang_other_write" placeholder="Escribo" value="<?php echo fe_val($prefill, 'lang_other_write'); ?>"></div>
    <div><label></label><input type="text" name="lang_other_years" placeholder="Años" value="<?php echo fe_val($prefill, 'lang_other_years'); ?>"></div>
</div></div></div>

<div class="fe-section"><h2>4. Experiencia de trabajo</h2>
<p class="fe-hint">Anote sus últimos empleos empezando por el más reciente.</p>
<?php for ($j = 1; $j <= 3; $j++): ?>
<div class="fe-subsection"><h3>Empleo <?php echo $j; ?></h3>
<div class="fe-grid">
    <div><label>Empresa</label><input type="text" name="job<?php echo $j; ?>_company" value="<?php echo fe_val($prefill, 'job' . $j . '_company'); ?>"></div>
    <div><label>Dirección</label><input type="text" name="job<?php echo $j; ?>_address" value="<?php echo fe_val($prefill, 'job' . $j . '_address'); ?>"></div>
    <div><label>Teléfono</label><input type="text" name="job<?php echo $j; ?>_phone" value="<?php echo fe_val($prefill, 'job' . $j . '_phone'); ?>"></div>
    <div><label>Inicio (mes/año)</label><input type="text" name="job<?php echo $j; ?>_start" value="<?php echo fe_val($prefill, 'job' . $j . '_start'); ?>"></div>
    <div><label>Retiro (mes/año)</label><input type="text" name="job<?php echo $j; ?>_end" value="<?php echo fe_val($prefill, 'job' . $j . '_end'); ?>"></div>
    <div><label>Sueldo inicio</label><input type="text" name="job<?php echo $j; ?>_salary_start" value="<?php echo fe_val($prefill, 'job' . $j . '_salary_start'); ?>"></div>
    <div><label>Sueldo retiro</label><input type="text" name="job<?php echo $j; ?>_salary_end" value="<?php echo fe_val($prefill, 'job' . $j . '_salary_end'); ?>"></div>
    <div class="full"><label>Cargo y funciones</label><textarea name="job<?php echo $j; ?>_role" rows="2"><?php echo fe_val($prefill, 'job' . $j . '_role'); ?></textarea></div>
    <div><label>Personas a cargo</label><input type="text" name="job<?php echo $j; ?>_people" value="<?php echo fe_val($prefill, 'job' . $j . '_people'); ?>"></div>
    <div><label>Jefe inmediato</label><input type="text" name="job<?php echo $j; ?>_boss" value="<?php echo fe_val($prefill, 'job' . $j . '_boss'); ?>"></div>
    <div class="full"><label>Motivo del retiro</label><input type="text" name="job<?php echo $j; ?>_leave_reason" value="<?php echo fe_val($prefill, 'job' . $j . '_leave_reason'); ?>"></div>
</div></div>
<?php endfor; ?>
<div class="fe-grid">
    <div><label>¿Podemos comunicarnos con su último trabajo?</label><input type="text" name="can_contact_last_job" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'can_contact_last_job'); ?>"></div>
    <div class="full"><label>Si no, explique la razón</label><textarea name="no_contact_reason" rows="2"><?php echo fe_val($prefill, 'no_contact_reason'); ?></textarea></div>
</div></div>

<div class="fe-section"><h2>5. Referencias personales</h2>
<?php for ($r = 1; $r <= 3; $r++): ?>
<div class="fe-subsection"><h3>Referencia <?php echo $r; ?></h3>
<div class="fe-grid">
    <div><label>Nombres y apellidos</label><input type="text" name="ref<?php echo $r; ?>_name" value="<?php echo fe_val($prefill, 'ref' . $r . '_name'); ?>"></div>
    <div><label>Ocupación</label><input type="text" name="ref<?php echo $r; ?>_occupation" value="<?php echo fe_val($prefill, 'ref' . $r . '_occupation'); ?>"></div>
    <div><label>Lugar de trabajo o estudio</label><input type="text" name="ref<?php echo $r; ?>_workplace" value="<?php echo fe_val($prefill, 'ref' . $r . '_workplace'); ?>"></div>
    <div><label>Dirección</label><input type="text" name="ref<?php echo $r; ?>_address" value="<?php echo fe_val($prefill, 'ref' . $r . '_address'); ?>"></div>
    <div><label>Teléfono</label><input type="text" name="ref<?php echo $r; ?>_phone" value="<?php echo fe_val($prefill, 'ref' . $r . '_phone'); ?>"></div>
    <div><label>Tiempo de conocerlo</label><input type="text" name="ref<?php echo $r; ?>_known_since" value="<?php echo fe_val($prefill, 'ref' . $r . '_known_since'); ?>"></div>
</div></div>
<?php endfor; ?></div>

<div class="fe-section"><h2>6. Información general</h2>
<div class="fe-grid">
    <div><label>Puesto que solicita</label><input type="text" name="position_applied" value="<?php echo fe_val($prefill, 'position_applied'); ?>"></div>
    <div><label>Salario mínimo esperado</label><input type="text" name="min_salary_expected" value="<?php echo fe_val($prefill, 'min_salary_expected'); ?>"></div>
    <div class="full"><label>Otras áreas de interés</label><input type="text" name="other_areas_interest" value="<?php echo fe_val($prefill, 'other_areas_interest'); ?>"></div>
    <div class="full"><label>¿Cómo se enteró de la oportunidad?</label><input type="text" name="how_found_job" value="<?php echo fe_val($prefill, 'how_found_job'); ?>"></div>
    <div class="full"><label>Páginas web donde busca empleo</label><input type="text" name="job_search_websites" value="<?php echo fe_val($prefill, 'job_search_websites'); ?>"></div>
    <div><label>¿Parientes en MEDICASA?</label><input type="text" name="relatives_at_medicasa" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'relatives_at_medicasa'); ?>"></div>
    <div class="full"><label>Nómbrelos</label><input type="text" name="relatives_names" value="<?php echo fe_val($prefill, 'relatives_names'); ?>"></div>
    <div class="full"><label>Asociaciones / sindicatos / clubes</label><input type="text" name="associations" value="<?php echo fe_val($prefill, 'associations'); ?>"></div>
    <div><label>¿Padece enfermedad u operaciones?</label><input type="text" name="has_illness" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'has_illness'); ?>"></div>
    <div class="full"><label>Detalle enfermedad</label><input type="text" name="illness_detail" value="<?php echo fe_val($prefill, 'illness_detail'); ?>"></div>
    <div><label>¿Toma medicamento?</label><input type="text" name="takes_medication" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'takes_medication'); ?>"></div>
    <div class="full"><label>Detalle medicamento</label><input type="text" name="medication_detail" value="<?php echo fe_val($prefill, 'medication_detail'); ?>"></div>
    <div class="full"><label>Pasatiempos / deportes</label><input type="text" name="hobbies" value="<?php echo fe_val($prefill, 'hobbies'); ?>"></div>
    <div><label>¿Tiene automóvil?</label><input type="text" name="has_car" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'has_car'); ?>"></div>
    <div><label>Marca y modelo</label><input type="text" name="car_brand_model" value="<?php echo fe_val($prefill, 'car_brand_model'); ?>"></div>
    <div><label>Año</label><input type="text" name="car_year" value="<?php echo fe_val($prefill, 'car_year'); ?>"></div>
    <div><label>Fecha en que podría presentarse</label><input type="text" name="available_start_date" value="<?php echo fe_val($prefill, 'available_start_date'); ?>"></div>
    <div><label>Emergencia — nombre</label><input type="text" name="emergency_contact_name" value="<?php echo fe_val($prefill, 'emergency_contact_name'); ?>"></div>
    <div><label>Emergencia — teléfono</label><input type="text" name="emergency_contact_phone" value="<?php echo fe_val($prefill, 'emergency_contact_phone'); ?>"></div>
    <div><label>Emergencia — parentesco</label><input type="text" name="emergency_contact_relation" value="<?php echo fe_val($prefill, 'emergency_contact_relation'); ?>"></div>
</div></div>

<div class="fe-section"><h2>7. Situación económica</h2>
<div class="fe-grid">
    <div><label>¿Su cónyuge trabaja?</label><input type="text" name="spouse_works" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'spouse_works'); ?>"></div>
    <div><label>Salario mensual cónyuge</label><input type="text" name="spouse_salary" value="<?php echo fe_val($prefill, 'spouse_salary'); ?>"></div>
    <div class="full"><label>Lugar de trabajo del cónyuge</label><input type="text" name="spouse_workplace" value="<?php echo fe_val($prefill, 'spouse_workplace'); ?>"></div>
    <div><label>¿Vive en casa propia?</label><input type="text" name="owns_home" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'owns_home'); ?>"></div>
    <div><label>Valor estimado vivienda</label><input type="text" name="home_value" value="<?php echo fe_val($prefill, 'home_value'); ?>"></div>
    <div><label>¿Paga renta?</label><input type="text" name="pays_rent" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'pays_rent'); ?>"></div>
    <div><label>Renta mensual</label><input type="text" name="rent_amount" value="<?php echo fe_val($prefill, 'rent_amount'); ?>"></div>
    <div><label>¿Otros ingresos?</label><input type="text" name="other_income" placeholder="Sí / No" value="<?php echo fe_val($prefill, 'other_income'); ?>"></div>
    <div><label>Importe mensual</label><input type="text" name="other_income_amount" value="<?php echo fe_val($prefill, 'other_income_amount'); ?>"></div>
    <div><label>Banco</label><input type="text" name="bank_name" value="<?php echo fe_val($prefill, 'bank_name'); ?>"></div>
    <div><label>No. cuenta</label><input type="text" name="bank_account" value="<?php echo fe_val($prefill, 'bank_account'); ?>"></div>
</div></div>

<div class="fe-section"><h2>8. Declaración</h2>
<div class="fe-grid">
    <div class="full"><label>Ciudad y fecha</label><input type="text" name="declaration_city_date" value="<?php echo fe_val($prefill, 'declaration_city_date'); ?>"></div>
    <div class="full"><label>Observaciones adicionales</label><textarea name="observations" rows="3"><?php echo fe_val($prefill, 'observations'); ?></textarea></div>
</div>
<p class="fe-hint">Declaro que las respuestas son verídicas y completas según mi leal saber y entender.</p>
</div>
