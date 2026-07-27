<?php
/**
 * Lista las incapacidades (ausencias de categoría Medical_Leave) del usuario en sesión.
 * Alimenta la vista "apartado específico" de incapacidades.
 */
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

try {
    if (!isset($connect) || !$connect) throw new Exception("Sin conexión a BD.");
    $user_id = (int) ($_SESSION['id'] ?? 0);

    $stmt = $connect->prepare("
        SELECT r.request_id, r.start_date, r.end_date, r.days_amount, r.request_status,
               r.issuing_institution, r.medical_leave_number, t.name AS type_name
        FROM hr_absence_requests r
        JOIN hr_absence_types t ON r.type_id = t.type_id
        WHERE r.user_id = ? AND t.category = 'Medical_Leave'
        ORDER BY r.start_date DESC
    ");
    $stmt->execute([$user_id]);

    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
