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
