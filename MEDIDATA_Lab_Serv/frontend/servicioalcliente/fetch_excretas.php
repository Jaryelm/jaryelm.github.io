<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'] ?? null;
    if (!$idpa) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    $stmt = $connect->prepare("
        SELECT fecha, hora, orina, vomito, drenaje, succion, otros, procesado_por 
        FROM excretas 
        WHERE idpa = :idpa
        ORDER BY created_at ASC
    ");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $records]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
