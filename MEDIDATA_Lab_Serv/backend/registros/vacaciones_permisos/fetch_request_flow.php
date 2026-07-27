<?php
// Devuelve, para el usuario actual, el flujo de aprobación que procesaría una solicitud
// (mismo criterio que submit_absence_request.php: por DEPARTAMENTO del solicitante) y sus
// pasos, para mostrar el stepper de SOLO LECTURA en el modal de "Nueva Solicitud".
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/workflow_lib.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

/**
 * Etiqueta legible del aprobador de un paso. El paso "Jefe de Departamento" muestra,
 * si está asignado, el nombre del jefe del departamento del solicitante.
 */
function vp_flow_approver_label(array $s, ?string $jefe_nombre): string
{
    switch ($s['approver_type']) {
        case 'Department_Manager':
            return 'Jefe de Departamento' . ($jefe_nombre ? ' — ' . $jefe_nombre : '');
        case 'Specific_Role':
            return str_replace('_', ' ', (string) ($s['approver_role_name'] ?? 'Rol'));
        default:
            return (string) $s['approver_type'];
    }
}

try {
    global $connect;
    if (!$connect) throw new Exception('Sin conexión a la base de datos.');

    $uid     = (int) $_SESSION['id'];
    $type_id = isset($_GET['type_id']) ? (int) $_GET['type_id'] : 0;

    // Determinar el workflow igual que submit_absence_request.php:
    // 1) por departamento del solicitante, 2) por el tipo de ausencia, 3) default (1).
    $workflow_id = medidata_workflow_para_usuario($connect, $uid, $type_id ?: null);

    // Jefe del departamento del solicitante (para etiquetar el paso correspondiente).
    $departamento_id = medidata_user_departamento($connect, $uid);
    $jefe            = $departamento_id ? medidata_departamento_jefe($connect, $departamento_id) : null;
    $jefe_nombre     = $jefe['name'] ?? null;

    // Cabecera del workflow
    $stmt = $connect->prepare("SELECT workflow_id, name, description FROM hr_approval_workflows WHERE workflow_id = ?");
    $stmt->execute([$workflow_id]);
    $wf = $stmt->fetch(PDO::FETCH_ASSOC);

    // Pasos ordenados
    $steps = [];
    if ($wf) {
        $stmt = $connect->prepare("
            SELECT s.step_order, s.approver_type, s.approver_role_name
            FROM hr_approval_workflow_steps s
            WHERE s.workflow_id = ?
            ORDER BY s.step_order ASC
        ");
        $stmt->execute([$workflow_id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
            $steps[] = [
                'step_order' => (int) $s['step_order'],
                'label'      => vp_flow_approver_label($s, $jefe_nombre),
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
