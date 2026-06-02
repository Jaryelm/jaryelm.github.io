<?php
require_once('../../backend/bd/Conexion.php');
session_start();
date_default_timezone_set('America/Tegucigalpa');
header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $study_id = $input['study_id'] ?? null;
    $technician_id = $_SESSION['id'] ?? null;
    $local_time = date('Y-m-d H:i:s');

    if (!$study_id || !$technician_id) {
        echo json_encode(['success' => false, 'message' => 'ID de estudio o técnico no proporcionado.']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE worklist SET status = 'completed', updated_at = ?, technician_id = ? WHERE id = ?");
    $stmt->execute([$local_time, $technician_id, $study_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 