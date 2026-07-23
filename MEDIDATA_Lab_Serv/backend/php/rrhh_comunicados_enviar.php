<?php
/**
 * API JSON: envío de comunicados RRHH (SMTP perfil rrhh).
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/rrhh_comunicados_lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($connect) || !($connect instanceof PDO)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Base de datos no disponible.']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = [];
if (is_string($raw) && $raw !== '') {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if ($payload === []) {
    $payload = $_POST;
}

$subject = trim((string) ($payload['subject'] ?? ''));
$message = trim((string) ($payload['message'] ?? ''));
$userIds = $payload['user_ids'] ?? [];
if (!is_array($userIds)) {
    $userIds = [];
}

$manual = $payload['manual_emails'] ?? [];
if (is_string($manual)) {
    $manualRaw = $manual;
} elseif (is_array($manual)) {
    $manualRaw = implode("\n", array_map('strval', $manual));
} else {
    $manualRaw = '';
}

try {
    $resolved = medidata_rrhh_comunicados_resolve_recipients($connect, $userIds, $manualRaw);

    if ($resolved['recipients'] === []) {
        if ($resolved['invalid_manual'] !== []) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay destinatarios válidos. Revise los correos manuales: ' . implode(', ', $resolved['invalid_manual']),
                'invalid_manual' => $resolved['invalid_manual'],
                'skipped_no_email' => $resolved['skipped_no_email'],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $hint = $resolved['skipped_no_email'] !== []
            ? ' Los usuarios seleccionados no tienen correo registrado en MEDIDATA; agréguelos manualmente.'
            : '';
        echo json_encode([
            'success' => false,
            'message' => 'No hay destinatarios válidos.' . $hint,
            'skipped_no_email' => $resolved['skipped_no_email'],
            'invalid_manual' => $resolved['invalid_manual'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = medidata_rrhh_comunicados_send_bulk(
        $resolved['recipients'],
        $subject,
        $message
    );
    $result['skipped_no_email'] = $resolved['skipped_no_email'];
    $result['invalid_manual'] = $resolved['invalid_manual'];
    $result['preview_count'] = count($resolved['recipients']);

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('rrhh_comunicados_enviar: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno al enviar comunicados. Intente de nuevo.',
    ], JSON_UNESCAPED_UNICODE);
}
