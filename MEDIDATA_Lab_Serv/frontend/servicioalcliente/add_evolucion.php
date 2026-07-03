<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Validar que todos los campos se reciban
    $requiredFields = ['nota_evolucion', 'fecha_hora', 'ordenes_medicas', 'procesado_por', 'idpa'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio.");
        }
    }

    // Escapar los datos recibidos
    $notaEvolucion = $_POST['nota_evolucion'];
    $fechaHora = $_POST['fecha_hora'];
    $ordenesMedicas = $_POST['ordenes_medicas'];
    $procesadoPor = $_POST['procesado_por'];
    $idpa = intval($_POST['idpa']);

    // Insertar en la base de datos
    $sql = "INSERT INTO control_evolucion (idpa, nota_evolucion, fecha_hora, ordenes_medicas, procesado_por) 
            VALUES (:idpa, :nota_evolucion, :fecha_hora, :ordenes_medicas, :procesado_por)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':nota_evolucion', $notaEvolucion);
    $stmt->bindParam(':fecha_hora', $fechaHora);
    $stmt->bindParam(':ordenes_medicas', $ordenesMedicas);
    $stmt->bindParam(':procesado_por', $procesadoPor);

    $stmt->execute();

    echo json_encode(["success" => "Registro de Hoja de Evolución guardado correctamente."]);
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error General: " . $e->getMessage());
    echo json_encode(["error" => "Error general: " . $e->getMessage()]);
}
?>