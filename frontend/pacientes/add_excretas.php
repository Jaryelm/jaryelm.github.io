<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera JSON
header('Content-Type: application/json');

try {
    // Validar que todos los campos requeridos estén presentes
    $requiredFields = ['fecha', 'hora', 'orina', 'vomito', 'drenaje', 'succion', 'otros', 'procesado_por', 'idpa'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio.");
        }
    }

    // Escapar y asignar datos
    $idpa = intval($_POST['idpa']);
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $orina = $_POST['orina'];
    $vomito = $_POST['vomito'];
    $drenaje = $_POST['drenaje'];
    $succion = $_POST['succion'];
    $otros = $_POST['otros'];
    $procesadoPor = $_POST['procesado_por'];

    // Insertar en la base de datos
    $sql = "INSERT INTO excretas (idpa, fecha, hora, orina, vomito, drenaje, succion, otros, procesado_por) 
            VALUES (:idpa, :fecha, :hora, :orina, :vomito, :drenaje, :succion, :otros, :procesado_por)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':hora', $hora);
    $stmt->bindParam(':orina', $orina);
    $stmt->bindParam(':vomito', $vomito);
    $stmt->bindParam(':drenaje', $drenaje);
    $stmt->bindParam(':succion', $succion);
    $stmt->bindParam(':otros', $otros);
    $stmt->bindParam(':procesado_por', $procesadoPor);
    $stmt->execute();

    echo json_encode(["success" => "Registro de EXCRETAS guardado correctamente."]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
