<?php
declare(strict_types=1);

/**
 * Visor de documentos del expediente de contratación (hiring_requirements).
 * Solo usuarios autenticados (Admin / RRHH).
 */

include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/rrhh_candidato_workflow_lib.php';

$candidateId = (int) ($_GET['id'] ?? 0);
$docKey = trim((string) ($_GET['doc'] ?? ''));

$docs = medidata_rrhh_expediente_documentos();
if ($candidateId <= 0 || $docKey === '' || !isset($docs[$docKey])) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Documento no válido.';
    exit;
}

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Base de datos de Recursos Humanos no disponible.';
    exit;
}

try {
    medidata_rrhh_workflow_ensure_schema($pdo);
    $col = $docs[$docKey]['column'];

    $stmtCand = $pdo->prepare('SELECT id FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1');
    $stmtCand->execute([$candidateId]);
    if (!$stmtCand->fetchColumn()) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Candidato no encontrado.';
        exit;
    }

    $stmt = $pdo->prepare(
        "SELECT `{$col}` AS doc_blob
         FROM hiring_requirements
         WHERE id_candidate = ? AND deleted = 0
         ORDER BY id DESC
         LIMIT 1"
    );
    $stmt->execute([$candidateId]);
    $blob = $stmt->fetchColumn();

    if ($blob === false || $blob === null || $blob === '') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No hay documento subido.';
        exit;
    }

    if (is_resource($blob)) {
        $blob = stream_get_contents($blob);
    }
    $blob = (string) $blob;
    if ($blob === '') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No hay documento subido.';
        exit;
    }

    // Documentos de staff a veces se guardan como ruta (/uploads/staff/...), no como BLOB.
    $trimmed = trim($blob);
    if (
        strlen($trimmed) < 500
        && (
            preg_match('#^https?://#i', $trimmed)
            || preg_match('#^/uploads/#i', $trimmed)
            || (str_starts_with($trimmed, '/') && !str_starts_with($trimmed, '%PDF'))
        )
    ) {
        if (preg_match('#^https?://#i', $trimmed)) {
            header('Location: ' . $trimmed, true, 302);
            exit;
        }
        $rel = ltrim($trimmed, '/');
        $fullPath = realpath(__DIR__ . '/../../' . $rel);
        $uploadsRoot = realpath(__DIR__ . '/../../uploads');
        if (!$fullPath || !$uploadsRoot || !is_file($fullPath) || strpos($fullPath, $uploadsRoot) !== 0) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Archivo no encontrado.';
            exit;
        }
        $mime = 'application/octet-stream';
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($fullPath);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $docKey) ?: 'documento';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $safeName . '_' . $candidateId . '.' . pathinfo($fullPath, PATHINFO_EXTENSION) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');
        readfile($fullPath);
        exit;
    }

    $isPdf = strncmp($blob, '%PDF', 4) === 0;
    $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $docKey) ?: 'documento';
    $filename = $safeName . '_' . $candidateId;

    if ($isPdf) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
    } else {
        $mime = 'application/octet-stream';
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->buffer($blob);
            if (is_string($detected) && $detected !== '') {
                $mime = $detected;
            }
        }
        $ext = 'bin';
        if (str_contains($mime, 'jpeg')) {
            $ext = 'jpg';
        } elseif (str_contains($mime, 'png')) {
            $ext = 'png';
        } elseif (str_contains($mime, 'gif')) {
            $ext = 'gif';
        } elseif (str_contains($mime, 'webp')) {
            $ext = 'webp';
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $filename . '.' . $ext . '"');
    }

    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    echo $blob;
} catch (Throwable $e) {
    error_log('view_expediente_doc.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Error al obtener el documento.';
}
