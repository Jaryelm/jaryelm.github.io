<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_json_require();

try {
    $sql = "SELECT id, level_name, position_category, min_salary, max_salary, deleted 
            FROM salary_levels 
            ORDER BY level_name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    error_log('tabla_niveles_salariales: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar niveles salariales', 'message' => $e->getMessage()]);
}
