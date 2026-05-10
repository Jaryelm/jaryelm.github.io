<?php
require '../../backend/bd/Conexion.php';

header('Content-Type: application/json');

try {
    $sql = "SELECT idodc, nodoc, apdoc, nomesp, comisiona FROM doctor ORDER BY nodoc, apdoc";
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($medicos);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener médicos: ' . $e->getMessage()]);
}
?> 