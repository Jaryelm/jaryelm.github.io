<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bd/Conexion.php';
require_once dirname(__DIR__, 2) . '/backend/php/diario_detalle_repository.php';

header('Content-Type: application/json; charset=utf-8');

$idCompra = isset($_POST['id_compra']) ? (int) $_POST['id_compra'] : (int) ($_GET['id_compra'] ?? 0);
if ($idCompra < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'id_compra inválido'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    echo json_encode(medidata_diario_fetch_detalle_compras($connect, $idCompra), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('obtener_detalle_compras.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
