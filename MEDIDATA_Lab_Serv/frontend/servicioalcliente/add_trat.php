<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

try {
    // Validar los datos recibidos
    if (empty($_POST['trat']) || empty($_POST['tratdpa']) || empty($_POST['tratnopa'])) {
        throw new Exception("Todos los campos son obligatorios");
    }

    // Recibir los datos del formulario
    $trat1 = $_POST['trat'];
    $tratdpa1 = $_POST['tratdpa'];
    $tratnopa1 = $_POST['tratnopa'];

    // Generar la fecha y hora actual desde PHP
    $fecha_actual = date('Y-m-d H:i:s');

    // Inserción en la tabla con la fecha generada desde PHP
    $sql = "INSERT INTO treatment (nomtra, idpa, nompa, state, fere) VALUES (:nomtra, :idpa, :nompa, '1', :fere)";
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':nomtra', $trat1);
    $stmt->bindParam(':idpa', $tratdpa1);
    $stmt->bindParam(':nompa', $tratnopa1);
    $stmt->bindParam(':fere', $fecha_actual);
    $stmt->execute();

    echo "Agregado correctamente";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>