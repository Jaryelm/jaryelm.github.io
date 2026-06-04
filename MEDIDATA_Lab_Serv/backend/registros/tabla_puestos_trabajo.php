<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo json_encode(['error' => 'Base de datos RRHH no disponible']);
    exit;
}

try {
    $search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

    $mainDb = dbname;
    $sql = "SELECT pd.id, p.name, d.name as department, pd.immediate_boss, pd.objective,
                   pd.schedule, pd.deleted
            FROM positions_details pd
            INNER JOIN $mainDb.positions p ON pd.id_positions = p.id
            LEFT JOIN departaments d ON pd.id_departament = d.id
            WHERE pd.deleted IN (0, 1)";

    $params = [];
    if ($search !== '') {
        $sql .= " AND (
            p.name LIKE :search1 OR d.name LIKE :search2 OR
            pd.immediate_boss LIKE :search3 OR pd.objective LIKE :search4
        )";
        $like = '%' . $search . '%';
        $params[':search1'] = $like;
        $params[':search2'] = $like;
        $params[':search3'] = $like;
        $params[':search4'] = $like;
    }

    $sql .= ' ORDER BY p.name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    error_log('tabla_puestos_trabajo: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar puestos de trabajo']);
}
