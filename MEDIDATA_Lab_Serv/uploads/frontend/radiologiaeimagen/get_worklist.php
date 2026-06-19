<?php
declare(strict_types=1);

date_default_timezone_set('America/Tegucigalpa');

require_once __DIR__ . '/../../backend/bd/Conexion.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Inicia sesión para continuar.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @param array<string, mixed> $filters
 * @param list<mixed> $params
 */
function medidata_worklist_sql_where(int $technicianId, array $filters, array &$params): string
{
    $where = ' WHERE (w.technician_id = ? OR w.technician_id IS NULL)';
    $params = [$technicianId];

    if (!empty($filters['modality'])) {
        $where .= ' AND w.modality = ?';
        $params[] = (string) $filters['modality'];
    }

    if (!empty($filters['priority'])) {
        $where .= ' AND w.priority = ?';
        $params[] = (string) $filters['priority'];
    }

    if (!empty($filters['status'])) {
        $where .= ' AND w.status = ?';
        $params[] = (string) $filters['status'];
    }

    if (!empty($filters['date'])) {
        $where .= ' AND DATE(w.study_date) = ?';
        $params[] = (string) $filters['date'];
    }

    if (!empty($filters['search'])) {
        $where .= ' AND (w.patient_name LIKE ? OR w.patient_id LIKE ? OR w.study_description LIKE ?)';
        $term = '%' . (string) $filters['search'] . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    return $where;
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $technicianId = (int) $_SESSION['id'];
    $page = max(1, (int) ($payload['page'] ?? 1));
    $limit = (int) ($payload['limit'] ?? 10);
    $limit = max(5, min(50, $limit));
    $offset = ($page - 1) * $limit;

    $filters = [
        'modality' => trim((string) ($payload['modality'] ?? '')),
        'priority' => trim((string) ($payload['priority'] ?? '')),
        'status'   => trim((string) ($payload['status'] ?? '')),
        'date'     => trim((string) ($payload['date'] ?? '')),
        'search'   => trim((string) ($payload['search'] ?? '')),
    ];

    $params = [];
    $where = medidata_worklist_sql_where($technicianId, $filters, $params);

    $countStmt = $connect->prepare('SELECT COUNT(*) FROM worklist w' . $where);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = "
        SELECT
            w.id,
            w.study_id,
            w.series_id,
            w.patient_id,
            COALESCE(w.patient_name, 'N/A') AS patient_name,
            w.study_date,
            w.modality,
            COALESCE(w.study_description, 'Sin descripción') AS description,
            w.status,
            w.priority,
            w.technician_id,
            w.radiologist_id,
            w.radiologist_name,
            w.last_sync,
            w.last_update,
            CASE WHEN EXISTS (
                SELECT 1 FROM quality_control qc WHERE qc.study_id = w.id LIMIT 1
            ) THEN 1 ELSE 0 END AS has_quality_control
        FROM worklist w
        {$where}
        ORDER BY
            FIELD(w.priority, 'emergency', 'urgent', 'routine'),
            w.study_date DESC,
            w.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['has_quality_control'] = (int) ($row['has_quality_control'] ?? 0);
    }
    unset($row);

    $totalPages = $total > 0 ? (int) ceil($total / $limit) : 1;

    echo json_encode([
        'success'     => true,
        'data'        => $rows,
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'totalPages'  => $totalPages,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_worklist.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Error al cargar la lista de trabajo.',
    ], JSON_UNESCAPED_UNICODE);
}
