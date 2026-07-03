<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera JSON
header('Content-Type: application/json');

try {
    // Validar que todos los campos requeridos estén presentes
    $requiredFields = ['fecha', 'hora', 'via_oral_tipo', 'via_oral_cantidad', 'via_parenteral_tipo', 'via_parenteral_cantidad', 'procesado_por', 'idpa'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio.");
        }
    }

    // Escapar y asignar datos
    $idpa = intval($_POST['idpa']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $viaOralTipo = $_POST['via_oral_tipo'];
    $viaOralCantidad = $_POST['via_oral_cantidad'];
    $viaParenteralTipo = $_POST['via_parenteral_tipo'];
    $viaParenteralCantidad = $_POST['via_parenteral_cantidad'];
    $procesadoPor = $_POST['procesado_por'];

    // Insertar en la base de datos
    $sql = "INSERT INTO ingestas (idpa, fecha, hora, via_oral_tipo, via_oral_cantidad, via_parenteral_tipo, via_parenteral_cantidad, procesado_por) 
            VALUES (:idpa, :fecha, :hora, :via_oral_tipo, :via_oral_cantidad, :via_parenteral_tipo, :via_parenteral_cantidad, :procesado_por)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':hora', $hora);
    $stmt->bindParam(':via_oral_tipo', $viaOralTipo);
    $stmt->bindParam(':via_oral_cantidad', $viaOralCantidad);
    $stmt->bindParam(':via_parenteral_tipo', $viaParenteralTipo);
    $stmt->bindParam(':via_parenteral_cantidad', $viaParenteralCantidad);
    $stmt->bindParam(':procesado_por', $procesadoPor);
    $stmt->execute();

    echo json_encode(["success" => "Registro de INGESTAS guardado correctamente."]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
