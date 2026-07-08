<?php
require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

try {
    $user_id = (int) ($_SESSION['id'] ?? 0);
    $user_rol = $_SESSION['rol'] ?? '';

    if ($user_rol !== 'Radiologo' || $user_id <= 0) {
        echo json_encode([]);
        exit;
    }

    $activeStatuses = ['pending', 'draft', 'pending_transcription', 'final', 'transcribed', 'reviewed'];
    $placeholders = implode(',', array_fill(0, count($activeStatuses), '?'));

    $query = "
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
        FROM radiology_reports rr
        INNER JOIN worklist w ON rr.study_id = w.study_id
        WHERE rr.user_id = ?
          AND rr.status IN ($placeholders)
    ";
    $params = array_merge([$user_id], $activeStatuses);

    if (!empty($data['modality'])) {
        $query .= ' AND w.modality = ?';
        $params[] = $data['modality'];
    }
    if (!empty($data['date'])) {
        $query .= ' AND DATE(rr.updated_at) = ?';
        $params[] = $data['date'];
    }

    $statusFilter = trim((string) ($data['status'] ?? ''));
    if ($statusFilter === 'pending') {
        $query .= " AND rr.status IN ('pending', 'draft')";
    } elseif ($statusFilter === 'pending_transcription' || $statusFilter === 'in_transcription') {
        $query .= " AND rr.status = 'pending_transcription'";
    } elseif ($statusFilter === 'completed' || $statusFilter === 'final') {
        $query .= " AND (
            rr.status IN ('final', 'transcribed', 'reviewed')
            OR EXISTS (
                SELECT 1 FROM report_transcriptions rtx
                WHERE rtx.report_id = rr.id AND rtx.status = 'completed'
            )
        )";
    }

    $query .= ' ORDER BY rr.updated_at DESC, rr.id DESC';

    $stmt = $connect->prepare($query);
    $stmt->execute($params);
    $studies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($studies);
} catch (Throwable $e) {
    error_log('get_completed_studies.php: ' . $e->getMessage());
    echo json_encode([]);
}
