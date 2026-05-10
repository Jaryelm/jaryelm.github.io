<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    $id = $_POST['id_dieta'] ?? null;
    $estado = $_POST['estado'] ?? null;

    if (!$id || !$estado) {
        throw new Exception("ID de dieta y estado son obligatorios.");
    }

    $sql = "UPDATE control_dieta SET estado = :estado WHERE id = :id";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':estado', $estado);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente.']);
    } else {
        throw new Exception("No se pudo actualizar el estado.");
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
