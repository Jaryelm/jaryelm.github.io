<?php
/**
 * Exporta TODOS los registros de Detalle Pago según filtros (sin paginación).
 * Misma ubicación que get_diariogeneral_export.php (probado en producción).
 */
ob_start();
include_once '../../backend/registros/session_check.php';
require_once __DIR__ . '/../../backend/php/reporte_datatable_helper.php';

try {
    $format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
    $desde = trim((string) ($_GET['fechaDesde'] ?? $_GET['desde'] ?? ''));
    $hasta = trim((string) ($_GET['fechaHasta'] ?? $_GET['hasta'] ?? ''));
    $searchValue = trim((string) ($_GET['search'] ?? ''));

    if ($desde !== '' xor $hasta !== '') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode(['error' => 'Para exportar un período, indique fecha Desde y Hasta.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $headers = medidata_reporte_detalle_pago_export_headers();

    if ($format === 'copy') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/plain; charset=UTF-8');

        $rows = medidata_reporte_detalle_pago_fetch_rows($connect, $desde, $hasta, $searchValue);
        $lines = [implode("\t", $headers)];
        foreach ($rows as $row) {
            $lines[] = implode("\t", medidata_reporte_detalle_pago_row_to_export($row));
        }
        echo implode("\n", $lines);
        exit;
    }

    if ($format === 'print' || $format === 'pdf') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');

        $rows = medidata_reporte_detalle_pago_fetch_rows($connect, $desde, $hasta, $searchValue);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Detalle Pago - Exportación</title>';
        echo '<style>body{font-family:Arial,sans-serif;margin:20px;font-size:12px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #333;padding:6px;text-align:left;} th{background:#035c67;color:white;} .text-right{text-align:right;}</style>';
        echo '</head><body><h1>Detalle Pago</h1>';
        if ($desde !== '' && $hasta !== '') {
            echo '<p><strong>Período:</strong> ' . htmlspecialchars($desde) . ' — ' . htmlspecialchars($hasta) . '</p>';
        }
        if ($searchValue !== '') {
            echo '<p><strong>Búsqueda:</strong> ' . htmlspecialchars($searchValue) . '</p>';
        }
        echo '<p><strong>Total registros:</strong> ' . count($rows) . '</p>';
        echo '<table><thead><tr>';
        foreach ($headers as $h) {
            echo '<th>' . htmlspecialchars($h) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach (medidata_reporte_detalle_pago_row_to_export($row) as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }

    $sep = ($format === 'excel') ? ';' : ',';
    $periodo = ($desde !== '' && $hasta !== '') ? ($desde . '_' . $hasta) : 'completo';
    $filename = 'detalle_pago_' . $periodo . '_' . date('His') . '.csv';

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/octet-stream; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        throw new RuntimeException('No se pudo abrir la salida de exportación.');
    }
    if ($format === 'excel') {
        fwrite($out, "\xEF\xBB\xBF");
    }
    fputcsv($out, $headers, $sep);

    foreach (medidata_reporte_detalle_pago_stream_rows($connect, $desde, $hasta, $searchValue) as $row) {
        fputcsv($out, medidata_reporte_detalle_pago_row_to_export($row), $sep);
    }
    fclose($out);
} catch (Throwable $e) {
    error_log('get_reporte_detalle_pago_export: ' . $e->getMessage());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['error' => 'Error al exportar los datos del reporte.'], JSON_UNESCAPED_UNICODE);
    }
}
