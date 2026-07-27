<?php
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

try {
    global $connect; if (!$connect) throw new Exception("Sin conexión a BD.");
    $pdo = $connect;
    
    // Traer tipos activos
    $stmt = $pdo->query("SELECT type_id, name, category, deducts_vacation, requires_document as requires_proof FROM hr_absence_types WHERE status = 1 ORDER BY name ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
