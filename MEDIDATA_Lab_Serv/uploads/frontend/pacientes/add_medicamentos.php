<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Validar que todos los campos se reciban
    $requiredFields = ['medicamento_tratamiento', 'fecha_hora', 'procesado_por', 'idpa'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio.");
        }
    }

    // Escapar los datos recibidos
    $medicamentoTratamiento = $_POST['medicamento_tratamiento'];
    $fechaHora = $_POST['fecha_hora'];
    $procesadoPor = $_POST['procesado_por'];
    $idpa = intval($_POST['idpa']);

    // Insertar en la base de datos
    $sql = "INSERT INTO control_medicamentos (idpa, medicamento_tratamiento, fecha_hora, procesado_por) 
            VALUES (:idpa, :medicamento_tratamiento, :fecha_hora, :procesado_por)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':medicamento_tratamiento', $medicamentoTratamiento);
    $stmt->bindParam(':fecha_hora', $fechaHora);
    $stmt->bindParam(':procesado_por', $procesadoPor);

    $stmt->execute();

    echo json_encode(["success" => "Registro de Hoja de Medicamentos guardado correctamente."]);
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode(["error" => "Error en la base de datos: " . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error General: " . $e->getMessage());
    echo json_encode(["error" => "Error general: " . $e->getMessage()]);
}
?>
