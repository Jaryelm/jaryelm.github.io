<?php
require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

// Definir $today al inicio
$today = date('Y-m-d');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id'])) {
    http_response_code(401); // No autorizado
    echo json_encode(['error' => 'Acceso no autorizado. Inicia sesión para continuar.']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    // $technician_id = $_SESSION['id']; // Ya no lo necesitas aquí

    // Construir la consulta base
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
        LEFT JOIN quality_control qc ON w.id = qc.study_id
        INNER JOIN radiology_reports r ON w.study_id = r.study_id
        LEFT JOIN report_transcriptions rt ON r.id = rt.report_id
        WHERE 1=1
    ";
    $params = [];

    // Filtros dinámicos (solo modalidad, prioridad, fecha y búsqueda)
    if (!empty($data['modality'])) {
        $query .= " AND w.modality = ?";
        $params[] = $data['modality'];
    }
    if (!empty($data['priority'])) {
        $query .= " AND w.priority = ?";
        $params[] = $data['priority'];
    }
    if (!empty($data['status'])) {
        if ($data['status'] === 'pending') {
            $query .= " AND (rt.status IS NULL OR rt.status IN ('pending', 'in_progress'))";
        } elseif ($data['status'] === 'completed') {
            $query .= " AND rt.status = 'completed'";
        }
    } else {
        // Si no hay filtro, mostrar todos los estudios pendientes, en progreso y completados
        $query .= " AND (rt.status IS NULL OR rt.status IN ('pending', 'in_progress', 'completed'))";
    }
    if (!empty($data['date'])) {
        $query .= " AND DATE(w.study_date) = ?";
        $params[] = $data['date'];
    }
    if (!empty($data['search'])) {
        $query .= " AND (w.patient_name LIKE ? OR w.patient_id LIKE ? OR w.study_description LIKE ?)";
        $searchTerm = '%' . $data['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    // Ordenar por prioridad y fecha
    $query .= " ORDER BY 
        FIELD(w.priority, 'emergency', 'urgent', 'routine'),
        w.study_date DESC";

    $stmt = $connect->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Transformar los resultados para mostrar el estado correcto
    foreach ($results as &$row) {
        $row['id'] = $row['report_id'] ?? $row['id'] ?? null;
        $row['series_id'] = $row['series_id'] ?? null;
        $row['patient_id'] = $row['patient_id'] ?? 'N/A';
        $row['modality'] = $row['modality'] ?? 'N/A';
        $row['description'] = $row['description'] ?? 'Sin descripción';
        $row['priority'] = $row['priority'] ?? 'routine';
        // Estado visual
        if ($row['transcription_status'] === 'completed' && $row['completed_at'] && substr($row['completed_at'], 0, 10) === $today) {
            $row['status'] = 'completed';
        } elseif (empty($row['transcription_status']) || in_array($row['transcription_status'], ['pending', 'in_progress'])) {
            $row['status'] = 'pending_transcription';
        } else {
            $row['status'] = $row['transcription_status'] ?? 'pending_transcription';
        }
        $row['has_quality_control'] = $row['has_quality_control'] ?? 0;
    }

    echo json_encode($results);
    exit;

} catch (Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['error' => 'Error al cargar la lista de trabajo: ' . $e->getMessage()]);
}
?>