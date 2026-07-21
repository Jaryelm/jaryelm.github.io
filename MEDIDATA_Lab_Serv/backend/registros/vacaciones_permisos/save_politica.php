<?php
require_once '../../bd/Conexion.php';

header('Content-Type: application/json');

try {
    $policy_id = isset($_POST['policy_id']) ? intval($_POST['policy_id']) : 0;
    $min_seniority_years = $_POST['min_seniority_years'] ?? 0;
    $max_seniority_years = $_POST['max_seniority_years'] ?? 0;
    $granted_days = $_POST['granted_days'] ?? 0;
    $max_accumulated_days = $_POST['max_accumulated_days'] ?? 0;
    $status = $_POST['status'] ?? 'ACTIVE';

    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;

    if ($policy_id > 0) {
        $stmt = $pdo->prepare("UPDATE medic9ue_hr_leaves.hr_vacation_policies SET min_seniority_years = ?, max_seniority_years = ?, granted_days = ?, max_accumulated_days = ?, status = ? WHERE policy_id = ?");
        $stmt->execute([$min_seniority_years, $max_seniority_years, $granted_days, $max_accumulated_days, $status, $policy_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO medic9ue_hr_leaves.hr_vacation_policies (min_seniority_years, max_seniority_years, granted_days, max_accumulated_days, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$min_seniority_years, $max_seniority_years, $granted_days, $max_accumulated_days, $status]);
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
?>
