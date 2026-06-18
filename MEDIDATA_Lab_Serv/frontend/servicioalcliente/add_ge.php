<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

try {
    // Validar los datos recibidos
    if (empty($_POST['det1']) || empty($_POST['pa1']) || empty($_POST['nomp1'])) {
        throw new Exception("Todos los campos son obligatorios");
    }

    // Recibir los datos del formulario
    $det2 = $_POST['det1'];
    $pa2 = $_POST['pa1'];
    $nomp2 = $_POST['nomp1'];

    // Generar la fecha y hora actual desde PHP
    $fecha_actual = date('Y-m-d H:i:s');

    // Inserción en la tabla con la fecha generada desde PHP
    $sql = "INSERT INTO genogram (detage, idpa, nompa, state, fere) VALUES (:detage, :idpa, :nompa, '1', :fere)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':detage', $det2);
    $stmt->bindParam(':idpa', $pa2);
    $stmt->bindParam(':nompa', $nomp2);
    $stmt->bindParam(':fere', $fecha_actual);
    $stmt->execute();

    // Obtener el último registro insertado
    $last_id = $connect->lastInsertId();
    $stmt = $connect->prepare("SELECT * FROM genogram WHERE idge = :last_id");
    $stmt->bindParam(':last_id', $last_id);
    $stmt->execute();
    $new_record = $stmt->fetch(PDO::FETCH_ASSOC);

    // Enviar el registro en formato JSON
    echo json_encode($new_record);
} catch (Exception $e) {
    http_response_code(500); // Devuelve error HTTP 500
    echo json_encode(["error" => $e->getMessage()]);
}
?>