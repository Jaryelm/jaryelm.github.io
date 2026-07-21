<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) {
        throw new Exception("Error de conexión a la base de datos de recursos humanos.");
    }

    $stmt = $connect_hr_leaves->query("SELECT workflow_id, name, description, status FROM hr_approval_workflows ORDER BY workflow_id DESC");
    $data = $stmt->fetchAll();
    
    echo json_encode($data);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
