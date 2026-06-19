<?php
// Configurar zona horaria para Honduras
date_default_timezone_set('America/Tegucigalpa');

require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
session_start();
header('Content-Type: application/json');

try {
    $user_id = $_SESSION['id'];
    $today = date('Y-m-d');

    // Listados: total de estudios asignados al radiólogo
    $stmt = $connect->prepare("
        SELECT COUNT(*) as listados
        FROM radiology_reports
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $listados = $stmt->fetch(PDO::FETCH_ASSOC)['listados'] ?? 0;

    // Pendientes: estudios con status 'pending' o 'draft'
    $stmt = $connect->prepare("
        SELECT COUNT(*) as pending
        FROM radiology_reports
        WHERE user_id = ?
        AND status IN ('pending', 'draft')
    ");
    $stmt->execute([$user_id]);
    $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;

    // Interpretados hoy: estudios con status 'final' y fecha de hoy
    $stmt = $connect->prepare("
        SELECT COUNT(*) as today
        FROM radiology_reports
        WHERE user_id = ?
        AND status = 'final'
        AND DATE(updated_at) = CURDATE()
    ");
    $stmt->execute([$user_id]);
    $today = $stmt->fetch(PDO::FETCH_ASSOC)['today'] ?? 0;

    // Hallazgos críticos: estudios con is_critical = 1
    $stmt = $connect->prepare("
        SELECT COUNT(*) as critical
        FROM radiology_reports
        WHERE user_id = ?
        AND is_critical = 1
    ");
    $stmt->execute([$user_id]);
    $critical = $stmt->fetch(PDO::FETCH_ASSOC)['critical'] ?? 0;

    // Obtener tiempo promedio por informe
    $stmt = $connect->prepare("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_time
        FROM radiology_reports
        WHERE user_id = ?
        AND status IN ('transcribed', 'reviewed', 'final')
        AND DATE(created_at) = ?
    ");
    $stmt->execute([$user_id, $today]);
    $avg_time = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_time'] ?? 0);

    // Completados globales: estudios en radiology_reports con status 'final' (sin importar la fecha)
    $stmt = $connect->prepare("
        SELECT COUNT(*) as completed_global
        FROM radiology_reports
        WHERE status = 'final'
        AND user_id = ?
    ");
    $stmt->execute([$user_id]);
    $completed_global = $stmt->fetch(PDO::FETCH_ASSOC)['completed_global'] ?? 0;

    echo json_encode([
        'listados' => $listados,
        'pending' => $pending,
        'today' => $today,
        'completed_global' => (int)$completed_global,
        'avgTime' => $avg_time,
        'critical' => $critical
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 