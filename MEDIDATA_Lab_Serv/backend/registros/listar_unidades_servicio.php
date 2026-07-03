<?php
/**
 * Lista unidades de servicio / departamentos para Partida Manual
 * Departamentos administrativos fijos
 */
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$unidades = [
    ['id' => 'MEDICASA', 'nombre' => 'MEDICASA'],
    ['id' => 'RRHH', 'nombre' => 'RRHH'],
    ['id' => 'Mantenimiento', 'nombre' => 'Mantenimiento'],
    ['id' => 'Gerencia', 'nombre' => 'Gerencia'],
    ['id' => 'Contabilidad', 'nombre' => 'Contabilidad'],
    ['id' => 'Mercadeo', 'nombre' => 'Mercadeo'],
    ['id' => 'Compras', 'nombre' => 'Compras'],
    ['id' => 'Servicios Generales', 'nombre' => 'Servicios Generales']
];

echo json_encode(['success' => true, 'unidades' => $unidades]);
