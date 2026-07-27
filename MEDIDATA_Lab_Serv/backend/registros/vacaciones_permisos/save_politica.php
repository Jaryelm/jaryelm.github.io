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
    $min_seniority_years = $_POST['min_seniority_years'] ?? 0;
    $max_seniority_years = $_POST['max_seniority_years'] ?? 0;
    $granted_days = $_POST['granted_days'] ?? 0;
    $max_accumulated_days = $_POST['max_accumulated_days'] ?? 0;
    $status = isset($_POST['status']) ? intval($_POST['status']) : 1;

    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;

    $actor = (int) ($_SESSION['id'] ?? 0);
    $new_vals = [
        'min_seniority_years'  => $min_seniority_years,
        'max_seniority_years'  => $max_seniority_years,
        'granted_days'         => $granted_days,
        'max_accumulated_days' => $max_accumulated_days,
        'status'               => $status,
    ];

    if ($policy_id > 0) {
        $oldStmt = $pdo->prepare("SELECT min_seniority_years, max_seniority_years, granted_days, max_accumulated_days, status FROM hr_vacation_policies WHERE policy_id = ?");
        $oldStmt->execute([$policy_id]);
        $old_vals = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $stmt = $pdo->prepare("UPDATE hr_vacation_policies SET min_seniority_years = ?, max_seniority_years = ?, granted_days = ?, max_accumulated_days = ?, status = ? WHERE policy_id = ?");
        $stmt->execute([$min_seniority_years, $max_seniority_years, $granted_days, $max_accumulated_days, $status, $policy_id]);

        medidata_audit_log($pdo, $actor, 'UPDATE_POLICY', 'hr_vacation_policies', $policy_id, $old_vals, $new_vals);
    } else {
        $stmt = $pdo->prepare("INSERT INTO hr_vacation_policies (min_seniority_years, max_seniority_years, granted_days, max_accumulated_days, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$min_seniority_years, $max_seniority_years, $granted_days, $max_accumulated_days, $status]);

        medidata_audit_log($pdo, $actor, 'CREATE_POLICY', 'hr_vacation_policies', $pdo->lastInsertId(), null, $new_vals);
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>

