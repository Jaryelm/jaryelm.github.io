<?php
/**
 * Descarga / visualización del Manual de Usuario RRHH (PDF).
 */
include_once '../../backend/registros/session_check.php';

$pdfPath = realpath(__DIR__ . '/../../backend/docs/manual_usuario_rrhh.pdf');
if ($pdfPath === false || !is_readable($pdfPath)) {
    http_response_code(404);
    echo 'Manual no disponible. Ejecute: php backend/scripts/generar_manual_rrhh_pdf.php';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="manual_usuario_rrhh_medicasa.pdf"');
header('Content-Length: ' . filesize($pdfPath));
header('Cache-Control: private, max-age=3600');
readfile($pdfPath);
exit;
