<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

$study_id = $_GET['study_id'] ?? null;
$report_id = $_GET['report_id'] ?? null;

if (!$study_id && !$report_id) {
    echo json_encode(['error' => 'ID de estudio o informe no proporcionado']);
    exit;
}

try {
    // 1. Datos del worklist (incluye técnico y radiólogo asignados)
    if ($study_id) {
        $stmt = $connect->prepare("SELECT w.*, t.name AS technician_name, r.name AS radiologist_name
            FROM worklist w
            LEFT JOIN users t ON w.technician_id = t.id
            LEFT JOIN users r ON w.radiologist_id = r.id
            WHERE w.study_id = ?");
        $stmt->execute([$study_id]);
        $worklist = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $worklist = null;
    }

    // 2. Informe radiológico (con radiólogo)
    if ($report_id) {
        $stmt = $connect->prepare("SELECT rr.*, u.name AS radiologist_name FROM radiology_reports rr LEFT JOIN users u ON rr.radiologist_id = u.id WHERE rr.id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif ($study_id) {
        $stmt = $connect->prepare("SELECT rr.*, u.name AS radiologist_name FROM radiology_reports rr LEFT JOIN users u ON rr.radiologist_id = u.id WHERE rr.study_id = ? ORDER BY rr.updated_at DESC LIMIT 1");
        $stmt->execute([$study_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $report = null;
    }

    // 3. Todas las transcripciones (con transcriptor)
    $transcriptions = [];
    if ($report) {
        $stmt = $connect->prepare("SELECT rt.*, rt.transcriber_name FROM report_transcriptions rt WHERE rt.report_id = ? ORDER BY rt.created_at ASC");
        $stmt->execute([$report['id']]);
        $transcriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Todos los controles de calidad (con usuario)
    $quality_controls = [];
    if ($study_id) {
        $stmt = $connect->prepare("SELECT qc.*, u.name AS user_name FROM quality_control qc LEFT JOIN users u ON qc.user_id = u.id WHERE qc.study_id = ? ORDER BY qc.created_at ASC");
        $stmt->execute([$study_id]);
        $quality_controls = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Todas las dosis de radiación (con usuario)
    $doses = [];
    if ($study_id) {
        $stmt = $connect->prepare("SELECT d.*, u.name AS user_name FROM radiation_dose d LEFT JOIN users u ON d.technician_id = u.id WHERE d.study_id = ? ORDER BY d.recorded_at ASC");
        $stmt->execute([$study_id]);
        $doses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. Todas las incidencias (con usuario)
    $incidents = [];
    if ($study_id) {
        $stmt = $connect->prepare("SELECT i.*, u.name AS user_name FROM incidents i LEFT JOIN users u ON i.technician_id = u.id WHERE i.study_id = ? ORDER BY i.created_at ASC");
        $stmt->execute([$study_id]);
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 7. Todas las repeticiones (con usuario)
    $repeats = [];
    if ($study_id) {
        $stmt = $connect->prepare("SELECT r.*, u.name AS user_name FROM study_repeats r LEFT JOIN users u ON r.technician_id = u.id WHERE r.study_id = ? ORDER BY r.created_at ASC");
        $stmt->execute([$study_id]);
        $repeats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 8. Historial de estados (si existe la tabla worklist_status_history)
    $status_history = [];
    if ($study_id) {
        try {
            $stmt = $connect->prepare("SELECT h.*, u.name AS user_name FROM worklist_status_history h LEFT JOIN users u ON h.user_id = u.id WHERE h.study_id = ? ORDER BY h.changed_at ASC");
            $stmt->execute([$study_id]);
            $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $status_history = [];
        }
    }

    echo json_encode([
        'success' => true,
        'worklist' => $worklist,
        'report' => $report,
        'transcriptions' => $transcriptions,
        'quality_controls' => $quality_controls,
        'doses' => $doses,
        'incidents' => $incidents,
        'repeats' => $repeats,
        'status_history' => $status_history
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el detalle completo: ' . $e->getMessage()
    ]);
} 