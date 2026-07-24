<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';

header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    $policy_id = isset($_POST['policy_id']) ? intval($_POST['policy_id']) : 0;

    if ($policy_id === 0) {
        throw new Exception("Invalid ID");
    }

    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;

    $stmt = $pdo->prepare("SELECT status FROM medic9ue_hr_leaves.hr_vacation_policies WHERE policy_id = ?");
    $stmt->execute([$policy_id]);
    $current = $stmt->fetchColumn();

    $new_status = ($current == 1) ? 0 : 1;

    $stmt = $pdo->prepare("UPDATE medic9ue_hr_leaves.hr_vacation_policies SET status = ? WHERE policy_id = ?");
    $stmt->execute([$new_status, $policy_id]);

    medidata_audit_log($pdo, (int) ($_SESSION['id'] ?? 0), 'TOGGLE_POLICY', 'hr_vacation_policies', $policy_id, ['status' => $current], ['status' => $new_status]);

    echo json_encode(['success' => true, 'new_status' => $new_status]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>

