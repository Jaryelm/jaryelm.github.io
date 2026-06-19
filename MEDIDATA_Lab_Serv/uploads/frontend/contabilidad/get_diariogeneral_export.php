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
    // Filtros (los mismos que la búsqueda)
    $fechaDesde = $_GET['fechaDesde'] ?? null;
    $fechaHasta = $_GET['fechaHasta'] ?? null;
    $numeroPartida = $_GET['numeroPartida'] ?? null;
    $cuenta = $_GET['cuenta'] ?? null;
    $tipoTransaccion = isset($_GET['tipoTransaccion']) ? trim((string) $_GET['tipoTransaccion']) : '';
    $format = $_GET['format'] ?? 'csv';

    // Construir la consulta base (SIN paginación)
    $query = "SELECT 
                id,
                numero_partida,
                fecha_ocurrencia,
                fecha_registro,
                unidad_servicio,
                cuenta,
                nombre_cuenta,
                descripcion,
                debe,
                haber,
                neto,
                turno,
                usuario,
                referencia,
                tipo_transaccion
              FROM diario_general_transacciones 
              WHERE 1=1";
    
    $params = [];
    $types = [];
    
    if ($fechaDesde || $fechaHasta) {
        $filtroFechas = medidata_diario_sql_filtro_rango_fechas($fechaDesde, $fechaHasta, '', '_exp');
        $query .= $filtroFechas['sql'];
        $params = array_merge($params, $filtroFechas['params']);
        $types = array_merge($types, $filtroFechas['types']);
    }
    
    if ($numeroPartida) {
        $query .= " AND numero_partida = :numeroPartida";
        $params[':numeroPartida'] = $numeroPartida;
        $types[':numeroPartida'] = PDO::PARAM_STR;
    }
    
    if ($cuenta) {
        $query .= " AND cuenta = :cuenta";
        $params[':cuenta'] = $cuenta;
        $types[':cuenta'] = PDO::PARAM_STR;
    }

    if ($tipoTransaccion !== '') {
        $query .= " AND tipo_transaccion = :tipoTransaccion";
        $params[':tipoTransaccion'] = $tipoTransaccion;
        $types[':tipoTransaccion'] = PDO::PARAM_STR;
    }

    $query .= " ORDER BY numero_partida DESC, fecha_ocurrencia DESC, id DESC";
    
    $stmt = $connect->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, $types[$key] ?? PDO::PARAM_STR);
    }
    $stmt->execute();
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
            echo '<p><strong>Período:</strong> ' . ($fechaDesde ?: '...') . ' - ' . ($fechaHasta ?: '...') . '</p>';
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
    $filename = 'diario_general_' . date('Y-m-d_His') . '.' . $ext;
    
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
