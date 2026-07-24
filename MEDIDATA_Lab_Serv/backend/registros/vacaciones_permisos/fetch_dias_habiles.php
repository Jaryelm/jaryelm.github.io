<?php
/**
 * Devuelve la cantidad de días HÁBILES entre dos fechas para el usuario en sesión,
 * excluyendo los días que no labora según su horario activo y los feriados.
 * Usado por el formulario de solicitud para el cálculo automático (vista previa).
 */
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
require_once __DIR__ . '/../../php/absence_workdays_lib.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['id'])) {
        throw new Exception('Sesión no válida.');
    }

    $user_id    = (int) $_SESSION['id'];
    $start_date = $_GET['start_date'] ?? $_POST['start_date'] ?? null;
    $end_date   = $_GET['end_date']   ?? $_POST['end_date']   ?? null;

    $re = '/^\d{4}-\d{2}-\d{2}$/';
    if (!$start_date || !$end_date || !preg_match($re, $start_date) || !preg_match($re, $end_date)) {
        throw new Exception('Fechas inválidas.');
    }
    if ($end_date < $start_date) {
        echo json_encode(['days' => 0, 'has_schedule' => false, 'holidays' => [], 'nonworking' => []]);
        exit;
    }

    if (!isset($connect) || !$connect) {
        throw new Exception('Sin conexión a BD.');
    }

    $result = medidata_absence_count_working_days(
        $connect,
        $connect_hr_leaves ?? null,
        $user_id,
        $start_date,
        $end_date
    );

    echo json_encode($result);
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
