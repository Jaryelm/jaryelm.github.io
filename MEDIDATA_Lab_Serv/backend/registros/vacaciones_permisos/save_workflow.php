<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
        exit;
    }

    if ($id) {
        $stmt = $connect_hr_leaves->prepare("UPDATE hr_approval_workflows SET name = ?, description = ? WHERE workflow_id = ?");
        $stmt->execute([$name, $description, $id]);
    } else {
        $stmt = $connect_hr_leaves->prepare("INSERT INTO hr_approval_workflows (name, description, status) VALUES (?, ?, 1)");
        $stmt->execute([$name, $description]);
    }
    
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
