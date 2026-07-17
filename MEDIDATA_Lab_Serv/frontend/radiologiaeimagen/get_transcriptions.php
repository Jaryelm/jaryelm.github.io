<?php
declare(strict_types=1);

date_default_timezone_set('America/Tegucigalpa');

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/transcription_queue_lib.php';
require_once __DIR__ . '/mhpacs_filters_lib.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado. Inicia sesión para continuar.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    medidata_sync_transcription_queue($connect);

    $payload = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        $payload = [];
    }

    $filters = mhpacs_filters_parse_payload($payload);
    $page = $filters['page'];
    $limit = $filters['limit'];
    $offset = $filters['offset'];

    $params = [];
    $where = "
        WHERE (
            r.status IN ('pending_transcription', 'final', 'transcribed', 'reviewed')
            OR rt.id IS NOT NULL
        )
    ";

    if (!empty($filters['modality'])) {
        $where .= ' AND w.modality = ?';
        $params[] = $filters['modality'];
    }
    if (!empty($filters['priority'])) {
        $where .= ' AND w.priority = ?';
        $params[] = $filters['priority'];
    }

    $statusFilter = $filters['status'];
    if ($statusFilter === 'pending_transcription') {
        $where .= " AND (rt.id IS NULL OR rt.status = 'pending')";
    } elseif ($statusFilter === 'in_progress') {
        $where .= " AND rt.status = 'in_progress'";
    } elseif ($statusFilter === 'completed') {
        $where .= " AND rt.status = 'completed'";
    } elseif ($statusFilter === 'needs_review') {
        $where .= " AND rt.status = 'needs_review'";
    }

    $where .= mhpacs_sql_date_range('w.study_date', $filters['date_from'], $filters['date_to'], $params);
    $where .= mhpacs_sql_search(
        ['w.patient_name', 'w.patient_id', 'w.study_description', 'w.study_id', 'r.radiologist_name'],
        $filters['search'],
        $params
    );

    $fromSql = "
        FROM worklist w
        INNER JOIN (
            SELECT r1.*
            FROM radiology_reports r1
            INNER JOIN (
                SELECT study_id, MAX(updated_at) AS max_updated
                FROM radiology_reports
                GROUP BY study_id
            ) latest ON latest.study_id = r1.study_id AND latest.max_updated = r1.updated_at
        ) r ON w.study_id = r.study_id
        LEFT JOIN quality_control qc ON w.id = qc.study_id
        LEFT JOIN (
            SELECT rt1.*
            FROM report_transcriptions rt1
            INNER JOIN (
                SELECT report_id, MAX(id) AS max_id
                FROM report_transcriptions
                GROUP BY report_id
            ) rt_latest ON rt_latest.max_id = rt1.id
        ) rt ON r.id = rt.report_id
        {$where}
    ";

    $countStmt = $connect->prepare('SELECT COUNT(DISTINCT r.id)' . $fromSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $query = "
        SELECT
            w.id AS worklist_id,
            w.study_id,
            w.series_id,
            w.patient_id,
            COALESCE(w.patient_name, 'N/A') AS patient_name,
            w.study_date,
            w.modality,
            w.priority,
            COALESCE(w.study_description, 'Sin descripción') AS description,
            CASE WHEN qc.study_id IS NOT NULL THEN 1 ELSE 0 END AS has_quality_control,
            r.radiologist_name,
            r.status AS report_status,
            r.id AS report_id,
            rt.status AS transcription_status,
            rt.completed_at
        {$fromSql}
        ORDER BY
            FIELD(w.priority, 'emergency', 'urgent', 'routine'),
            w.study_date DESC,
            r.updated_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $connect->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$row) {
        $row['id'] = $row['report_id'] ?? $row['worklist_id'] ?? null;
        $row['series_id'] = $row['series_id'] ?? null;
        $row['patient_id'] = $row['patient_id'] ?? 'N/A';
        $row['modality'] = $row['modality'] ?? 'N/A';
        $row['description'] = $row['description'] ?? 'Sin descripción';
        $row['priority'] = $row['priority'] ?? 'routine';

        $txStatus = (string) ($row['transcription_status'] ?? '');
        if ($txStatus === 'completed') {
            $row['status'] = 'completed';
        } elseif ($txStatus === 'in_progress') {
            $row['status'] = 'in_progress';
        } elseif ($txStatus === 'needs_review') {
            $row['status'] = 'needs_review';
        } elseif ($txStatus === '' || $txStatus === 'pending') {
            $row['status'] = 'pending_transcription';
        } else {
            $row['status'] = $txStatus;
        }

        $row['has_quality_control'] = (int) ($row['has_quality_control'] ?? 0);
    }
    unset($row);

    mhpacs_json_paginated($results, $total, $page, $limit);
} catch (Throwable $e) {
    error_log('get_transcriptions.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al cargar transcripciones.'], JSON_UNESCAPED_UNICODE);
}
