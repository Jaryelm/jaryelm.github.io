<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$doctorId = $data['doctorId'] ?? null;
$porcentaje = $data['porcentaje'] ?? null;

if ($doctorId === null || $porcentaje === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos. Se requiere ID del doctor y el porcentaje.']);
    exit;
}

if (!is_numeric($porcentaje) || $porcentaje < 0 || $porcentaje > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'El porcentaje debe ser un número entre 0 y 100.']);
    exit;
}

try {
    $sql = "UPDATE doctor SET porcentaje_honorario = :porcentaje WHERE idodoc = :idodoc";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':porcentaje', $porcentaje);
    $stmt->bindParam(':idodoc', $doctorId, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Porcentaje actualizado correctamente.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo actualizar el porcentaje.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?> 