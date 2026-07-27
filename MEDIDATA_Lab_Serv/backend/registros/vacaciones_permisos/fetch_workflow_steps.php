<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;

    $workflow_id = $_GET['id'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT step_id, workflow_id, step_order, approver_type, approver_role_name
        FROM hr_approval_workflow_steps
        WHERE workflow_id = ?
        ORDER BY step_order ASC
    ");
    $stmt->execute([$workflow_id]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
