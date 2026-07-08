<?php
/**
 * Ayuda contextual: tipos de personal vs catálogos (departamento, cargo).
 * Variables: $staffFormIsAdmin (bool), $staffHelpContext ('colaboradores'|'medicos'|'medifarma')
 */
require_once __DIR__ . '/../../backend/php/staff_areas_lib.php';

$staffFormIsAdmin = isset($staffFormIsAdmin) ? (bool) $staffFormIsAdmin : true;
$staffHelpContext = $staffHelpContext ?? 'colaboradores';
if (!in_array($staffHelpContext, medidata_staff_lista_contextos(), true)) {
    $staffHelpContext = 'colaboradores';
}

$agregarColab = medidata_staff_agregar_page_for_context('colaboradores', $staffFormIsAdmin);
$agregarMedico = medidata_staff_agregar_page_for_context('medicos', $staffFormIsAdmin);
$agregarMedifarma = medidata_staff_agregar_page_for_context('medifarma', $staffFormIsAdmin);
$listaColab = medidata_staff_return_page_for_context('colaboradores', $staffFormIsAdmin);
$listaMedico = medidata_staff_return_page_for_context('medicos', $staffFormIsAdmin);
$listaMedifarma = medidata_staff_return_page_for_context('medifarma', $staffFormIsAdmin);

$areasColab = medidata_staff_areas_for_colaborador_select();
$areasLista = implode(', ', array_map(static function ($label) {
    return htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
}, $areasColab));
?>
<div class="staff-area-help">
    <p><strong>¿Qué tipo de personal va a registrar?</strong></p>
    <ul>
        <?php if ($staffHelpContext === 'colaboradores'): ?>
        <li><strong>En este formulario:</strong> <?php echo $areasLista; ?> (elige el área en el select).</li>
        <li><strong>Médicos:</strong> use <a href="<?php echo htmlspecialchars($agregarMedico); ?>">Nuevo Médico</a> o la pestaña <a href="<?php echo htmlspecialchars($listaMedico); ?>">Lista de Médicos</a>.</li>
        <li><strong>Medifarma:</strong> use <a href="<?php echo htmlspecialchars($agregarMedifarma); ?>">Nuevo Medifarma</a> o <a href="<?php echo htmlspecialchars($listaMedifarma); ?>">Lista Medifarma</a>.</li>
        <?php elseif ($staffHelpContext === 'medicos'): ?>
        <li><strong>En este formulario:</strong> personal <strong>Médico</strong>. La <strong>especialidad / categoría</strong> es obligatoria (ej. RADIOLOGIA para el módulo de Radiología).</li>
        <li><strong>Enfermería, Administrativo y Servicios Generales:</strong> use <a href="<?php echo htmlspecialchars($agregarColab); ?>">Nuevo Colaborador</a> o <a href="<?php echo htmlspecialchars($listaColab); ?>">Lista de Colaboradores</a>.</li>
        <li><strong>Medifarma:</strong> use <a href="<?php echo htmlspecialchars($agregarMedifarma); ?>">Nuevo Medifarma</a> o <a href="<?php echo htmlspecialchars($listaMedifarma); ?>">Lista Medifarma</a>.</li>
        <?php else: ?>
        <li><strong>En este formulario:</strong> personal <strong>Medifarma</strong> exclusivamente.</li>
        <li><strong>Enfermería, Administrativo y Servicios Generales:</strong> use <a href="<?php echo htmlspecialchars($agregarColab); ?>">Nuevo Colaborador</a> o <a href="<?php echo htmlspecialchars($listaColab); ?>">Lista de Colaboradores</a>.</li>
        <li><strong>Médicos:</strong> use <a href="<?php echo htmlspecialchars($agregarMedico); ?>">Nuevo Médico</a> o <a href="<?php echo htmlspecialchars($listaMedico); ?>">Lista de Médicos</a>.</li>
        <?php endif; ?>
        <li><strong>Departamento y cargo</strong> no se crean aquí: adminístrelos en <em>Departamentos</em> y <em>Posiciones de trabajo</em> antes del alta.</li>
    </ul>
</div>
