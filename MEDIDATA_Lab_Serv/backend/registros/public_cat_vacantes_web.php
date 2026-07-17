<?php
/**
 * Catálogo público de vacantes abiertas para medicasa.hn/aplica.html
 * Solo lectura; sin autenticación.
 */
declare(strict_types=1);

$allowedOrigins = [
    'https://medicasa.hn',
    'https://www.medicasa.hn',
    'http://medicasa.hn',
    'http://www.medicasa.hn',
];

$origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=120');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/postulaciones_guard.php';
require_once __DIR__ . '/rrhh_aplica_bridge.php';

medidata_rrhh_aplica_bridge_ensure_schema();

if (!medidata_rrhh_pdo()) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Catálogo de vacantes no disponible temporalmente.',
        'data' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => medidata_rrhh_public_cat_vacantes_web(),
], JSON_UNESCAPED_UNICODE);
