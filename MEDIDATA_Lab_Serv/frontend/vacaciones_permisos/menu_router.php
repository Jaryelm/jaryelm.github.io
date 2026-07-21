<?php
$roleMap = [
    'Administrador' => '../admin/menu.php',
    'IT' => '../it/menu.php',
    'Caja' => '../caja/menu.php',
    'Contabilidad' => '../contabilidad/menu.php',
    'Auxiliar Contable' => '../auxcontable/menu.php',
    'Facturación' => '../facturacion/menu.php',
    'Recursos_Humanos' => '../recursos_humanos/menu.php',
    'Mantenimiento' => '../mantenimiento/menu.php',
    'Médico' => '../medicos/menu.php', // Wait, the directory is often 'medicos'
    'Enfermero' => '../enfermeria/menu.php'
];

$rolActual = $_SESSION['rol'] ?? '';

// Fallback manual mapping if exact match is not in array
if (!isset($roleMap[$rolActual])) {
    // Si la clave tiene acentos o algo raro y no matchea
    $menuFile = '../admin/menu.php'; 
    if ($rolActual === 'Recursos_Humanos') $menuFile = '../recursos_humanos/menu.php';
} else {
    $menuFile = $roleMap[$rolActual];
}

// Ensure file exists, fallback to admin
if (!file_exists($menuFile)) {
    $menuFile = '../admin/menu.php';
}

include $menuFile;
?>
