<?php
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

try {
    // Verificar si la solicitud es GET
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception("Método no permitido.");
    }

    // Obtener el DNI del parámetro de consulta
    $dni = trim($_GET['dni']);

    if (empty($dni)) {
        throw new Exception("El DNI es obligatorio.");
    }

    // Usar la conexión desde Conexion.php
    global $connect;

    // Consultar el archivo por DNI
    $stmt = $connect->prepare("SELECT nombre_archivo, archivo, tipo_documento FROM radiologiaeimagen WHERE dni = :dni");
    $stmt->bindParam(':dni', $dni, PDO::PARAM_STR);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(404);
        echo json_encode(["success" => false, "type" => "error", "message" => "Archivo no encontrado."]);
        exit;
    }

    // Configurar encabezados para la descarga
    header("Content-Type: " . $result['tipo_documento']);
    header("Content-Disposition: attachment; filename=\"" . $result['nombre_archivo'] . "\"");

    // Imprimir el contenido del archivo
    echo $result['archivo'];
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "type" => "error", "message" => $e->getMessage()]);
}
?>