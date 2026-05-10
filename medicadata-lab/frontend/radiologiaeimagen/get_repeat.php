<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $study_id = $_GET['study_id'] ?? null;

    if (empty($study_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de estudio no proporcionado']);
        exit;
    }

    $stmt = $connect->prepare("SELECT * FROM study_repeats WHERE study_id = ?");
    $stmt->execute([$study_id]);
    $repeatData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($repeatData) {
        echo json_encode(['success' => true, 'repeat' => $repeatData]);
    } else {
        echo json_encode(['success' => true, 'repeat' => null]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener la repetición']);
}
?> 