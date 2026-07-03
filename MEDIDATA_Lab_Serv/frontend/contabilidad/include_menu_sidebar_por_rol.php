<?php
/**
 * Sidebar: contadores usan menú contabilidad; Auxiliar Contable el de auxcontable
 * (mismas rutas de reportes en /contabilidad/*_user.php).
 */
if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'Auxiliar Contable') {
    require_once __DIR__ . '/../auxcontable/menu.php';
} else {
    require_once __DIR__ . '/menu.php';
}
