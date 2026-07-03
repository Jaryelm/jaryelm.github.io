<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Base de datos RRHH no disponible']);
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

try {
    $sql = "SELECT pd.id, d.name as department_name, pd.immediate_boss
            FROM positions_details pd
            LEFT JOIN departaments d ON pd.id_departament = d.id
            WHERE pd.id = ? AND pd.deleted = 0";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Puesto no encontrado']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
