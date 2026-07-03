<?php
require_once __DIR__ . '/rrhh_candidato_workflow_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$token = trim((string) ($_POST['token'] ?? ''));
$docKey = trim((string) ($_POST['doc_key'] ?? ''));

echo json_encode(
    medidata_rrhh_expediente_upload_pdf($token, $docKey, $_FILES['document'] ?? []),
    JSON_UNESCAPED_UNICODE
);
