<?php
// Sirve un documento de respaldo de una solicitud, con control de acceso:
// el colaborador solo ve sus propios adjuntos; RRHH/Admin ve todos.
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

if (!isset($_SESSION['id'])) {
    http_response_code(403);
    exit('No autenticado');
}

$attachment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$attachment_id) {
    http_response_code(400);
    exit('Falta el identificador del documento');
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión');

    $stmt = $connect->prepare("
        SELECT a.original_filename, a.file_path, a.format, r.user_id
        FROM hr_absence_attachments a
        JOIN hr_absence_requests r ON a.request_id = r.request_id
        WHERE a.attachment_id = ?
    ");
    $stmt->execute([$attachment_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$doc) {
        http_response_code(404);
        exit('Documento no encontrado');
    }

    $rol    = $_SESSION['rol'] ?? '';
    $isPriv = in_array($rol, ['Administrador', 'Recursos_Humanos'], true);
    if (!$isPriv && (int) $doc['user_id'] !== (int) $_SESSION['id']) {
        http_response_code(403);
        exit('No autorizado');
    }

    // Resolver la ruta física de forma segura (solo basename dentro del dir de uploads)
    $path = __DIR__ . '/uploads/' . basename((string) $doc['file_path']);
    if (!is_file($path)) {
        http_response_code(404);
        exit('Archivo no disponible');
    }

    // Determinar el MIME
    $mime = (string) ($doc['format'] ?? '');
    if (strpos($mime, '/') === false) {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        $mime = $map[$ext] ?? 'application/octet-stream';
    }

    // PDF e imágenes se muestran inline; el resto se descarga
    $disposition = (strpos($mime, 'application/pdf') === 0 || strpos($mime, 'image/') === 0) ? 'inline' : 'attachment';

    $safe_filename = preg_replace('/[\r\n"]/', '', (string) $doc['original_filename']);

    header('Content-Type: ' . $mime);
    header('Content-Disposition: ' . $disposition . '; filename="' . $safe_filename . '"');
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    exit('Error al recuperar el documento');
}
