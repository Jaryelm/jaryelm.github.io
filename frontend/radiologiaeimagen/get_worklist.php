<?php
// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['id'])) {
    http_response_code(401); // No autorizado
    echo json_encode(['error' => 'Acceso no autorizado. Inicia sesión para continuar.']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $technician_id = $_SESSION['id'];

    // Construir la consulta base
    $query = "
        SELECT 
            w.*,
            COALESCE(w.patient_name, 'N/A') AS patient_name,
            COALESCE(w.study_description, 'Sin descripción') AS description,
            w.study_id AS study_id,
            CASE WHEN qc.study_id IS NOT NULL THEN 1 ELSE 0 END AS has_quality_control
        FROM worklist w
        LEFT JOIN quality_control qc ON w.id = qc.study_id
        WHERE (w.technician_id = ? OR w.technician_id IS NULL)
    ";
    $params = [$technician_id];

    // Aplicar filtros dinámicamente
    if (!empty($data['modality'])) {
        $query .= " AND w.modality = ?";
        $params[] = $data['modality'];
    }

    if (!empty($data['priority'])) {
        $query .= " AND w.priority = ?";
        $params[] = $data['priority'];
    }

    if (!empty($data['status'])) {
        $query .= " AND w.status = ?";
        $params[] = $data['status'];
    }

    if (!empty($data['date'])) {
        $query .= " AND DATE(w.study_date) = ?";
        $params[] = $data['date'];
    }

    // Filtro de búsqueda general
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

    // Transformar los resultados si es necesario
    foreach ($results as &$row) {
        $row['id'] = $row['id'] ?? null;
        $row['series_id'] = $row['series_id'] ?? null;
        $row['patient_id'] = $row['patient_id'] ?? 'N/A';
        $row['modality'] = $row['modality'] ?? 'N/A';
        $row['description'] = $row['description'] ?? 'Sin descripción';
        $row['priority'] = $row['priority'] ?? 'routine';
        $row['status'] = $row['status'] ?? 'pending';
        $row['has_quality_control'] = $row['has_quality_control'] ?? 0; // Asegurar que este campo exista
    }

    echo json_encode($results);

} catch (Exception $e) {
    http_response_code(500); // Error interno del servidor
    echo json_encode(['error' => 'Error al cargar la lista de trabajo: ' . $e->getMessage()]);
}
?>