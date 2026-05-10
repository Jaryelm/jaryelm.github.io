<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

try {
    if (!isset($_POST['idpa']) || !isset($_POST['turno']) || !isset($_POST['procedimiento_realizado']) || !isset($_POST['procesado_por'])) {
        throw new Exception("Faltan datos obligatorios.");
    }

    $idpa = intval($_POST['idpa']);
    $turno = trim($_POST['turno']);
    $procedimiento_realizado = trim($_POST['procedimiento_realizado']);
    $procesado_por = trim($_POST['procesado_por']);
    $fecha_registro = date('Y-m-d H:i:s'); // Fecha local

    // Insertar datos
    $stmt = $connect->prepare("INSERT INTO procedimientos (idpa, turno, procedimiento_realizado, procesado_por, fecha_registro) VALUES (:idpa, :turno, :procedimiento_realizado, :procesado_por, :fecha_registro)");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->bindParam(':turno', $turno, PDO::PARAM_STR);
    $stmt->bindParam(':procedimiento_realizado', $procedimiento_realizado, PDO::PARAM_STR);
    $stmt->bindParam(':procesado_por', $procesado_por, PDO::PARAM_STR);
    $stmt->bindParam(':fecha_registro', $fecha_registro);
    $stmt->execute();

    echo json_encode(["success" => true, "message" => "Procedimiento guardado correctamente."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
