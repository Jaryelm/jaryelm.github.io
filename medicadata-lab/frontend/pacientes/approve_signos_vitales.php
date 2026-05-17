<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Tegucigalpa');

require_once __DIR__ . '/../../backend/bd/Conexion.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['rol'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión no válida. Reinicie sesión e intente de nuevo.']);
    exit;
}

if (($_SESSION['rol'] ?? '') !== 'Administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado para aprobar signos vitales.']);
    exit;
}

$signoId = intval($_POST['signo_id'] ?? 0);
$idpa = intval($_POST['idpa'] ?? 0);
$approverId = (int) $_SESSION['id'];

if ($signoId < 1 || $idpa < 1 || $approverId < 1) {
    echo json_encode(['error' => 'Datos inválidos.']);
    exit;
}

try {
    $stmtRow = $connect->prepare(
        'SELECT id, reviews_by, reviewed_by_user_id, reviewed_at FROM signos_vitales WHERE id = ? AND idpa = ? LIMIT 1'
    );
    $stmtRow->execute([$signoId, $idpa]);
    $row = $stmtRow->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['error' => 'El registro no existe o no corresponde a este paciente.']);
        exit;
    }

    if (!empty($row['reviewed_by_user_id']) || !empty($row['reviewed_at'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Este registro ya fue aprobado.',
            'already' => true,
        ]);
        exit;
    }

    $reviewsByManual = isset($row['reviews_by']) ? trim((string) $row['reviews_by']) : '';
    if ($reviewsByManual !== '' && $reviewsByManual !== '-') {
        echo json_encode([
            'error' => 'Este registro ya tiene revisor registrado manualmente.',
        ]);
        exit;
    }

    $stmtName = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmtName->execute([$approverId]);
    $reviewerName = $stmtName->fetchColumn();

    if ($reviewerName === false || $reviewerName === null || trim((string) $reviewerName) === '') {
        echo json_encode(['error' => 'No se pudo obtener el nombre del usuario actual.']);
        exit;
    }

    $upd = $connect->prepare(
        'UPDATE signos_vitales SET reviews_by = :reviews_by, reviewed_by_user_id = :rid,
         reviewed_at = NOW() WHERE id = :sid AND idpa = :idpa AND reviewed_at IS NULL'
    );
    $upd->execute([
        ':reviews_by' => trim((string) $reviewerName),
        ':rid' => $approverId,
        ':sid' => $signoId,
        ':idpa' => $idpa,
    ]);

    if ($upd->rowCount() < 1) {
        echo json_encode(['error' => 'No se pudo actualizar el registro (posible cambio concurrente).']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registro de signos vitales aprobado correctamente.',
    ]);
} catch (PDOException $e) {
    error_log('approve_signos_vitales: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al guardar la aprobación. Verifique si la migración SQL se aplicó correctamente.']);
}
