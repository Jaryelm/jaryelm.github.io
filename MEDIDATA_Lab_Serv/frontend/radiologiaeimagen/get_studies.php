<?php
declare(strict_types=1);

/**
 * Lista paginada MH-PACS desde worklist (MySQL local).
 * Evita descargar miles de estudios de Orthanc en cada visita.
 */

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/mh_pacs_studies_repository.php';

if (!function_exists('getStudiesSendJson')) {
    function getStudiesSendJson(array $payload, int $httpCode = 200): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($payload, $flags);
        if ($json === false) {
            $json = json_encode(
                ['success' => false, 'error' => 'No se pudo generar la respuesta JSON.'],
                JSON_UNESCAPED_UNICODE
            );
        }
        echo $json;
        exit;
    }
}

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($err['type'], $fatalTypes, true) || headers_sent()) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    getStudiesSendJson(
        ['success' => false, 'error' => 'Error interno al procesar estudios. Revise el log del servidor.'],
        500
    );
});

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

try {
    $result = medidata_mh_pacs_fetch_studies($connect, $page, $limit, $search);

    getStudiesSendJson([
        'success'   => true,
        'studies'   => $result['studies'],
        'total'     => $result['total'],
        'page'      => $result['page'],
        'limit'     => $result['limit'],
        'totalPages'=> $result['totalPages'],
        'last_sync' => $result['last_sync'],
        'source'    => 'worklist',
    ], 200);
} catch (Throwable $e) {
    error_log('get_studies.php: ' . $e->getMessage());
    getStudiesSendJson([
        'success' => false,
        'error'   => 'No se pudieron cargar los estudios. Use «Sincronizar Orthanc» si la lista está vacía.',
    ], 500);
}
