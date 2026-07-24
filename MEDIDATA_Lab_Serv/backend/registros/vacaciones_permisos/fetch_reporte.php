<?php
/**
 * Genera un reporte de Vacaciones y Permisos (JSON para DataTables) según filtros.
 * Solo RRHH / Administrador.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/reporte_vacaciones_lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión a BD principal.');
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) throw new Exception('Sin conexión a BD de ausencias.');

    $filtros = [
        'tipo_reporte'    => $_GET['tipo_reporte']    ?? 'solicitudes',
        'user_id'         => $_GET['user_id']         ?? '',
        'id_departamento' => $_GET['id_departamento'] ?? '',
        'estado'          => $_GET['estado']          ?? '',
        'type_id'         => $_GET['type_id']         ?? '',
        'desde'           => $_GET['desde']           ?? '',
        'hasta'           => $_GET['hasta']           ?? '',
    ];

    $rep = medidata_reporte_generar($connect, $connect_hr_leaves, $filtros);
    echo json_encode(['titulo' => $rep['titulo'], 'columns' => $rep['columns'], 'data' => $rep['rows']]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
