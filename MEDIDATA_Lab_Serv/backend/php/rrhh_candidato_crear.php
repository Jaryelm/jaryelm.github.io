<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$usuario = trim((string) ($name ?? $username ?? 'sistema'));
$result = medidata_rrhh_crear_candidato_manual(
    (int) ($_POST['id_vacante'] ?? 0),
    trim((string) ($_POST['fullname'] ?? '')),
    trim((string) ($_POST['dni'] ?? '')),
    trim((string) ($_POST['phonenumber'] ?? '')),
    trim((string) ($_POST['email'] ?? '')),
    $usuario,
    trim((string) ($_POST['referral_source'] ?? 'Captación manual'))
);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
