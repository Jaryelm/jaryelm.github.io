<?php
/**
 * Exporta TODOS los registros de Compras Ingresadas / Detalladas según filtros (sin paginación).
 */
ob_start();
include_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../php/reporte_datatable_helper.php';

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('No hay conexión a la base de datos.');
    }

    $format = strtolower(trim((string) ($_GET['format'] ?? 'csv')));
    $report = strtolower(trim((string) ($_GET['report'] ?? 'ingresadas')));
    if (!in_array($report, ['ingresadas', 'detalladas'], true)) {
        $report = 'ingresadas';
    }

    $desde = trim((string) ($_GET['fechaDesde'] ?? $_GET['desde'] ?? date('Y-m-01')));
    $hasta = trim((string) ($_GET['fechaHasta'] ?? $_GET['hasta'] ?? date('Y-m-t')));
    $searchValue = trim((string) ($_GET['search'] ?? ''));

    if ($desde === '' || $hasta === '') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(400);
        echo json_encode(['error' => 'Indique fecha Desde y Hasta para exportar.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $headers = medidata_reporte_compras_export_headers($report);
    $titulo = $report === 'detalladas' ? 'Compras Detalladas' : 'Compras Ingresadas';

    if ($format === 'copy') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/plain; charset=UTF-8');

        $lines = [implode("\t", $headers)];
        foreach (medidata_reporte_compras_export_stream($connect, $report, $desde, $hasta, $searchValue) as $row) {
            $lines[] = implode("\t", $row);
        }
        echo implode("\n", $lines);
        exit;
    }

    if ($format === 'print' || $format === 'pdf') {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/html; charset=UTF-8');

        $rows = medidata_reporte_compras_export_fetch_rows($connect, $report, $desde, $hasta, $searchValue);
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($titulo) . '</title>';
        echo '<style>body{font-family:Arial,sans-serif;margin:20px;font-size:12px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #333;padding:6px;text-align:left;} th{background:#035c67;color:white;}</style>';
        echo '</head><body><h1>' . htmlspecialchars($titulo) . '</h1>';
        echo '<p><strong>Período:</strong> ' . htmlspecialchars($desde) . ' — ' . htmlspecialchars($hasta) . '</p>';
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
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }

    $sep = ($format === 'excel') ? ';' : ',';
    $filename = 'compras_' . $report . '_' . $desde . '_' . $hasta . '_' . date('His') . '.csv';

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

    foreach (medidata_reporte_compras_export_stream($connect, $report, $desde, $hasta, $searchValue) as $row) {
        fputcsv($out, $row, $sep);
    }
    fclose($out);
} catch (Throwable $e) {
    error_log('get_reporte_compras_export: ' . $e->getMessage());
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        http_response_code(500);
        echo json_encode(['error' => 'Error al exportar los datos del reporte.'], JSON_UNESCAPED_UNICODE);
    }
}
