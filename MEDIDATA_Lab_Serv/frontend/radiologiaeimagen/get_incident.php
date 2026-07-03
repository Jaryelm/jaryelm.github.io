<?php
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
header('Content-Type: application/json');

try {
    // Verificar si se proporcionó un study_id
    $study_id = $_GET['study_id'] ?? null;

    if (empty($study_id)) {
        http_response_code(400); // Petición incorrecta
        echo json_encode(['success' => false, 'message' => 'ID de estudio no proporcionado']);
        exit;
    }

    // Consultar el registro de incidencia
    $stmt = $connect->prepare("SELECT * FROM incidents WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $incidentData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($incidentData) {
        echo json_encode(['success' => true, 'incident' => $incidentData]);
    } else {
        echo json_encode(['success' => true, 'incident' => null]);
    }
} catch (Exception $e) {
    // Manejar errores
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'message' => 'Error al obtener la incidencia']);
}
?>