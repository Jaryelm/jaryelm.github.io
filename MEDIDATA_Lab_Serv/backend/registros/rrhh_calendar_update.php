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
$start = isset($_POST['start']) ? trim((string) $_POST['start']) : '';
$type = isset($_POST['type']) ? trim((string) $_POST['type']) : '';

if ($id <= 0 || $start === '' || $type !== 'interview') {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes o tipo de evento no editable.']);
    exit;
}

try {
    // Formato FullCalendar/Moment suele venir como ISO 8601: YYYY-MM-DDTHH:mm:ss
    $dt = new DateTime($start);
    $date = $dt->format('Y-m-d');
    $time = $dt->format('H:i:s');

    $sql = "UPDATE interviews SET date_interview = ?, time_interview = ?, updated_by = ? WHERE id = ? AND deleted = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date, $time, $_SESSION['name'] ?? 'Sistema', $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Entrevista reprogramada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o la entrevista no existe.']);
    }
} catch (Throwable $e) {
    error_log('rrhh_calendar_update: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar la fecha.']);
}
