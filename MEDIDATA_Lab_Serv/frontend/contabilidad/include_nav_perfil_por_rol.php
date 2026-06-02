<?php
if (!empty($_SESSION['rol']) && $_SESSION['rol'] === 'Auxiliar Contable') {
    require_once __DIR__ . '/../auxcontable/perfil.php';
} else {
    require_once __DIR__ . '/perfil.php';
}
