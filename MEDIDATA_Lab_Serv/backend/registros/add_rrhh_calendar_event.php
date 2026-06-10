<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Base de datos RRHH no disponible']);
    exit;
}

$title = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
$start = isset($_POST['start']) ? trim((string) $_POST['start']) : '';
$end = isset($_POST['end']) ? trim((string) $_POST['end']) : '';
$color = isset($_POST['color']) ? trim((string) $_POST['color']) : '#035c67';

if ($title === '' || $start === '' || $end === '') {
    echo json_encode(['success' => false, 'message' => 'Título y fechas son requeridos.']);
    exit;
}

try {
    $sql = "INSERT INTO rrhh_custom_events (title, start_datetime, end_datetime, color, created_by) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        date('Y-m-d H:i:s', strtotime($start)),
        date('Y-m-d H:i:s', strtotime($end)),
        $color,
        $_SESSION['name'] ?? 'Sistema'
    ]);

    if ($stmt->rowCount() > 0) {
        $id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Evento creado exitosamente.', 'id' => $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo crear el evento.']);
    }
} catch (Throwable $e) {
    error_log('add_rrhh_calendar_event: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al crear el evento.']);
}
