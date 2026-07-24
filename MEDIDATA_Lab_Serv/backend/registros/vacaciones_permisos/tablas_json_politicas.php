<?php
require_once '../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['data' => []]);
    exit;
}
try {
    if (!isset($connect_hr_leaves)) throw new Exception("Error de conexión a BD de hojas.");
    $stmt = $connect_hr_leaves->query("SELECT * FROM hr_vacation_policies ORDER BY min_seniority_years ASC");
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
