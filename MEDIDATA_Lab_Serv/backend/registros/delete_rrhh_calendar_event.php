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
$type = isset($_POST['type']) ? trim((string) $_POST['type']) : '';

if ($id <= 0 || $type === '') {
    echo json_encode(['success' => false, 'message' => 'ID y tipo de evento requeridos.']);
    exit;
}

try {
    if ($type === 'custom') {
        $sql = "UPDATE rrhh_custom_events SET deleted = 1 WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
    } elseif ($type === 'interview') {
        // Para entrevistas, el borrado lógico es marcar deleted = 1 en la tabla interviews
        $sql = "UPDATE interviews SET deleted = 1, updated_by = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['name'] ?? 'Sistema', $id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Este tipo de evento no puede ser eliminado directamente desde la agenda.']);
        exit;
    }

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Evento cancelado/eliminado correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el evento o ya estaba eliminado.']);
    }
} catch (Throwable $e) {
    error_log('delete_rrhh_calendar_event: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno al eliminar el evento.']);
}
