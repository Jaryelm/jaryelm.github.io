<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_json_require();

try {
    $sql = "SELECT id, name, deleted 
            FROM schedules 
            ORDER BY name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    error_log('tabla_horarios: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar horarios', 'message' => $e->getMessage()]);
}
