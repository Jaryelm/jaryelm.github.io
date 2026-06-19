<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Encapsulamos todo en un bloque try-catch para atrapar cualquier error fatal.
try {
    require_once __DIR__ . '/../../backend/bd/Conexion.php';
    header('Content-Type: application/json');

    $id_doctor = isset($_GET['id_doctor']) ? intval($_GET['id_doctor']) : 0;

    if ($id_doctor <= 0) {
        // No es un error fatal, es un error de cliente.
        http_response_code(400);
        echo json_encode(['error' => 'ID de médico no válido.']);
        exit;
    }

    $sql = "
        SELECT 
            s.id, 
            s.codigo_servicio,
            COALESCE(NULLIF(TRIM(s.nomservicio), ''), s.nombre_servicio) AS descripcion,
            hc.porcentaje_honorario AS porcentaje_especifico,
            hc.cuota_fija AS cuota_fija_especifica
        FROM servicios_hospital s
        LEFT JOIN honorarios_configuracion hc ON s.id = hc.id_servicio AND hc.id_doctor = :id_doctor
        ORDER BY COALESCE(NULLIF(TRIM(s.nomservicio), ''), s.nombre_servicio) ASC
    ";
    
    $stmt = $connect->prepare($sql);
    $stmt->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt->execute();
    
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Devolvemos los datos dentro de una clave 'data' para compatibilidad con el frontend.
    echo json_encode(['data' => $servicios]);

} catch (Throwable $e) {
    // Si algo falla, lo capturamos y lo enviamos como un error JSON.
    // Esto evita el error 500 y nos da el mensaje exacto.
    http_response_code(200); // Respondemos con 200 OK para que el success callback de AJAX lo procese.
    echo json_encode(['error' => 'Error fatal en el servidor: ' . $e->getMessage() . ' en la línea ' . $e->getLine()]);
}
?> 