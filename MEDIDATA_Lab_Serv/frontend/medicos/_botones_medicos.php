<?php
/**
 * Botones de navegación del módulo Médicos (solo registro y listado).
 * Definir $medicos_nav_rrhh = true en páginas *_usr.php (RRHH).
 * Definir $medicos_nav_unified = true cuando se entra desde lista_colaboradores_medicos (admin).
 */
$medicos_nav_rrhh = !empty($medicos_nav_rrhh);
$medicos_nav_unified = !empty($medicos_nav_unified);
$medicos_nav_medicos_tab = !empty($medicos_nav_medicos_tab);

if ($medicos_nav_rrhh) {
    $urlNuevo = 'nuevo_usr.php';
    $urlLista = $medicos_nav_medicos_tab
        ? '../recursos_humanos/lista_colaboradores_medicos_usr.php'
        : '../recursos_humanos/lista_colaboradores_usr.php';
    $urlListaEx = '../recursos_humanos/lista_excolaboradores_usr.php';
} elseif ($medicos_nav_unified) {
    $urlNuevo = '../recursos_humanos/agregar_colaborador.php?contexto=medicos';
    $urlLista = '../recursos_humanos/lista_colaboradores_medicos.php';
    $urlListaEx = '../recursos_humanos/lista_excolaboradores.php';
} else {
    $urlNuevo = '../medicos/nuevo.php';
    $urlLista = '../medicos/mostrar.php';
    $urlListaEx = '../medicos/mostrar_ex.php';
}
?>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($urlLista, ENT_QUOTES, 'UTF-8'); ?>')">Personal Activo</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($urlListaEx, ENT_QUOTES, 'UTF-8'); ?>')">Ex Médicos</button>
        <button class="button" onclick="cambiarColor(this, '<?php echo htmlspecialchars($urlNuevo, ENT_QUOTES, 'UTF-8'); ?>')">Registrar Médicos</button>
