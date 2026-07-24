<?php
// Devuelve, para el usuario actual, el flujo de aprobación que procesaría una solicitud
// (mismo criterio que submit_absence_request.php) y sus pasos, para mostrar el stepper
// de SOLO LECTURA en el modal de "Nueva Solicitud".
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

/**
 * Etiqueta legible del aprobador de un paso.
 */
function vp_flow_approver_label(array $s): string
{
    switch ($s['approver_type']) {
        case 'Direct_Manager':     return 'Jefe Inmediato';
        case 'Department_Manager': return 'Jefe de Departamento';
        case 'Specific_Role':      return str_replace('_', ' ', (string) ($s['approver_role_name'] ?? 'Rol'));
        case 'Specific_User':      return !empty($s['approver_user_name']) ? $s['approver_user_name'] : ('Usuario #' . (int) $s['approver_user_id']);
        default:                   return (string) $s['approver_type'];
    }
}

try {
    global $connect;
    if (!$connect) throw new Exception('Sin conexión a la base de datos.');

    $rol     = $_SESSION['rol'] ?? '';
    $type_id = isset($_GET['type_id']) ? (int) $_GET['type_id'] : 0;

    // Determinar el workflow igual que submit_absence_request.php:
    // 1) por rol (hr_workflow_roles), 2) por el tipo de ausencia, 3) default (1).
    $workflow_id = null;

    $stmt = $connect->prepare("SELECT workflow_id FROM medic9ue_hr_leaves.hr_workflow_roles WHERE role_name = ? LIMIT 1");
    $stmt->execute([$rol]);
    $wr = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($wr) {
        $workflow_id = (int) $wr['workflow_id'];
    } elseif ($type_id) {
        $stmt = $connect->prepare("SELECT workflow_id FROM medic9ue_hr_leaves.hr_absence_types WHERE type_id = ?");
        $stmt->execute([$type_id]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        $workflow_id = ($t && !empty($t['workflow_id'])) ? (int) $t['workflow_id'] : 1;
    } else {
        $workflow_id = 1;
    }

    // Cabecera del workflow
    $stmt = $connect->prepare("SELECT workflow_id, name, description FROM medic9ue_hr_leaves.hr_approval_workflows WHERE workflow_id = ?");
    $stmt->execute([$workflow_id]);
    $wf = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pasos ordenados
    $steps = [];
    if ($wf) {
        $stmt = $connect->prepare("
            SELECT s.step_order, s.approver_type, s.approver_role_name, s.approver_user_id, u.name AS approver_user_name
            FROM medic9ue_hr_leaves.hr_approval_workflow_steps s
            LEFT JOIN users u ON s.approver_user_id = u.id
            WHERE s.workflow_id = ?
            ORDER BY s.step_order ASC
        ");
        $stmt->execute([$workflow_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $steps[] = [
                'step_order' => (int) $s['step_order'],
                'label'      => vp_flow_approver_label($s),
            ];
        }
    }

    echo json_encode([
        'workflow' => $wf ? ['id' => (int) $wf['workflow_id'], 'name' => $wf['name'], 'description' => $wf['description']] : null,
        'steps'    => $steps,
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
