<?php
/**
 * Bloque de menú compartido "Vacaciones y Permisos" para los menús de todos los roles
 * operativos (colaboradores). Los enlaces de jefe solo se muestran si el usuario encabeza
 * al menos un departamento. Incluir con:
 *   <?php include __DIR__ . '/../vacaciones_permisos/_menu_vacaciones.php'; ?>
 * Las rutas de los <a> son relativas a la página (frontend/<rol>/*.php) → resuelven a
 * frontend/vacaciones_permisos/*.php desde cualquier rol.
 */
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/jefe_lib.php';

$__vpIsJefe = (isset($connect) && $connect)
    ? medidata_jefe_es_jefe($connect, (int) ($_SESSION['id'] ?? 0))
    : false;
?>
<li>
    <a href="#"><i class='bx bx-calendar-check icon'></i><span>VACACIONES Y PERMISOS</span><i class='bx bx-chevron-right icon-right'></i></a>
    <ul class="side-dropdown">
        <li><a href="../vacaciones_permisos/mis_vacaciones.php"><span>MIS SOLICITUDES</span></a></li>
        <li><a href="../vacaciones_permisos/incapacidades.php"><span>MIS INCAPACIDADES</span></a></li>
        <?php if ($__vpIsJefe): ?>
        <li><a href="../vacaciones_permisos/aprobaciones.php"><span>APROBACIONES DE MI EQUIPO</span></a></li>
        <li><a href="../vacaciones_permisos/calendario_departamento.php"><span>CALENDARIO DE MI DEPARTAMENTO</span></a></li>
        <?php endif; ?>
    </ul>
</li>
