<?php
/**
 * Botones de navegación del módulo Médicos (solo registro y listado).
 * Definir $medicos_nav_rrhh = true en páginas *_usr.php (RRHH).
 */
$medicos_nav_rrhh = !empty($medicos_nav_rrhh);
if ($medicos_nav_rrhh) {
    $urlNuevo = 'nuevo_usr.php';
    $urlLista = 'mostrar_usr.php';
    $urlListaEx = 'mostrar_ex_usr.php';
} else {
    $urlNuevo = '../medicos/nuevo.php';
    $urlLista = '../medicos/mostrar.php';
    $urlListaEx = '../medicos/mostrar_ex.php';
}
?>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($urlLista, ENT_QUOTES, 'UTF-8'); ?>')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($urlListaEx, ENT_QUOTES, 'UTF-8'); ?>')">Ex Médicos</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($urlNuevo, ENT_QUOTES, 'UTF-8'); ?>')">Registrar Médicos</button>
