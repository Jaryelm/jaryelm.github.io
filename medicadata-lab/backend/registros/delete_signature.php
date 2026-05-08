<?php
require_once '../../backend/bd/Conexion.php'; // $connect está definido aquí como un objeto PDO
header('Content-Type: application/json');

// Leer los datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de firma no proporcionado.']);
    exit;
}

$signatureId = intval($data['id']);

try {
    // Eliminar la firma por ID
    $query = "DELETE FROM user_signatures WHERE id = :id";
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':id', $signatureId, PDO::PARAM_INT);

    if ($stmt->execute() && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Firma eliminada exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró una firma con el ID especificado.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la firma: ' . $e->getMessage()]);
}
