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

$result = medidata_rrhh_cambiar_estado_candidato($candidateId, $nuevoEstado, $usuario, $observaciones);

if ($result['success'] && $nuevoEstado === 'Contratado') {
    $isUsr = strpos((string) ($_SERVER['HTTP_REFERER'] ?? ''), '_usr.php') !== false
        || strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '_usr') !== false;
    $suffix = $isUsr ? '_usr' : '';
    $result['redirect_url'] = 'lista_colaboradores' . $suffix . '.php';
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
