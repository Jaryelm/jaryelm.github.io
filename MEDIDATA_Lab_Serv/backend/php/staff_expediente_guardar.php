<?php
/**
 * Subida pública de documentos del colaborador (sin sesión, por token).
 */
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/staff_expediente_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($connect) || !($connect instanceof PDO)) {
    echo json_encode(['success' => false, 'message' => 'Servicio no disponible.']);
    exit;
}

$token = trim((string) ($_POST['token'] ?? ''));
$docKey = trim((string) ($_POST['doc_key'] ?? ''));

echo json_encode(
    medidata_staff_expediente_upload($connect, $token, $docKey, $_FILES['document'] ?? []),
    JSON_UNESCAPED_UNICODE
);
