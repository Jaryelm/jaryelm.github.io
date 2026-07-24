<?php
/**
 * Elimina un feriado de medic9ue_hr_leaves.hr_holiday_calendar.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/audit_lib.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

try {
    global $connect;
    if (!$connect) throw new Exception("No db connection");
    $pdo = $connect;

    $holiday_id = $_POST['holiday_id'] ?? '';
    if (empty($holiday_id)) {
        echo json_encode(['success' => false, 'message' => 'ID no proporcionado.']);
        exit;
    }

    $oldStmt = $pdo->prepare("SELECT `date`, description FROM medic9ue_hr_leaves.hr_holiday_calendar WHERE holiday_id = ?");
    $oldStmt->execute([$holiday_id]);
    $old_val = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $stmt = $pdo->prepare("DELETE FROM medic9ue_hr_leaves.hr_holiday_calendar WHERE holiday_id = ?");
    $stmt->execute([$holiday_id]);

    medidata_audit_log($pdo, (int) ($_SESSION['id'] ?? 0), 'DELETE_HOLIDAY', 'hr_holiday_calendar', $holiday_id, $old_val, null);

    echo json_encode(['success' => true, 'message' => 'Feriado eliminado correctamente.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
