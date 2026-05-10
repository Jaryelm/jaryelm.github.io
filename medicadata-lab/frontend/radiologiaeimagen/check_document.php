<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Método no permitido.");
    }

    $dni = trim($_GET['dni'] ?? '');
    if (empty($dni)) {
        throw new Exception("DNI requerido.");
    }

    global $connect;
    $stmt = $connect->prepare("SELECT COUNT(*) AS total FROM radiologiaeimagen WHERE dni = :dni");
    $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(["exists" => $result['total'] > 0]);
} catch (Exception $e) {
    echo json_encode(["exists" => false, "error" => $e->getMessage()]);
}
?>