<?php
declare(strict_types=1);

/**
 * Detalle de líneas de orden (checkout). Usado por varios módulos.
 * Consulta optimizada vía diario_detalle_repository.
 */
require_once dirname(__DIR__) . '/bd/Conexion.php';
require_once dirname(__DIR__, 2) . '/backend/php/diario_detalle_repository.php';

header('Content-Type: application/json; charset=utf-8');

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'order_id inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    echo json_encode(medidata_diario_fetch_order_details_checkout($connect, $orderId), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('obtener_detalles_checkout.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al obtener detalles'], JSON_UNESCAPED_UNICODE);
}
