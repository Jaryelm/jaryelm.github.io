<?php
require_once('../../backend/bd/Conexion.php');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

// Establecer cabecera para JSON
header('Content-Type: application/json');

try {
    // Depuración: Guardar datos recibidos en log
    file_put_contents('debug_gastos_quirofano.log', print_r($_POST, true));

    // Validar los datos recibidos
    if (
        empty($_POST['procesado_por']) ||
        empty($_POST['insumo_material_descartable']) || empty($_POST['cantidad_material_descartable']) ||
        empty($_POST['insumo_medicamentos']) || empty($_POST['cantidad_medicamentos']) ||
        empty($_POST['idpa'])
    ) {
        throw new Exception("Todos los campos son obligatorios.");
    }

    // Obtener la fecha y hora actual en Tegucigalpa
    $fecha = date('Y-m-d');
    $hora = date('H:i:s');
    $created_at = date('Y-m-d H:i:s'); // Generar la fecha y hora en la zona horaria local

    $procesadoPor = $_POST['procesado_por'];
    $insumoMaterial = $_POST['insumo_material_descartable'];
    $cantidadMaterial = $_POST['cantidad_material_descartable'];
    $insumoMedicamentos = $_POST['insumo_medicamentos'];
    $cantidadMedicamentos = $_POST['cantidad_medicamentos'];
    $idpa = $_POST['idpa'];

    // Insertar en la tabla con la fecha y hora local de PHP
    $sql = "INSERT INTO gastos_quirofano 
            (fecha, hora, procesado_por, insumo_material_descartable, cantidad_material_descartable, 
            insumo_medicamentos, cantidad_medicamentos, idpa, created_at) 
            VALUES (:fecha, :hora, :procesadoPor, :insumoMaterial, :cantidadMaterial, 
            :insumoMedicamentos, :cantidadMedicamentos, :idpa, :created_at)";

    $stmt = $connect->prepare($sql);
    $stmt->execute([
        ':fecha' => $fecha,
        ':hora' => $hora,
        ':procesadoPor' => $procesadoPor,
        ':insumoMaterial' => $insumoMaterial,
        ':cantidadMaterial' => $cantidadMaterial,
        ':insumoMedicamentos' => $insumoMedicamentos,
        ':cantidadMedicamentos' => $cantidadMedicamentos,
        ':idpa' => $idpa,
        ':created_at' => $created_at // Se envía la fecha y hora locales a la base de datos
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => "Registro guardado correctamente."]);
    } else {
        echo json_encode(["error" => "La consulta SQL no insertó datos."]);
    }
} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    echo json_encode(["error" => "Error al guardar en la base de datos."]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(["error" => $e->getMessage()]);
}
?>
