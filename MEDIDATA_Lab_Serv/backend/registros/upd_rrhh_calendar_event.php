<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Base de datos RRHH no disponible']);
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$title = isset($_POST['title']) ? trim((string) $_POST['title']) : '';
$start = isset($_POST['start']) ? trim((string) $_POST['start']) : '';
$end = isset($_POST['end']) ? trim((string) $_POST['end']) : '';
$color = isset($_POST['color']) ? trim((string) $_POST['color']) : '#035c67';

if ($id <= 0 || $title === '' || $start === '' || $end === '') {
    echo json_encode(['success' => false, 'message' => 'ID, título y fechas son requeridos.']);
    exit;
}

try {
    $sql = "UPDATE rrhh_custom_events SET title = ?, start_datetime = ?, end_datetime = ?, color = ? WHERE id = ? AND deleted = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        date('Y-m-d H:i:s', strtotime($start)),
        date('Y-m-d H:i:s', strtotime($end)),
        $color,
        $id
    ]);

    if ($stmt->rowCount() >= 0) { // >= 0 because they might save without changing anything
        echo json_encode(['success' => true, 'message' => 'Evento actualizado exitosamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el evento.']);
    }
} catch (Throwable $e) {
    error_log('upd_rrhh_calendar_event: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar el evento.']);
}
