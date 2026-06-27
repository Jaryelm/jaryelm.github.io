<?php
/**
 * Botones de navegaciÃ³n del mÃ³dulo MÃ©dicos (solo registro y listado).
 * Definir $medicos_nav_rrhh = true en pÃ¡ginas *_usr.php (RRHH).
 */
$medicos_nav_rrhh = !empty($medicos_nav_rrhh);
if ($medicos_nav_rrhh) {
    $urlNuevo = '../recursos_humanos/agregar_colaborador.php';
    $urlLista = 'mostrar_usr.php';
    $urlListaEx = 'mostrar_ex_usr.php';
} else {
    $urlNuevo = '../recursos_humanos/agregar_colaborador.php';
    $urlLista = 'mostrar.php';
    $urlListaEx = 'mostrar_ex.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="rrhh-tab-nav" style="margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px;">
    <a href="<?php echo htmlspecialchars($urlLista, ENT_QUOTES, 'UTF-8'); ?>" class="button tab-button <?php echo ($current_page == 'mostrar.php' || $current_page == 'mostrar_usr.php') ? 'active' : ''; ?>">Personal Activo</a>
    <a href="<?php echo htmlspecialchars($urlListaEx, ENT_QUOTES, 'UTF-8'); ?>" class="button tab-button <?php echo ($current_page == 'mostrar_ex.php' || $current_page == 'mostrar_ex_usr.php') ? 'active' : ''; ?>">Ex Colaboradores</a>
    <a href="<?php echo htmlspecialchars($urlNuevo, ENT_QUOTES, 'UTF-8'); ?>" class="button tab-button" style="background-color: #28a745; color: white;">Agregar Colaborador</a>
</div>

