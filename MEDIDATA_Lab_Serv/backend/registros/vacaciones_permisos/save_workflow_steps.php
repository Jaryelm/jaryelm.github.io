<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;
    
    // Leer el payload JSON
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $workflow_id = $data['workflow_id'] ?? 0;
    $steps = $data['steps'] ?? [];
    
    if (empty($workflow_id)) {
        throw new Exception("ID de flujo no válido");
    }

    $pdo->beginTransaction();

    // Eliminar pasos anteriores
    $stmt = $pdo->prepare("DELETE FROM medic9ue_hr_leaves.hr_approval_workflow_steps WHERE workflow_id = ?");
    $stmt->execute([$workflow_id]);

    // Insertar nuevos pasos
    $stmtInsert = $pdo->prepare("INSERT INTO medic9ue_hr_leaves.hr_approval_workflow_steps 
        (workflow_id, step_order, approver_type, approver_role_name, approver_user_id) 
        VALUES (?, ?, ?, ?, ?)");

    foreach ($steps as $step) {
        $order = $step['order'];
        $type = $step['type'];
        $role_name = null;
        $user_id = null;

        if ($type === 'Specific_Role') {
            $role_name = $step['value'];
        } else if ($type === 'Specific_User') {
            $user_id = (int)$step['value'];
        }

        $stmtInsert->execute([$workflow_id, $order, $type, $role_name, $user_id]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>

