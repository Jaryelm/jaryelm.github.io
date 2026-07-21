<?php
declare(strict_types=1);

date_default_timezone_set('America/Tegucigalpa');

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/mhpacs_filters_lib.php';

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
function medidata_worklist_sql_where(string $userRol, int $userId, array $filters, array &$params): string
{
    $where = ' WHERE 1=1';

    $radiologistFilter = (string) ($filters['radiologist_id'] ?? '');
    // Si filtra por radiólogo, mostrar sus asignados (no limitar por técnico).
    // Sin ese filtro, el técnico solo ve los suyos o sin técnico.
    if (mhpacs_is_tecnico_role($userRol) && ($radiologistFilter === '' || $radiologistFilter === '_unassigned')) {
        $where .= ' AND (w.technician_id = ? OR w.technician_id IS NULL)';
        $params[] = $userId;
    }

    if (!empty($filters['modality'])) {
        $where .= ' AND w.modality = ?';
        $params[] = (string) $filters['modality'];
    }

    if (!empty($filters['priority'])) {
        $where .= ' AND w.priority = ?';
        $params[] = (string) $filters['priority'];
    }

    $where .= mhpacs_worklist_radiologist_sql($radiologistFilter, $params);

    $where .= mhpacs_sql_date_range('w.study_date', $filters['date_from'], $filters['date_to'], $params);
    $where .= mhpacs_sql_search(
        ['w.patient_name', 'w.patient_id', 'w.study_description', 'w.study_id'],
        $filters['search'],
        $params
    );

    return $where;
}

try {
    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $userId = (int) $_SESSION['id'];
    $userRol = (string) ($_SESSION['rol'] ?? '');
    $filters = mhpacs_filters_parse_payload($payload);
    $page = $filters['page'];
    $limit = $filters['limit'];
    $offset = $filters['offset'];

    $params = [];
    $where = medidata_worklist_sql_where($userRol, $userId, $filters, $params);

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

    mhpacs_json_paginated($rows, $total, $page, $limit);
} catch (Throwable $e) {
    error_log('get_worklist.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Error al cargar la lista de trabajo.',
    ], JSON_UNESCAPED_UNICODE);
}
