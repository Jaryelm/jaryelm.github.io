<?php
declare(strict_types=1);

date_default_timezone_set('America/Tegucigalpa');

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/mhpacs_filters_lib.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * @param list<mixed> $params
 */
function mhpacs_studies_status_sql(string $statusFilter, array &$params): string
{
    if ($statusFilter === '') {
        return '';
    }

    if ($statusFilter === 'pending') {
        return " AND rr.status IN ('pending', 'draft')";
    }
    if ($statusFilter === 'draft') {
        return " AND rr.status = 'draft'";
    }
    if ($statusFilter === 'pending_transcription' || $statusFilter === 'in_transcription') {
        return " AND rr.status = 'pending_transcription'";
    }
    if ($statusFilter === 'final') {
        return " AND rr.status = 'final'";
    }
    if ($statusFilter === 'completed' || $statusFilter === 'final') {
        return " AND (
            rr.status IN ('final', 'transcribed', 'reviewed')
            OR EXISTS (
                SELECT 1 FROM report_transcriptions rtx
                WHERE rtx.report_id = rr.id AND rtx.status = 'completed'
            )
        )";
    }

    return ' AND rr.status = ?';
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

    $activeStatuses = ['pending', 'draft', 'pending_transcription', 'final', 'transcribed', 'reviewed'];
    $placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));

    $params = array_merge([], $activeStatuses);
    $where = " WHERE rr.status IN ($placeholders)";

    if (mhpacs_is_radiologo_role($userRol)) {
        $where = ' WHERE rr.user_id = ? AND rr.status IN (' . $placeholders . ')';
        $params = array_merge([$userId], $activeStatuses);
    }

    if (!empty($filters['modality'])) {
        $where .= ' AND w.modality = ?';
        $params[] = $filters['modality'];
    }

    if (!empty($filters['priority'])) {
        $where .= ' AND w.priority = ?';
        $params[] = $filters['priority'];
    }

    $where .= mhpacs_sql_date_range('w.study_date', $filters['date_from'], $filters['date_to'], $params);
    $where .= mhpacs_sql_search(
        ['w.patient_name', 'w.patient_id', 'w.study_description', 'w.study_id', 'rr.radiologist_name'],
        $filters['search'],
        $params
    );

    $statusFilter = $filters['status'];
    if ($statusFilter !== '') {
        if ($statusFilter === 'completed') {
            $where .= " AND (
                rr.status IN ('final', 'transcribed', 'reviewed')
                OR EXISTS (
                    SELECT 1 FROM report_transcriptions rtx
                    WHERE rtx.report_id = rr.id AND rtx.status = 'completed'
                )
            )";
        } elseif ($statusFilter === 'pending') {
            $where .= " AND rr.status IN ('pending', 'draft')";
        } elseif ($statusFilter === 'draft') {
            $where .= " AND rr.status = 'draft'";
        } elseif ($statusFilter === 'pending_transcription') {
            $where .= " AND rr.status = 'pending_transcription'";
        } elseif ($statusFilter === 'final') {
            $where .= " AND rr.status = 'final'";
        } else {
            $where .= ' AND rr.status = ?';
            $params[] = $statusFilter;
        }
    }

    $fromSql = "
        FROM radiology_reports rr
        INNER JOIN worklist w ON rr.study_id = w.study_id
        {$where}
    ";

    $countStmt = $connect->prepare('SELECT COUNT(DISTINCT rr.id)' . $fromSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $sql = "
        SELECT
            rr.id,
            rr.patient_id,
            w.patient_name,
            rr.study_id,
            rr.radiologist_id,
            rr.radiologist_name,
            rr.user_id,
            rr.status,
            rr.created_at,
            rr.updated_at,
            w.study_description,
            w.modality,
            w.study_date,
            w.series_id,
            w.priority,
            (
                SELECT rt.status
                FROM report_transcriptions rt
                WHERE rt.report_id = rr.id
                ORDER BY rt.id DESC
                LIMIT 1
            ) AS transcription_status,
            (
                SELECT rt.completed_at
                FROM report_transcriptions rt
                WHERE rt.report_id = rr.id
                ORDER BY rt.id DESC
                LIMIT 1
            ) AS transcription_completed_at
        {$fromSql}
        ORDER BY w.study_date DESC, rr.updated_at DESC, rr.id DESC
        LIMIT {$limit} OFFSET {$offset}
    ";

    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $studies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    mhpacs_json_paginated($studies, $total, $page, $limit);
} catch (Throwable $e) {
    error_log('get_completed_studies.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al cargar estudios.', 'data' => []], JSON_UNESCAPED_UNICODE);
}
