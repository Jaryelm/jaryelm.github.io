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

if ($id <= 0 || $start === '' || $type === '') {
    echo json_encode(['success' => false, 'message' => 'Datos insuficientes.']);
    exit;
}

try {
    // Formato FullCalendar/Moment suele venir como ISO 8601: YYYY-MM-DDTHH:mm:ss
    $dt = new DateTime($start);
    $formattedStart = $dt->format('Y-m-d H:i:s');

    if ($type === 'interview') {
        $date = $dt->format('Y-m-d');
        $time = $dt->format('H:i:s');
        $sql = "UPDATE interviews SET date_interview = ?, time_interview = ?, updated_by = ? WHERE id = ? AND deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date, $time, $_SESSION['name'] ?? 'Sistema', $id]);
    } elseif ($type === 'custom') {
        $date = $dt->format('Y-m-d');
        $time = $dt->format('H:i:s');
        // Para eventos personalizados, actualizamos solo el inicio. 
        // Nota: FullCalendar mantiene la duración si no se cambia el fin, pero aquí actualizamos solo start_date y start_time.
        $sql = "UPDATE rrhh_custom_events SET start_date = ?, start_time = ? WHERE id = ? AND deleted = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$date, $time, $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de evento no editable.']);
        exit;
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Evento reprogramado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o el registro no existe.']);
    }
} catch (Throwable $e) {
    error_log('rrhh_calendar_update: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al actualizar la fecha.']);
}
