<?php
require_once('../../backend/bd/Conexion.php');
require_once __DIR__ . '/transcription_queue_lib.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Acceso no autorizado. Inicia sesión para continuar.']);
    exit;
}

try {
    medidata_sync_transcription_queue($connect);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $today = date('Y-m-d');

    $query = "
        SELECT
            w.*,
            COALESCE(w.patient_name, 'N/A') AS patient_name,
            COALESCE(w.study_description, 'Sin descripción') AS description,
            w.study_id AS study_id,
            CASE WHEN qc.study_id IS NOT NULL THEN 1 ELSE 0 END AS has_quality_control,
            r.radiologist_name,
            r.status AS report_status,
            r.id AS report_id,
            rt.status AS transcription_status,
            rt.completed_at
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
        LEFT JOIN report_transcriptions rt ON r.id = rt.report_id
        WHERE r.status IN ('pending_transcription', 'final', 'transcribed', 'reviewed')
           OR rt.id IS NOT NULL
    ";
    $params = [];

    if (!empty($data['modality'])) {
        $query .= ' AND w.modality = ?';
        $params[] = $data['modality'];
    }
    if (!empty($data['priority'])) {
        $query .= ' AND w.priority = ?';
        $params[] = $data['priority'];
    }

    $statusFilter = trim((string) ($data['status'] ?? ''));
    if ($statusFilter === 'pending' || $statusFilter === 'pending_transcription') {
        $query .= " AND (rt.id IS NULL OR rt.status IN ('pending', 'in_progress'))";
    } elseif ($statusFilter === 'completed') {
        $query .= " AND rt.status = 'completed'";
    } else {
        $query .= " AND (rt.id IS NULL OR rt.status IN ('pending', 'in_progress', 'completed'))";
    }

    if (!empty($data['date'])) {
        $query .= ' AND DATE(w.study_date) = ?';
        $params[] = $data['date'];
    }
    if (!empty($data['search'])) {
        $query .= ' AND (w.patient_name LIKE ? OR w.patient_id LIKE ? OR w.study_description LIKE ?)';
        $searchTerm = '%' . $data['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $query .= "
        ORDER BY
            FIELD(w.priority, 'emergency', 'urgent', 'routine'),
            w.study_date DESC,
            r.updated_at DESC
    ";

    $stmt = $connect->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as &$row) {
        $row['id'] = $row['report_id'] ?? $row['id'] ?? null;
        $row['series_id'] = $row['series_id'] ?? null;
        $row['patient_id'] = $row['patient_id'] ?? 'N/A';
        $row['modality'] = $row['modality'] ?? 'N/A';
        $row['description'] = $row['description'] ?? 'Sin descripción';
        $row['priority'] = $row['priority'] ?? 'routine';

        if (($row['transcription_status'] ?? '') === 'completed') {
            $row['status'] = 'completed';
        } elseif (empty($row['transcription_status']) || in_array($row['transcription_status'], ['pending', 'in_progress'], true)) {
            $row['status'] = 'pending_transcription';
        } else {
            $row['status'] = $row['transcription_status'] ?? 'pending_transcription';
        }

        $row['has_quality_control'] = $row['has_quality_control'] ?? 0;
    }
    unset($row);

    echo json_encode($results);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar la lista de trabajo: ' . $e->getMessage()]);
}
