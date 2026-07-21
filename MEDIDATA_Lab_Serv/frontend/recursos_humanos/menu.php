<?php
/**
 * Menú RRHH compartido desde recursos_humanos/, medicos/ y recursos/.
 * Las rutas relativas en HTML dependen de la URL actual, no del archivo incluido.
 */
if (!isset($MEDIDATA_RRHH_BASE)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_ends_with($scriptDir, '/medicos')) {
        $MEDIDATA_RRHH_BASE = '../recursos_humanos/';
        $MEDIDATA_MEDICOS_BASE = './';
        $MEDIDATA_RECURSOS_BASE = '../recursos/';
    } elseif (str_ends_with($scriptDir, '/recursos')) {
        $MEDIDATA_RRHH_BASE = '../recursos_humanos/';
        $MEDIDATA_MEDICOS_BASE = '../medicos/';
        $MEDIDATA_RECURSOS_BASE = './';
    } elseif (str_ends_with($scriptDir, '/vacaciones_permisos')) {
        $MEDIDATA_RRHH_BASE = '../recursos_humanos/';
        $MEDIDATA_MEDICOS_BASE = '../medicos/';
        $MEDIDATA_RECURSOS_BASE = '../recursos/';
    } else {
        $MEDIDATA_RRHH_BASE = './';
        $MEDIDATA_MEDICOS_BASE = '../medicos/';
        $MEDIDATA_RECURSOS_BASE = '../recursos/';
    }
}
if (!isset($MEDIDATA_MEDICOS_BASE)) {
    $MEDIDATA_MEDICOS_BASE = '../medicos/';
}
if (!isset($MEDIDATA_RECURSOS_BASE)) {
    $MEDIDATA_RECURSOS_BASE = '../recursos/';
}
?>
<!-- SIDEBAR -->
<section id="sidebar">
    <a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>escritorio.php" class="brand"><i class='bx bxs-home home'></i>MEDIDATA</a>
    <ul class="side-menu">
        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>escritorio.php" class="active"><i class='bx bxs-dashboard icon'></i> Panel</a></li>
        <li class="divider" data-text="panel">Panel</li>
        <li>
            <a href="#"><i class='bx bxs-user-detail icon'></i> RECURSOS HUMANOS<i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li>
                    <a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>lista_colaboradores_usr.php">LISTA COLABORADORES</a>
                </li>
                <li>
                    <a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>lista_colaboradores_medicos_usr.php">LISTA DE MÉDICOS</a>
                </li>
                <li>
                    <a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>positions_usr.php">LISTA POSICIONES</a>
                </li>
                <li>
                    <a href="<?php echo htmlspecialchars($MEDIDATA_RECURSOS_BASE); ?>departamentos_usr.php">DEPARTAMENTOS</a>
                </li>
                <li>
                    <a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>niveles_salariales_usr.php">NIVELES SALARIALES</a>
                </li>
                <li>
                    <a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>horarios_usr.php">HORARIOS LABORALES</a>
                </li>
                <li>
                    <a href="#" class="new-submenu-link">PROCESO DE RECLUTAMIENTO</a>
                    <ul class="new-side-dropdown">
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>puestos_trabajo_usr.php">PUESTOS DE TRABAJO</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>vacantes_trabajo_usr.php">VACANTES DE TRABAJO</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RECURSOS_BASE); ?>reclutamiento_usr.php">RECLUTAMIENTO</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>reclutamiento_usr.php">SOLICITUDES DE EMPLEO</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>postulantes_usr.php">POSTULANTES</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>entrevista_usr.php">ENTREVISTAS</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>pruebas_psicometricas_usr.php">PRUEBAS PSICOMETRICAS</a></li>
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>requisitos_contratacion_usr.php">REQUISITOS DE CONTRATACION</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#" class="new-submenu-link">PERSONAL</a>
                    <ul class="new-side-dropdown">
                        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RECURSOS_BASE); ?>relojbio_usr.php">RELOJ BIOMÉTRICO</a></li>
                    </ul>
                </li>
            </ul>
        </li>
        <li>
            <a href="#"><i class='bx bxs-calendar-event icon'></i> <span>VACACIONES Y PERMISOS</span><i class='bx bx-chevron-right icon-right'></i></a>
            <ul class="side-dropdown">
                <li><a href="../vacaciones_permisos/mis_vacaciones.php"><span>MIS SOLICITUDES</span></a></li>
                <li><a href="../vacaciones_permisos/aprobaciones.php"><span>MIS APROBACIONES</span></a></li>
                <?php if(isset($_SESSION['rol']) && ($_SESSION['rol'] === 'Administrador' || $_SESSION['rol'] === 'Recursos_Humanos')): ?>
                <li>
                    <a href="#" class="new-submenu-link"><span>ADMINISTRACIÓN RRHH</span></a>
                    <ul class="new-side-dropdown">
                        <li><a href="../vacaciones_permisos/dashboard.php"><span>DASHBOARD</span></a></li>
                        <li><a href="../vacaciones_permisos/gestionar_solicitudes.php"><span>GESTIÓN DE SOLICITUDES</span></a></li>
                        <li><a href="../vacaciones_permisos/kardex_vacaciones.php"><span>KARDEX DE VACACIONES</span></a></li>
                        <li><a href="../vacaciones_permisos/tipos_ausencia.php"><span>TIPOS DE AUSENCIA</span></a></li>
                        <li><a href="../vacaciones_permisos/flujos_aprobacion.php"><span>FLUJOS DE APROBACIÓN</span></a></li>
                        <li><a href="../vacaciones_permisos/politicas_vacaciones.php"><span>POLÍTICAS DE VACACIONES</span></a></li>
                        <li><a href="../vacaciones_permisos/calendario_feriados.php"><span>CALENDARIO DE FERIADOS</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </li>
        <li><a href="<?php echo htmlspecialchars($MEDIDATA_RRHH_BASE); ?>mostrar.php"><i class='bx bxs-info-circle icon'></i>ACERCA DE MEDIDATA</a></li>
    </ul>
</section>
<!-- SIDEBAR -->

