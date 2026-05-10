<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'] ?? null;
    if (!$idpa) {
        throw new Exception('El ID del paciente es obligatorio.');
    }

    $stmt = $connect->prepare("
        SELECT temps, procesado_por, frecuenciac, tensiona, spo_2, peso_kg, talla_temp, imc_temp, glucap_temp, fresp_temp, turno, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at
        FROM grafica_temperatura
        WHERE idpa = :idpa
        ORDER BY created_at DESC
    ");
    $stmt->execute([':idpa' => $idpa]);
    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
