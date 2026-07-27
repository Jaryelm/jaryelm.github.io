<?php
/**
 * Opciones para los filtros del reporte: colaboradores, departamentos y tipos de ausencia.
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

    // Colaboradores (id_user + nombre) a partir del mapa de empleados.
    $empMap = medidata_reporte_empleados_map($connect);
    $colaboradores = [];
    foreach ($empMap as $uid => $emp) {
        if ($emp['nombre'] === '') continue;
        $colaboradores[] = ['id_user' => $uid, 'nombre' => $emp['nombre']];
    }
    usort($colaboradores, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

    // Departamentos.
    $departamentos = [];
    foreach (medidata_reporte_departamentos_map($connect) as $id => $name) {
        $departamentos[] = ['id' => $id, 'name' => $name];
    }
    usort($departamentos, fn($a, $b) => strcmp($a['name'], $b['name']));

    // Tipos de ausencia (para el filtro por tipo de permiso).
    $tipos = [];
    if (isset($connect) && $connect) {
        $tipos = $connect->query(
            "SELECT type_id, name, category FROM hr_absence_types WHERE status = 1 ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'colaboradores' => $colaboradores,
        'departamentos' => $departamentos,
        'tipos'         => $tipos,
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
