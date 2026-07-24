<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

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

    $actor = (int) ($_SESSION['id'] ?? 0);
    if ($id) {
        $stmt = $connect_hr_leaves->prepare("UPDATE hr_approval_workflows SET name = ?, description = ? WHERE workflow_id = ?");
        $stmt->execute([$name, $description, $id]);
        medidata_audit_log($connect_hr_leaves, $actor, 'UPDATE_WORKFLOW', 'hr_approval_workflows', $id, null, ['name' => $name, 'description' => $description]);
    } else {
        $stmt = $connect_hr_leaves->prepare("INSERT INTO hr_approval_workflows (name, description, status) VALUES (?, ?, 1)");
        $stmt->execute([$name, $description]);
        medidata_audit_log($connect_hr_leaves, $actor, 'CREATE_WORKFLOW', 'hr_approval_workflows', $connect_hr_leaves->lastInsertId(), null, ['name' => $name, 'description' => $description]);
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
