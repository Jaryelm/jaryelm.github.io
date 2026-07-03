<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$candidateId = (int) ($_POST['candidate_id'] ?? 0);
$nuevoEstado = trim((string) ($_POST['status'] ?? ''));
$observaciones = trim((string) ($_POST['observaciones'] ?? ''));
$usuario = trim((string) ($name ?? 'sistema'));

echo json_encode(
    medidata_rrhh_cambiar_estado_candidato($candidateId, $nuevoEstado, $usuario, $observaciones),
    JSON_UNESCAPED_UNICODE
);
