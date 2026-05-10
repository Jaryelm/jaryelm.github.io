<?php
require('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
    echo json_encode(["success" => false, "message" => "ID del paciente no proporcionado."]);
    exit;
}

$idpa = intval($_GET['idpa']);

try {
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM anestesia WHERE idpa = :idpa");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && $result['total'] > 0) {
        echo json_encode(["success" => true, "hasData" => true]);
    } else {
        echo json_encode(["success" => true, "hasData" => false]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error al verificar los datos: " . $e->getMessage()]);
}
?>
