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
$start_date = isset($_POST['start_date']) ? trim((string) $_POST['start_date']) : '';
$start_time = isset($_POST['start_time']) && trim($_POST['start_time']) !== '' ? trim($_POST['start_time']) : null;
$end_date = isset($_POST['end_date']) ? trim((string) $_POST['end_date']) : '';
$end_time = isset($_POST['end_time']) && trim($_POST['end_time']) !== '' ? trim($_POST['end_time']) : null;
$color = isset($_POST['color']) ? trim((string) $_POST['color']) : '#035c67';

// New fields
$id_event_type = isset($_POST['id_event_type']) && (int)$_POST['id_event_type'] > 0 ? (int)$_POST['id_event_type'] : null;
$description = isset($_POST['description']) ? trim((string) $_POST['description']) : null;
$all_day = isset($_POST['all_day']) && $_POST['all_day'] === 'true' ? 1 : 0;
$is_public = isset($_POST['is_public']) && $_POST['is_public'] === 'true' ? 1 : 0;

if ($id <= 0 || $title === '' || $start_date === '' || $end_date === '') {
    echo json_encode(['success' => false, 'message' => 'ID, título y fechas son requeridos.']);
    exit;
}

if ($all_day) {
    $endDt = new DateTime($end_date);
    $endDt->modify('+1 day');
    $end_date = $endDt->format('Y-m-d');
    $start_time = null;
    $end_time = null;
}

try {
    $sql = "UPDATE rrhh_custom_events SET title = ?, start_date = ?, start_time = ?, end_date = ?, end_time = ?, color = ?, id_event_type = ?, description = ?, all_day = ?, is_public = ? WHERE id = ? AND deleted = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        $start_date,
        $start_time,
        $end_date,
        $end_time,
        $color,
        $id_event_type,
        $description,
        $all_day,
        $is_public,
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
