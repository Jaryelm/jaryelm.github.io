<?php
require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

// Recibir filtros desde el frontend
$data = json_decode(file_get_contents('php://input'), true);

try {
    $user_id = $_SESSION['id'] ?? null;
    $user_rol = $_SESSION['rol'] ?? '';

    // Si no es radiólogo, no mostrar ningún estudio
    if ($user_rol !== 'Radiologo') {
        echo json_encode([]);
        exit;
    }

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
            w.series_id
        FROM radiology_reports rr
        JOIN worklist w ON rr.study_id = w.study_id
        WHERE rr.user_id = ?
          AND rr.status IN ('pending', 'draft', 'final')
    ";
    $params = [$user_id];

    // Filtros opcionales
    if (!empty($data['modality'])) {
        $query .= " AND w.modality = ?";
        $params[] = $data['modality'];
    }
    if (!empty($data['date'])) {
        $query .= " AND DATE(rr.created_at) = ?";
        $params[] = $data['date'];
    }

    $query .= " ORDER BY rr.created_at DESC";

    $stmt = $connect->prepare($query);
    $stmt->execute($params);
    $studies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($studies);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
