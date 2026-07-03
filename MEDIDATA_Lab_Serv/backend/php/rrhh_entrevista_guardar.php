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
$dateInterview = trim((string) ($_POST['date_interview'] ?? ''));
$timeInterview = trim((string) ($_POST['time_interview'] ?? ''));

$questions = medidata_rrhh_interview_questions_default();
$answers = [];
foreach ($questions as $key => $label) {
    $answers[$key] = trim((string) ($_POST[$key] ?? ''));
}

echo json_encode(
    medidata_rrhh_save_interview_form($candidateId, $answers, $usuario, $dateInterview, $timeInterview),
    JSON_UNESCAPED_UNICODE
);
