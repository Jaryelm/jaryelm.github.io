<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($id === null || $status === null) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    $stmt = $connect_hr_leaves->prepare("UPDATE hr_approval_workflows SET status = ? WHERE workflow_id = ?");
    $stmt->execute([$status, $id]);
    
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
