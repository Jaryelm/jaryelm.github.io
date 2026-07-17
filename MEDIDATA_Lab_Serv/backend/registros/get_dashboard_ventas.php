<?php
declare(strict_types=1);

include_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/dashboard_ventas_helper.php';

header('Content-Type: application/json; charset=utf-8');

function dv_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        dv_json(['success' => false, 'message' => 'Sin conexión a la base de datos.'], 500);
    }

    $tipo = trim((string) ($_GET['periodo'] ?? $_POST['periodo'] ?? 'mes'));
    $fechaRef = trim((string) ($_GET['fecha_ref'] ?? $_POST['fecha_ref'] ?? ''));

    if ($fechaRef === '') {
        $fechaRef = date('Y-m-d');
    }

    $payload = medidata_dv_build_payload($connect, $tipo, $fechaRef);
    dv_json($payload);
} catch (Throwable $e) {
    error_log('get_dashboard_ventas: ' . $e->getMessage());
    dv_json(['success' => false, 'message' => 'Error al generar el dashboard de ventas.'], 500);
}
