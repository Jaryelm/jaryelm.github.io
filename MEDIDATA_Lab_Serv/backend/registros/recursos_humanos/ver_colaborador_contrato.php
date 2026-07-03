<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/colaborador_extra_lib.php';

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        http_response_code(503);
        exit('Servicio no disponible.');
    }

    $tipo = trim((string) ($_GET['tipo'] ?? ''));
    $refId = (int) ($_GET['ref_id'] ?? 0);

    if (!in_array($tipo, medidata_colab_extra_tipos(), true) || $refId <= 0) {
        http_response_code(400);
        exit('Solicitud no válida.');
    }

    medidata_colab_extra_ensure_table($connect);

    $stmt = $connect->prepare('SELECT contrato_nombre, contrato_pdf FROM rrhh_colaborador_extra WHERE tipo = ? AND ref_id = ? LIMIT 1');
    $stmt->execute([$tipo, $refId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['contrato_pdf'] === null || $row['contrato_pdf'] === '') {
        http_response_code(404);
        exit('Contrato no encontrado.');
    }

    $nombre = $row['contrato_nombre'] ?: ('contrato_' . $refId . '.pdf');
    $nombre = preg_replace('/[^\w\.\-]+/', '_', $nombre);

    // 'inline' para visualizar en el modal; 'attachment' al descargar.
    $disp = (isset($_GET['dl']) && $_GET['dl'] === '1') ? 'attachment' : 'inline';

    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . $disp . '; filename="' . $nombre . '"');
    header('Content-Length: ' . strlen($row['contrato_pdf']));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('X-Content-Type-Options: nosniff');
    echo $row['contrato_pdf'];
} catch (Throwable $e) {
    error_log('ver_colaborador_contrato: ' . $e->getMessage());
    http_response_code(500);
    exit('Error al obtener el contrato.');
}
