<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';

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

    // Nuevo modelo (migración 20260726-02): un paso solo puede ser el Jefe de
    // Departamento del solicitante (se resuelve por departaments.id_jefe) o un
    // rol administrativo (Recursos Humanos / Administrador).
    $rolesPermitidos = ['Recursos_Humanos', 'Administrador'];

    $pdo->beginTransaction();

    // Eliminar pasos anteriores
    $stmt = $pdo->prepare("DELETE FROM hr_approval_workflow_steps WHERE workflow_id = ?");
    $stmt->execute([$workflow_id]);

    // Insertar nuevos pasos
    $stmtInsert = $pdo->prepare("INSERT INTO hr_approval_workflow_steps
        (workflow_id, step_order, approver_type, approver_role_name)
        VALUES (?, ?, ?, ?)");

    foreach ($steps as $step) {
        $order = (int) ($step['order'] ?? 0);
        $type = (string) ($step['type'] ?? '');
        $role_name = null;

        if ($type === 'Specific_Role') {
            $role_name = (string) ($step['value'] ?? '');
            if (!in_array($role_name, $rolesPermitidos, true)) {
                throw new Exception("Rol de aprobador no permitido: " . $role_name);
            }
        } elseif ($type !== 'Department_Manager') {
            throw new Exception("Tipo de aprobador no permitido: " . $type);
        }

        $stmtInsert->execute([$workflow_id, $order, $type, $role_name]);
    }

    $pdo->commit();

    medidata_audit_log($pdo, (int) ($_SESSION['id'] ?? 0), 'UPDATE_WORKFLOW_STEPS', 'hr_approval_workflow_steps', $workflow_id, null, ['pasos' => count($steps)]);

    echo json_encode(['success' => true]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
