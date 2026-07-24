<?php
/**
 * Exporta el reporte a Word (.doc). Genera HTML compatible con Word (application/msword),
 * reutilizando exactamente la misma lógica de filtros que la tabla en pantalla.
 * Solo RRHH / Administrador.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/reporte_vacaciones_lib.php';

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    http_response_code(403);
    echo 'Acceso denegado';
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión a BD principal.');
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) throw new Exception('Sin conexión a BD de ausencias.');

    $filtros = [
        'tipo_reporte'    => $_GET['tipo_reporte']    ?? 'solicitudes',
        'user_id'         => $_GET['user_id']         ?? '',
        'id_departamento' => $_GET['id_departamento'] ?? '',
        'estado'          => $_GET['estado']          ?? '',
        'type_id'         => $_GET['type_id']         ?? '',
        'desde'           => $_GET['desde']           ?? '',
        'hasta'           => $_GET['hasta']           ?? '',
    ];

    $rep = medidata_reporte_generar($connect, $connect_hr_leaves, $filtros);

    $esc = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    $fecha = date('d/m/Y H:i');
    $filename = 'reporte_vacaciones_' . date('Ymd_His') . '.doc';

    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>";
    echo "<head><meta charset='utf-8'><title>" . $esc($rep['titulo']) . "</title></head><body>";
    echo "<h2 style='font-family:Arial;'>MEDIDATA — " . $esc($rep['titulo']) . "</h2>";
    echo "<p style='font-family:Arial;font-size:11px;color:#555;'>Generado: " . $esc($fecha) . " &nbsp;|&nbsp; Registros: " . count($rep['rows']) . "</p>";
    echo "<table border='1' cellspacing='0' cellpadding='5' style='border-collapse:collapse;font-family:Arial;font-size:12px;'>";
    echo "<thead><tr style='background:#035c67;color:#fff;'>";
    foreach ($rep['columns'] as $c) {
        echo "<th>" . $esc($c['title']) . "</th>";
    }
    echo "</tr></thead><tbody>";
    foreach ($rep['rows'] as $row) {
        echo "<tr>";
        foreach ($rep['columns'] as $c) {
            $val = $row[$c['data']] ?? '';
            echo "<td>" . $esc($val === '' ? '—' : $val) . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table></body></html>";
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Error al generar el reporte: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
