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
    if ($report_id) {
        // Si tenemos report_id, buscamos directamente en radiology_reports
        $stmt = $connect->prepare("
            SELECT 
                r.*,
                w.patient_id,
                w.patient_name,
                w.study_description,
                w.modality
            FROM radiology_reports r
            LEFT JOIN worklist w ON r.study_id = w.study_id
            WHERE r.id = ?
        ");
        $stmt->execute([$report_id]);
    } else {
        // Si tenemos study_id, buscamos el último informe
        $stmt = $connect->prepare("
            SELECT 
                r.*,
                w.patient_id,
                w.patient_name,
                w.study_description,
                w.modality
            FROM radiology_reports r
            LEFT JOIN worklist w ON r.study_id = w.study_id
            WHERE r.study_id = ?
            ORDER BY r.updated_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$study_id]);
    }

    $report = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($report) {
        // Buscar la transcripción asociada a este informe
        $stmt2 = $connect->prepare("SELECT * FROM report_transcriptions WHERE report_id = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt2->execute([$report['id']]);
        $transcription = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'report' => [
                'id' => $report['id'],
                'study_id' => $report['study_id'],
                'patient_id' => $report['patient_id'],
                'patient_name' => $report['patient_name'],
                'clinical_history' => $report['clinical_history'],
                'findings' => $report['findings'],
                'impression' => $report['impression'],
                'status' => $report['status'],
                'is_critical' => $report['is_critical'],
                'urgency_level' => $report['urgency_level'],
                'notified_to' => $report['notified_to'],
                'radiologist_name' => $report['radiologist_name'],
                'created_at' => $report['created_at'],
                'updated_at' => $report['updated_at'],
                'audio_url' => $report['audio_url']
            ],
            'transcription' => $transcription ? [
                'id' => $transcription['id'],
                'clinical_history' => $transcription['clinical_history'],
                'findings' => $transcription['findings'],
                'impression' => $transcription['impression'],
                'comments' => $transcription['comments'],
                'report_title' => $transcription['report_title'],
                'status' => $transcription['status'],
                'created_at' => $transcription['created_at'],
                'updated_at' => $transcription['updated_at'],
                'completed_at' => $transcription['completed_at'],
                'transcriber_id' => $transcription['transcriber_id']
            ] : null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el informe'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener el informe: ' . $e->getMessage()
    ]);
}
?>
