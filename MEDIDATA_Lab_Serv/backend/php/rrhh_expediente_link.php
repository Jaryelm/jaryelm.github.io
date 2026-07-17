<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/rrhh_candidato_workflow_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$candidateId = (int) ($_POST['candidate_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? 'link'));
$usuario = trim((string) ($name ?? 'sistema'));

$result = medidata_rrhh_expediente_issue_link($candidateId, $usuario, true);
if (!$result['success']) {
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

$estado = medidata_rrhh_expediente_estado($candidateId);

if ($action === 'send_email') {
    $email = trim((string) ($result['email'] ?? ''));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'El candidato no tiene correo válido. Copie el enlace manualmente.',
            'url' => $result['url'],
            'estado' => $estado,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nombre = (string) ($result['candidate_name'] ?? 'Candidato/a');
    $url = (string) $result['url'];
    $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    $nombreEsc = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

    $mail = medidata_rrhh_send_notification_email(
        $email,
        'Hospital MEDICASA — Expediente de contratación',
        "<div style=\"font-family:Arial,sans-serif;color:#333;line-height:1.5;\">"
            . "<p>Estimado/a <strong>{$nombreEsc}</strong>,</p>"
            . "<p>Le solicitamos cargar los documentos de su <strong>expediente de contratación</strong> (solo archivos PDF).</p>"
            . "<p style=\"text-align:center;margin:24px 0;\">"
            . "<a href=\"{$urlEsc}\" style=\"background:#06adbf;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;\">Subir documentos</a>"
            . "</p>"
            . "<p>Enlace directo:<br><span style=\"color:#035c67;word-break:break-all;\">{$urlEsc}</span></p>"
            . "<p>Atentamente,<br><strong>Talento Humano</strong><br>Hospital MEDICASA</p></div>",
        "Estimado/a {$nombre},\n\nSuba sus documentos de contratación (PDF) en:\n{$url}\n\nTalento Humano — Hospital MEDICASA",
        $nombre
    );

    echo json_encode([
        'success' => $mail['success'],
        'message' => $mail['message'],
        'url' => $url,
        'email' => $email,
        'estado' => $estado,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Enlace de expediente generado.',
    'url' => $result['url'],
    'email' => $result['email'] ?? '',
    'estado' => $estado,
], JSON_UNESCAPED_UNICODE);
