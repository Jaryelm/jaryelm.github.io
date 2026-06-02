<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/mh_pacs_studies_repository.php';

try {
    $summary = medidata_mh_pacs_studies_summary($connect);
    echo json_encode([
        'total'     => $summary['total'],
        'last_sync' => $summary['last_sync'],
        'source'    => 'worklist',
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_total_studies.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener el total de estudios'], JSON_UNESCAPED_UNICODE);
}
<<<<<<< Updated upstream

// Obtener el código de estado HTTP
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

unset($ch);

// Verificar el código de estado HTTP
if ($http_status != 200) {
    error_log("HTTP error: $http_status"); // Registrar el código de error
    die(json_encode(['error' => 'Error al obtener los estudios desde Orthanc']));
}

// Decodificar la respuesta JSON
$data = json_decode($response, true);

// Calcular el número total de estudios
$totalStudies = is_array($data) ? count($data) : 0;

// Devolver el total de estudios
echo json_encode(['total' => $totalStudies]);
?>
=======
>>>>>>> Stashed changes
