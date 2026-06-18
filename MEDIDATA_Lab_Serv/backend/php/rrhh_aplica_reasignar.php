<?php
require_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$idAplica = (int) ($_POST['id_aplica'] ?? 0);
$idVacante = (int) ($_POST['id_vacante'] ?? 0);
$usuario = trim((string) ($name ?? 'sistema'));

echo json_encode(
    medidata_rrhh_reasignar_aplica($idAplica, $idVacante, $usuario),
    JSON_UNESCAPED_UNICODE
);
