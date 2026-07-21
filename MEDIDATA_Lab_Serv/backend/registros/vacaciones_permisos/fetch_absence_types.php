<?php
require_once '../../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

try {
    if (!isset($connect_hr_leaves)) throw new Exception("Sin conexión a BD.");
    
    // Traer tipos activos
    $stmt = $connect_hr_leaves->query("SELECT type_id, name, category, deducts_vacation, requires_proof FROM hr_absence_types WHERE is_active = 1");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
