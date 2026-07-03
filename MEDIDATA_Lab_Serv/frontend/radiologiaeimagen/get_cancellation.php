<?php
// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $study_id = $_GET['study_id'] ?? null;

    if (empty($study_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de estudio no proporcionado']);
        exit;
    }

    $stmt = $connect->prepare("SELECT * FROM study_cancellations WHERE study_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$study_id]);
    $cancelData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cancelData) {
        echo json_encode(['success' => true, 'cancellation' => $cancelData]);
    } else {
        echo json_encode(['success' => true, 'cancellation' => null]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener la cancelación']);
} 