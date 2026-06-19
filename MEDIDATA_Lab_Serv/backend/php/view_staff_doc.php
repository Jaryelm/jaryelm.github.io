<?php
include_once '../registros/session_check.php';
require_once 'staff_colaborador_bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$doc = $_GET['doc'] ?? '';

if ($id <= 0 || !in_array($doc, ['contrato', 'solicitud', 'psicometricas'])) {
    die('Documento no válido.');
}

$column = 'url_' . $doc;

try {
    $stmt = $connect->prepare("SELECT $column FROM staff_administrative WHERE idadm = ? LIMIT 1");
    $stmt->execute([$id]);
    $blob = $stmt->fetchColumn();

    if (empty($blob)) {
        die('No hay documento subido.');
    }

    // Attempt to guess if it's a PDF by looking at the first 4 bytes
    $isPdf = (strpos($blob, '%PDF') === 0);
    
    if ($isPdf) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $doc . '_' . $id . '.pdf"');
    } else {
        // Fallback for images (JPEG/PNG)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($blob) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $doc . '_' . $id . '"');
    }

    echo $blob;
} catch (Exception $e) {
    die('Error al obtener el documento.');
}
