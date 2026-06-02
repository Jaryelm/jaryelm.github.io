<?php
require_once('../../backend/bd/Conexion.php');
date_default_timezone_set('America/Tegucigalpa');

try {
    // Validar campos obligatorios
    if (
        empty($_POST['consl']) || 
        empty($_POST['csidpa']) || 
        empty($_POST['csnopa']) || 
        empty($_POST['medico_tratante']) || 
        empty($_POST['especialidad']) || 
        empty($_POST['servicio'])
    ) {
        throw new Exception("Todos los campos obligatorios deben ser completados.");
    }

    // Recibir datos
    $consl1 = $_POST['consl'];
    $csidpa1 = $_POST['csidpa'];
    $csnopa1 = $_POST['csnopa'];
    $medico_tratante = $_POST['medico_tratante'];
    $especialidad = $_POST['especialidad'];
    $servicio = $_POST['servicio'];
    $habitacion_no = $_POST['habitacion_no'] ?? null; // Valor nulo si no se proporciona
    $fecha_hora_ingreso = $_POST['fecha_hora_ingreso'] ?? null; // Valor nulo si no se proporciona
    $fecha_hora_egreso = $_POST['fecha_hora_egreso'] ?? null; // Valor nulo si no se proporciona
    $fecha_actual = date('Y-m-d H:i:s');

    // Ajustar formato de fecha y hora para los campos opcionales
    $fecha_hora_ingreso = !empty($fecha_hora_ingreso) ? date('Y-m-d H:i:s', strtotime($fecha_hora_ingreso)) : null;
    $fecha_hora_egreso = !empty($fecha_hora_egreso) ? date('Y-m-d H:i:s', strtotime($fecha_hora_egreso)) : null;

    // Preparar la consulta SQL
    $sql = "INSERT INTO consult 
            (mtcl, idpa, nompa, medico_tratante, especialidad, servicio, habitacion_no, fecha_hora_ingreso, fecha_hora_egreso, state, fere) 
            VALUES 
            (:consl, :idpa, :nompa, :medico_tratante, :especialidad, :servicio, :habitacion_no, :fecha_hora_ingreso, :fecha_hora_egreso, '1', :fere)";
    $stmt = $connect->prepare($sql);

    // Asignar parámetros
    $stmt->bindParam(':consl', $consl1);
    $stmt->bindParam(':idpa', $csidpa1);
    $stmt->bindParam(':nompa', $csnopa1);
    $stmt->bindParam(':medico_tratante', $medico_tratante);
    $stmt->bindParam(':especialidad', $especialidad);
    $stmt->bindParam(':servicio', $servicio);
    $stmt->bindParam(':habitacion_no', $habitacion_no);
    $stmt->bindParam(':fecha_hora_ingreso', $fecha_hora_ingreso);
    $stmt->bindParam(':fecha_hora_egreso', $fecha_hora_egreso);
    $stmt->bindParam(':fere', $fecha_actual);

    // Ejecutar la consulta
    $stmt->execute();

    echo "Agregado correctamente";
} catch (Exception $e) {
    // Capturar y mostrar errores
    echo "Error: " . $e->getMessage();
}
?>
