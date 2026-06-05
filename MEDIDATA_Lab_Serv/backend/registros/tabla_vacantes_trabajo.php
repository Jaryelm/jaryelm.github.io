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

    $sql = "SELECT vp.id, vp.priority, vp.available_slots, vp.reason,
                   vp.init_date, vp.end_date, vp.deleted,
                   COALESCE(p.name, 'Sin Título') AS position_name,
                   (SELECT COUNT(*) FROM candidates c
                    WHERE c.id_vacant_position = vp.id AND c.deleted = 0) AS total_applicants
            FROM vacant_positions vp
            LEFT JOIN positions_details pd ON vp.id_position = pd.id
            LEFT JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id
            WHERE vp.deleted IN (0, 1)";

    $params = [];
    if ($search !== '') {
        $sql .= " AND (
            vp.reason LIKE :search1 OR
            vp.requesting_department LIKE :search2 OR p.name LIKE :search3
        )";
        $like = '%' . $search . '%';
        $params[':search1'] = $like;
        $params[':search2'] = $like;
        $params[':search3'] = $like;
    }

    $sql .= ' ORDER BY FIELD(vp.priority, \'Urgente\', \'Alta\', \'Media\', \'Baja\'), vp.init_date DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    error_log('tabla_vacantes_trabajo: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar vacantes de trabajo: ' . $e->getMessage()]);
}
