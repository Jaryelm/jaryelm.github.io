<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Validar que todos los campos se reciban
    $requiredFields = ['fecha', 'turno', 'tipo_dieta', 'procesado_por', 'idpa'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio.");
        }
    }

    // Escapar los datos recibidos
    $fecha = date('Y-m-d');
    $created_at = date('Y-m-d H:i:s');
    $turno = $_POST['turno'];
    $tipoDieta = $_POST['tipo_dieta'];
    $procesadoPor = $_POST['procesado_por'];
    $idpa = intval($_POST['idpa']);

    // Insertar en la base de datos
    $sql = "INSERT INTO control_dieta (idpa, fecha, turno, tipo_dieta, procesado_por, created_at) 
            VALUES (:idpa, :fecha, :turno, :tipo_dieta, :procesado_por, :created_at)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':turno', $turno);
    $stmt->bindParam(':tipo_dieta', $tipoDieta);
    $stmt->bindParam(':procesado_por', $procesadoPor);
    $stmt->bindParam(':created_at', $created_at);

    $stmt->execute();

    echo json_encode(["success" => "Registro de Hoja de Control de Dieta guardado correctamente."]);
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error General: " . $e->getMessage());
    echo json_encode(["error" => "Error general: " . $e->getMessage()]);
}
?>
