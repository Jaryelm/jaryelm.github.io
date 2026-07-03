<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/rrhh_candidato_workflow_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$candidateId = (int) ($_POST['candidate_id'] ?? 0);
$usuario = trim((string) ($name ?? 'sistema'));
$tests = isset($_POST['tests']) && is_array($_POST['tests']) ? $_POST['tests'] : [];
$score = trim((string) ($_POST['score'] ?? ''));
$notes = trim((string) ($_POST['notes'] ?? ''));

$file = null;
if (!empty($_FILES['document']['name'])) {
    $file = $_FILES['document'];
}

echo json_encode(
    medidata_rrhh_save_psychometric_form($candidateId, $tests, $file, $usuario, $score, $notes),
    JSON_UNESCAPED_UNICODE
);
