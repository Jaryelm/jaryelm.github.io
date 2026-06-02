<?php
// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

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

    // Consultar el registro de control de calidad
    $stmt = $connect->prepare("SELECT * FROM quality_control WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $qcData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($qcData) {
        echo json_encode(['success' => true, 'quality_control' => $qcData]);
    } else {
        echo json_encode(['success' => true, 'quality_control' => null]);
    }
} catch (Exception $e) {
    // Manejar errores
    http_response_code(500); // Error interno del servidor
    echo json_encode(['success' => false, 'message' => 'Error al obtener el control de calidad']);
}
?>