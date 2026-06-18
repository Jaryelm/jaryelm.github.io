<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/colaborador_extra_lib.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('Conexión a base de datos no disponible.');
    }

    $tipo = trim((string) ($_POST['tipo'] ?? ''));
    $refId = (int) ($_POST['ref_id'] ?? 0);

    if (!in_array($tipo, medidata_colab_extra_tipos(), true) || $refId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Colaborador no válido.']);
        exit;
    }

    if (!isset($_FILES['contrato']) || $_FILES['contrato']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No se recibió el archivo (puede exceder el límite del servidor).']);
        exit;
    }

    $file = $_FILES['contrato'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos PDF.']);
        exit;
    }
    if ($file['size'] > 15 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El PDF no puede superar 15 MB.']);
        exit;
    }

    $binary = file_get_contents($file['tmp_name']);
    if ($binary === false || $binary === '') {
        throw new RuntimeException('No se pudo leer el archivo subido.');
    }

    // Validar que realmente sea PDF por contenido
    if (strncmp($binary, '%PDF', 4) !== 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'El archivo no es un PDF válido.']);
        exit;
    }

    $nombre = basename((string) $file['name']);
    $ok = medidata_colab_extra_save_contrato($connect, $tipo, $refId, $binary, $nombre);

    echo json_encode(['success' => (bool) $ok, 'nombre' => $nombre]);
} catch (Throwable $e) {
    error_log('upload_colaborador_contrato: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al subir el contrato.']);
}
