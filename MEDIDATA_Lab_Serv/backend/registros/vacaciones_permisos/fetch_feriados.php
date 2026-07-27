<?php
/**
 * Lista los feriados registrados (fuente única: hr_holiday_calendar).
 * Estos feriados se reflejan en los calendarios que consumen fetch_calendario.php.
 */
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

try {
    global $connect;
    if (!$connect) throw new Exception("No db connection");
    $pdo = $connect;

    $stmt = $pdo->query("
        SELECT holiday_id, `date`, description
        FROM hr_holiday_calendar
        ORDER BY `date` ASC
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
