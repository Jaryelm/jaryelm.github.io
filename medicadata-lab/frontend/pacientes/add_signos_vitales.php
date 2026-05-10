<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Validar los datos recibidos
    if (
        empty($_POST['fecha']) || empty($_POST['hora']) || empty($_POST['procesado_por']) ||
        empty($_POST['fc']) || empty($_POST['ta']) || empty($_POST['temp']) ||
        empty($_POST['spo']) || empty($_POST['peso']) || empty($_POST['talla']) ||
        empty($_POST['idpa'])
    ) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    // Recibir los datos
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $procesadoPor = $_POST['procesado_por'];
    $fc = $_POST['fc'];
    $ta = $_POST['ta'];
    $temp = $_POST['temp'];
    $spo = $_POST['spo'];
    $peso = $_POST['peso'];
    $talla = $_POST['talla'];
    $idpa = $_POST['idpa'];

    // Insertar en la tabla
    $sql = "INSERT INTO signos_vitales (fecha, hora, procesado_por, fc, ta, temp, spo, peso_kg, talla, idpa)
            VALUES (:fecha, :hora, :procesadoPor, :fc, :ta, :temp, :spo, :peso, :talla, :idpa)";
    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':procesadoPor' => $procesadoPor,
        ':fc' => $fc,
        ':ta' => $ta,
        ':temp' => $temp,
        ':spo' => $spo,
        ':peso' => $peso,
        ':talla' => $talla,
        ':idpa' => $idpa
    ]);

    // Recuperar todos los registros actualizados
    $fetchSql = "SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC";
    $fetchStmt = $connect->prepare($fetchSql);
    $fetchStmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $fetchStmt->execute();
    $records = $fetchStmt->fetchAll(PDO::FETCH_ASSOC);

    // Confirmar inserción exitosa y enviar los datos
    echo json_encode([
        "success" => "Signos vitales guardados correctamente.",
        "data" => $records
    ]);
} catch (PDOException $e) {
    // Capturar errores de la base de datos
    error_log("Error de base de datos: " . $e->getMessage());
    echo json_encode(["error" => "Error al guardar en la base de datos."]);
} catch (Exception $e) {
    // Capturar errores generales
    error_log("Error general: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
