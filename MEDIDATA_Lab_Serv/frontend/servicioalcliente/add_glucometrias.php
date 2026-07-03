<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Validar que todos los campos se reciban
    $requiredFields = ['fecha', 'turno', 'hora', 'glucometria', 'insulina_cristalina', 'nph', 'procesado_por', 'firma', 'idpa'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio.");
        }
    }

    // Escapar los datos recibidos
    $fecha = $_POST['fecha'];
    $turno = $_POST['turno'];
    $hora = $_POST['hora'];
    $glucometria = $_POST['glucometria'];
    $insulinaCristalina = $_POST['insulina_cristalina'];
    $nph = $_POST['nph'];
    $procesadoPor = $_POST['procesado_por'];
    $firma = $_POST['firma'];
    $idpa = intval($_POST['idpa']);

    // Decodificar la firma (Base64 a binario)
    $firmaBinaria = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $firma));

    // Insertar en la base de datos
    $sql = "INSERT INTO glucometrias (idpa, fecha, turno, hora, glucometria, insulina_cristalina, nph, procesado_por, firma) 
            VALUES (:idpa, :fecha, :turno, :hora, :glucometria, :insulina_cristalina, :nph, :procesado_por, :firma)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->bindParam(':turno', $turno);
    $stmt->bindParam(':hora', $hora);
    $stmt->bindParam(':glucometria', $glucometria);
    $stmt->bindParam(':insulina_cristalina', $insulinaCristalina);
    $stmt->bindParam(':nph', $nph);
    $stmt->bindParam(':procesado_por', $procesadoPor);
    $stmt->bindParam(':firma', $firmaBinaria, PDO::PARAM_LOB);

    $stmt->execute();

    echo json_encode(["success" => "Registro de Glucometrías e Insulinas guardado correctamente."]);
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error General: " . $e->getMessage());
    echo json_encode(["error" => "Error general: " . $e->getMessage()]);
}
?>
