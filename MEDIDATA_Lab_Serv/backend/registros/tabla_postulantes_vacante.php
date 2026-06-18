<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo json_encode(['error' => 'Base de datos RRHH no disponible', 'data' => []]);
    exit;
}

$idVacante = isset($_GET['id_vacante']) ? (int) $_GET['id_vacante'] : 0;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

try {
    $sql = "SELECT c.id, c.fullname, c.dni, c.phonenumber, c.email, c.status,
                   c.overall_score, c.created_at
            FROM candidates c
            WHERE c.deleted = 0";
    $params = [];

    if ($idVacante > 0) {
        $sql .= ' AND c.id_vacant_position = :id_vacante';
        $params[':id_vacante'] = $idVacante;
    }

    if ($search !== '') {
        $sql .= ' AND (c.fullname LIKE :search1 OR c.dni LIKE :search2 OR c.email LIKE :search3)';
        $like = '%' . $search . '%';
        $params[':search1'] = $like;
        $params[':search2'] = $like;
        $params[':search3'] = $like;
    }

    $sql .= ' ORDER BY c.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    error_log('tabla_postulantes_vacante: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar postulantes', 'data' => []]);
}
