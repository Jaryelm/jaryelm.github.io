<?php if (isset($rrhh_disponible) && !$rrhh_disponible): ?>
<?php
$rrhhErr = function_exists('medidata_rrhh_last_error') ? medidata_rrhh_last_error() : null;
$rrhhErrLower = $rrhhErr ? strtolower($rrhhErr) : '';
?>
<div class="alert" style="margin-bottom: 15px;">
    <strong>Aviso:</strong> MEDIDATA no pudo conectarse a la base de datos de Recursos Humanos
    (<code>medic9ue_medi_rrhh_interviews</code>).
    <ul style="margin: 10px 0 0 18px; padding: 0;">
        <?php if (strpos($rrhhErrLower, 'access denied') !== false): ?>
        <li>El usuario MySQL de la aplicación (<code><?php echo htmlspecialchars(defined('dbuser') ? dbuser : ''); ?></code>) no tiene permisos sobre esa base de datos. En cPanel → MySQL® Databases → asigne el usuario a <code>medic9ue_medi_rrhh_interviews</code> con ALL PRIVILEGES.</li>
        <?php elseif (strpos($rrhhErrLower, 'unknown database') !== false): ?>
        <li>MySQL no encuentra la base de datos. Créela o verifique el nombre exacto en el servidor.</li>
        <?php else: ?>
        <li>Verifique que el usuario MySQL de PHP tenga acceso a <code>medic9ue_medi_rrhh_interviews</code> (no basta verla en phpMyAdmin con otra cuenta).</li>
        <?php endif; ?>
        <li>Ejecute también el script <code>backend/scripts/DDL-rrhh-interviews-views.sql</code> (vistas <code>puestos_trabajo</code>, <code>vacantes_trabajo</code>, <code>postulantes</code>).</li>
    </ul>
    <?php if ($rrhhErr && (isset($_SESSION['rol']) && in_array($_SESSION['rol'], ['Administrador', 'Admin', 'Recursos_Humanos'], true))): ?>
    <p style="margin-top: 10px; font-size: 0.85rem; color: #666;"><strong>Detalle técnico:</strong> <?php echo htmlspecialchars($rrhhErr); ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>
