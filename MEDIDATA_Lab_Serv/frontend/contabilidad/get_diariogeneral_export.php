<?php
/**
 * Exporta TODOS los registros del Diario General según los filtros aplicados
 * (sin paginación - para Copy, CSV, Excel, PDF, Print)
 * Sistema: MEDIDATA
 */
ob_start();
include_once '../../backend/registros/session_check.php';
require_once __DIR__ . '/../../backend/php/diario_tipo_etiqueta.php';
require_once __DIR__ . '/../../backend/php/funciones_diario_general.php';

try {
    $format = $_GET['format'] ?? 'csv';
    $filtros = medidata_diario_normalizar_filtros_request($_GET);

    $resultados = medidata_diario_fetch_filas_export($connect, $filtros);
    $fechaDesde = $filtros['fechaDesde'];
    $fechaHasta = $filtros['fechaHasta'];
    $searchValue = $filtros['searchValue'];
    
    $headers = [
        'Partida #', 'Fecha Ocurrencia', 'Fecha Registro', 'Referencia', 'Tipo',
        'Unidad Servicio', 'Cuenta', 'Nombre', 'Descripción', 'Debe', 'Haber', 'Neto', 'Turno', 'Usuario',
    ];
    
    if ($format === 'copy') {
        ob_end_clean();
        header('Content-Type: text/plain; charset=UTF-8');
        $lines = [implode("\t", $headers)];
        foreach ($resultados as $row) {
            $fechaOcur = date('d/m/Y', strtotime($row['fecha_ocurrencia']));
            $fechaReg = date('d/m/Y H:i', strtotime($row['fecha_registro']));
            $debe = number_format($row['debe'], 2, '.', ',');
            $haber = number_format($row['haber'], 2, '.', ',');
            $neto = number_format($row['neto'], 2, '.', ',');
            $cols = medidata_diario_columnas_cuenta($row['cuenta'] ?? '', $row['nombre_cuenta'] ?? '');
            $lines[] = implode("\t", [
                $row['numero_partida'], $fechaOcur, $fechaReg, $row['referencia'] ?? '',
                medidata_etiqueta_tipo_transaccion($row['tipo_transaccion'] ?? null),
                $row['unidad_servicio'] ?? '', $cols['cuenta'], $cols['nombre_cuenta'],
                $row['descripcion'], 'L. ' . $debe, 'L. ' . $haber, 'L. ' . $neto,
                $row['turno'] ?? '', $row['usuario'] ?? ''
            ]);
        }
        echo implode("\n", $lines);
        exit;
    }
    
    if ($format === 'print') {
        ob_end_clean();
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Diario General - Exportación</title>';
        echo '<style>body{font-family:Arial,sans-serif;margin:20px;} table{width:100%;border-collapse:collapse;} th,td{border:1px solid #333;padding:8px;text-align:left;} th{background:#035c67;color:white;} .text-right{text-align:right;} .partida-header{background:#e3f2fd;font-weight:bold;}</style>';
        echo '</head><body><h1>Diario General</h1>';
        if ($fechaDesde || $fechaHasta) {
            echo '<p><strong>Período (fecha de ocurrencia):</strong> ' . htmlspecialchars($fechaDesde ?: '...') . ' - ' . htmlspecialchars($fechaHasta ?: '...') . '</p>';
        }
        if ($searchValue !== '') {
            echo '<p><strong>Búsqueda:</strong> ' . htmlspecialchars($searchValue) . '</p>';
        }
        echo '<p><strong>Total registros:</strong> ' . count($resultados) . '</p>';
        echo '<table><thead><tr>';
        foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($resultados as $row) {
            $fechaOcur = date('d/m/Y', strtotime($row['fecha_ocurrencia']));
            $fechaReg = date('d/m/Y H:i', strtotime($row['fecha_registro']));
            $debe = number_format($row['debe'], 2, '.', ',');
            $haber = number_format($row['haber'], 2, '.', ',');
            $neto = number_format($row['neto'], 2, '.', ',');
            $cols = medidata_diario_columnas_cuenta($row['cuenta'] ?? '', $row['nombre_cuenta'] ?? '');
            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['numero_partida']) . '</td>';
            echo '<td>' . $fechaOcur . '</td>';
            echo '<td>' . $fechaReg . '</td>';
            echo '<td>' . htmlspecialchars($row['referencia'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars(medidata_etiqueta_tipo_transaccion($row['tipo_transaccion'] ?? null)) . '</td>';
            echo '<td>' . htmlspecialchars($row['unidad_servicio'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($cols['cuenta']) . '</td>';
            echo '<td>' . htmlspecialchars($cols['nombre_cuenta']) . '</td>';
            echo '<td>' . htmlspecialchars($row['descripcion']) . '</td>';
            echo '<td class="text-right">L. ' . $debe . '</td>';
            echo '<td class="text-right">L. ' . $haber . '</td>';
            echo '<td class="text-right">L. ' . $neto . '</td>';
            echo '<td>' . htmlspecialchars($row['turno'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($row['usuario'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }
    
    // CSV o Excel - ambos usan extensión .csv (Excel abre CSV sin advertencias de formato)
    $sep = ($format === 'excel') ? ';' : ',';  // ; para Excel en español
    $ext = 'csv';
    $periodo = ($fechaDesde && $fechaHasta) ? ($fechaDesde . '_' . $fechaHasta) : 'completo';
    $filename = 'diario_general_' . $periodo . '_' . date('His') . '.' . $ext;
    
    ob_end_clean();
    header('Content-Type: application/octet-stream; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    
    $out = fopen('php://output', 'w');
    if ($format === 'excel') {
        fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 para Excel
    }
    fputcsv($out, $headers, $sep);
    
    foreach ($resultados as $row) {
        $fechaOcur = date('d/m/Y', strtotime($row['fecha_ocurrencia']));
        $fechaReg = date('d/m/Y H:i', strtotime($row['fecha_registro']));
        $debe = number_format($row['debe'], 2, '.', ',');
        $haber = number_format($row['haber'], 2, '.', ',');
        $neto = number_format($row['neto'], 2, '.', ',');
        $cols = medidata_diario_columnas_cuenta($row['cuenta'] ?? '', $row['nombre_cuenta'] ?? '');
        $rowData = [
            $row['numero_partida'],
            $fechaOcur,
            $fechaReg,
            $row['referencia'] ?? '',
            medidata_etiqueta_tipo_transaccion($row['tipo_transaccion'] ?? null),
            $row['unidad_servicio'] ?? '',
            $cols['cuenta'],
            $cols['nombre_cuenta'],
            $row['descripcion'],
            'L. ' . $debe,
            'L. ' . $haber,
            'L. ' . $neto,
            $row['turno'] ?? '',
            $row['usuario'] ?? ''
        ];
        fputcsv($out, $rowData, $sep);
    }
    fclose($out);
    
} catch (Exception $e) {
    error_log("Error en get_diariogeneral_export.php: " . $e->getMessage());
    if (!headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Error al exportar los datos']);
    }
}
