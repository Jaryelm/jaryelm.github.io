<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$idAplica = (int) ($_POST['id_aplica'] ?? 0);
$motivo = trim((string) ($_POST['motivo'] ?? ''));
$usuario = trim((string) ($name ?? 'sistema'));

echo json_encode(
    medidata_rrhh_descartar_aplica($idAplica, $usuario, $motivo),
    JSON_UNESCAPED_UNICODE
);
