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
$id_user = $_SESSION['id'] ?? null;

// Recurrencia (serie completa).
$recurrence = isset($_POST['recurrence']) ? trim((string) $_POST['recurrence']) : 'none';
if (!in_array($recurrence, medidata_rrhh_recurrence_options(), true)) {
    $recurrence = 'none';
}
$recurrence_until = null;
if ($recurrence !== 'none' && isset($_POST['recurrence_until'])) {
    $ru = trim((string) $_POST['recurrence_until']);
    if ($ru !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $ru)) {
        $recurrence_until = $ru;
    }
}

if ($title === '' || $start_date === '' || $end_date === '') {
    echo json_encode(['success' => false, 'message' => 'Título y fechas son requeridos.']);
    exit;
}



// FullCalendar all-day logic: add 1 day to end date to ensure it covers the day visually
if ($all_day) {
    $endDt = new DateTime($end_date);
    $endDt->modify('+1 day');
    $end_date = $endDt->format('Y-m-d');
    $start_time = null;
    $end_time = null;
}

try {
    $sql = "INSERT INTO rrhh_custom_events (title, start_date, start_time, end_date, end_time, color, created_by, id_event_type, description, all_day, id_user, is_public, recurrence, recurrence_until) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $title,
        $start_date,
        $start_time,
        $end_date,
        $end_time,
        $color,
        $_SESSION['name'] ?? 'Sistema',
        $id_event_type,
        $description,
        $all_day,
        $id_user,
        $is_public,
        $recurrence,
        $recurrence_until
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
