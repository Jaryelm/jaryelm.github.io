<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../registros/postulaciones_guard.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$inline = isset($_GET['view']) && (string) $_GET['view'] === 'inline';
$forceDownload = isset($_GET['download']) && (string) $_GET['download'] === '1';

if ($id <= 0) {
    http_response_code(400);
    echo 'ID inválido.';
    exit;
}

$pdo = medidata_postulaciones_pdo();
if (!$pdo) {
    http_response_code(503);
    echo 'Base de datos de postulaciones no disponible.';
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT cv FROM aplica WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('download_cv: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error al consultar el registro.';
    exit;
}

if (!$row) {
    http_response_code(404);
    echo 'Registro no encontrado.';
    exit;
}

$cvRaw = $row['cv'] ?? '';
if ($cvRaw === '' || $cvRaw === null) {
    http_response_code(404);
    echo 'No se encontró el nombre del archivo en la base de datos.';
    exit;
}

$filePath = medidata_postulaciones_resolver_ruta_cv($cvRaw);
if ($filePath === null || !is_readable($filePath)) {
    http_response_code(404);
    echo 'El archivo no existe en el servidor.';
    exit;
}

$downloadName = basename($filePath);
$mime = medidata_postulaciones_cv_mime_type($filePath);

header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
if ($inline && !$forceDownload) {
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
}
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
