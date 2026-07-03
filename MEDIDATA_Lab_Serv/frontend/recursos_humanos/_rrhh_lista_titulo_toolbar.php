<?php
/**
 * Barra de título de listas RRHH con botón agregar opcional.
 * Variables: $rrhh_lista_titulo, $rrhh_lista_add_url (opcional), $rrhh_lista_add_label (opcional)
 */
?>
<div class="table-title rrhh-table-title-bar">
    <h1><?php echo htmlspecialchars($rrhh_lista_titulo ?? ''); ?></h1>
    <?php if (!empty($rrhh_lista_add_url)): ?>
    <a href="<?php echo htmlspecialchars($rrhh_lista_add_url); ?>"
       class="fa fa-plus tooltip rrhh-btn-agregar"
       title="<?php echo htmlspecialchars($rrhh_lista_add_label ?? 'Agregar'); ?>"></a>
    <?php endif; ?>
</div>
