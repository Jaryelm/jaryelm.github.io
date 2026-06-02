<?php
declare(strict_types=1);

/**
 * Detalle de compra (COMP-#) para Diario General (modal).
 */
require_once dirname(__DIR__, 2) . '/backend/registros/session_check.php';
require_once dirname(__DIR__, 2) . '/backend/bd/Conexion.php';
require_once dirname(__DIR__, 2) . '/backend/php/diario_detalle_repository.php';

header('Content-Type: application/json; charset=utf-8');

$idCompra = isset($_GET['id_compra']) ? (int) $_GET['id_compra'] : 0;
if (isset($_POST['id_compra'])) {
    $idCompra = (int) $_POST['id_compra'];
}

if ($idCompra < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'id_compra inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $rows = medidata_diario_fetch_detalle_compras($connect, $idCompra);
    echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('diario_detalle_compra.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'No se pudo cargar el detalle de la compra.'], JSON_UNESCAPED_UNICODE);
}
