<?php
declare(strict_types=1);

/**
 * Detalle de venta para Diario General (modal).
 */
require_once dirname(__DIR__, 2) . '/backend/registros/session_check.php';
require_once dirname(__DIR__, 2) . '/backend/bd/Conexion.php';
require_once dirname(__DIR__, 2) . '/backend/php/diario_detalle_repository.php';

header('Content-Type: application/json; charset=utf-8');

$orderId = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
if ($orderId < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'order_id inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rows = medidata_diario_fetch_order_details_checkout($connect, $orderId);
    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('diario_detalle_venta.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se pudo cargar el detalle de la venta.'], JSON_UNESCAPED_UNICODE);
}
