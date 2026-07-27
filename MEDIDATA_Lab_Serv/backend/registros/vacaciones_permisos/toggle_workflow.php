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
    if (!isset($connect) || !$connect) {
        throw new Exception("Error de conexión a la base de datos.");
    }

    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if ($id === null || $status === null) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    $stmt = $connect->prepare("UPDATE hr_approval_workflows SET status = ? WHERE workflow_id = ?");
    $stmt->execute([$status, $id]);

    medidata_audit_log($connect, (int) ($_SESSION['id'] ?? 0), 'TOGGLE_WORKFLOW', 'hr_approval_workflows', $id, null, ['status' => (int) $status]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
