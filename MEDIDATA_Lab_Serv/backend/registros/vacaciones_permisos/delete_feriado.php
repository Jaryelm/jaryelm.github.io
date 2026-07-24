<?php
/**
 * Elimina un feriado de medic9ue_hr_leaves.hr_holiday_calendar.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
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

    $stmt = $pdo->prepare("DELETE FROM medic9ue_hr_leaves.hr_holiday_calendar WHERE holiday_id = ?");
    $stmt->execute([$holiday_id]);

    echo json_encode(['success' => true, 'message' => 'Feriado eliminado correctamente.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Internal error: ' . $e->getMessage()]);
}
