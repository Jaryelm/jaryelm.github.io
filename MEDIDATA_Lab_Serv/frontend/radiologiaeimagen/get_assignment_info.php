<?php
// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $study_id = $_GET['study_id'] ?? null;
    
    if (!$study_id) {
        throw new Exception('ID de estudio requerido');
    }
    
    // Obtener información de asignación desde worklist y radiology_reports
    $stmt = $connect->prepare("
        SELECT 
            w.id,
            w.study_id,
            w.patient_id,
            w.patient_name,
            w.modality,
            w.study_description as description,
            w.radiologist_id,
            w.radiologist_name,
            w.technician_id,
            w.technician_name,
            w.status,
            w.updated_at as completion_date,
            rr.id AS report_id,
            rr.status AS report_status,
            rr.created_at as assignment_date,
            CASE 
                WHEN w.radiologist_id IS NOT NULL AND w.radiologist_id > 0 THEN 'asignado'
                ELSE 'no_asignado'
            END as assignment_status
        FROM worklist w
        LEFT JOIN radiology_reports rr ON w.study_id = rr.study_id
        WHERE w.id = ?
        ORDER BY rr.id DESC
        LIMIT 1
    ");
    
    $stmt->execute([$study_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($assignment) {
        $statusCode = (string) ($assignment['status'] ?? '');
        $reportStatus = (string) ($assignment['report_status'] ?? '');
        $blockedReassign = ['pending_transcription', 'final', 'transcribed', 'reviewed'];
        $assignment['status_code'] = $statusCode;
        $assignment['report_status_code'] = $reportStatus;
        $assignment['can_reassign'] = $statusCode !== 'cancelled'
            && (int) ($assignment['radiologist_id'] ?? 0) > 0
            && ($reportStatus === '' || !in_array($reportStatus, $blockedReassign, true));

        // Formatear fechas solo si realmente se asignó un médico
        if ($assignment['assignment_status'] === 'asignado' && $assignment['assignment_date']) {
            $assignment['assignment_date'] = date('d/m/Y H:i', strtotime($assignment['assignment_date']));
        } else {
            $assignment['assignment_date'] = null; // No mostrar fecha si no se asignó médico
        }
        
        $assignment['completion_date'] = $assignment['completion_date'] ? date('d/m/Y H:i', strtotime($assignment['completion_date'])) : null;
        
        // Traducir estado
        $status_map = [
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado'
        ];
        $assignment['status'] = $status_map[$assignment['status']] ?? $assignment['status'];
        
        echo json_encode([
            'success' => true,
            'assignment' => $assignment
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Estudio no encontrado'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 