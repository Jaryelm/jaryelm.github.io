<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    $sql = "SELECT idodc, nodoc, apdoc, nomesp FROM doctor ORDER BY nodoc ASC";
    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($doctores);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener los doctores: ' . $e->getMessage()]);
}
?> 