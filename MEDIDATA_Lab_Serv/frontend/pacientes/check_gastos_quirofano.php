<?php
require('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    $idpa = $_GET['idpa'] ?? null;

    if (!$idpa) {
        throw new Exception("El ID del paciente es obligatorio.");
    }

    // Verificar si hay registros en gastos_quirofano
    $stmt = $connect->prepare("
        SELECT COUNT(*) as total 
        FROM gastos_quirofano 
        WHERE idpa = :idpa
    ");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $hasData = $result['total'] > 0;

    echo json_encode(["success" => true, "hasData" => $hasData]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
