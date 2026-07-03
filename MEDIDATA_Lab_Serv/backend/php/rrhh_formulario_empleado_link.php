<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/rrhh_employee_form_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$candidateId = (int) ($_POST['candidate_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? 'link'));
$usuario = trim((string) ($name ?? 'sistema'));

$result = medidata_rrhh_employee_form_issue_link($candidateId, $usuario, true);
if (!$result['success']) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'send_email') {
    $email = trim((string) ($result['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'El candidato no tiene correo válido. Copie el enlace manualmente.',
            'url' => $result['url'],
            'email' => '',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $mail = medidata_rrhh_employee_form_send_email(
        (string) $result['email'],
        (string) ($result['candidate_name'] ?? ''),
        (string) $result['url']
    );
    echo json_encode([
        'success' => $mail['success'],
        'message' => $mail['message'],
        'url' => $result['url'],
        'email' => $result['email'],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Enlace generado.',
    'url' => $result['url'],
    'email' => $result['email'],
], JSON_UNESCAPED_UNICODE);
