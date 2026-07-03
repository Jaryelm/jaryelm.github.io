<?php
require_once('../../backend/bd/Conexion.php');

header('Content-Type: application/json');

try {
    // Verificar que se haya proporcionado el ID del paciente
    if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
        http_response_code(400); // Código HTTP 400 (Bad Request)
        echo json_encode(['error' => 'El ID del paciente (idpa) es obligatorio.']);
        exit;
    }    

    $idpa = intval($_GET['idpa']); // Sanitizar el ID del paciente

    // Consultar registros de hospitalización del paciente
    $stmt = $connect->prepare("SELECT * FROM hospitalizacion WHERE idpa = :idpa ORDER BY fere DESC LIMIT 1");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();

    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        echo json_encode(["message" => "No se encontraron registros para este paciente."]);
        exit;
    }

    echo json_encode($data);
} catch (Exception $e) {
    http_response_code(400); // Código HTTP 400: Bad Request
    echo json_encode(["error" => $e->getMessage()]);
}
?>
