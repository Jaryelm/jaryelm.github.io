<?php
require_once '../../bd/Conexion.php';

try {
    global $connect; if (!$connect) throw new Exception("No db connection"); $pdo = $connect;
    
    $stmt = $pdo->prepare("SELECT policy_id, min_seniority_years, max_seniority_years, granted_days, max_accumulated_days, status FROM hr_vacation_policies ORDER BY min_seniority_years ASC");
    $stmt->execute();
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
