<?php
declare(strict_types=1);

require_once __DIR__ . '/../../backend/bd/Conexion.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('America/Tegucigalpa');

try {
    if (empty($_SESSION['id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $today = date('Y-m-d');

    $stmt = $connect->prepare("
        SELECT
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN status = 'completed' AND DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) AS completed_today,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_global,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress,
            SUM(CASE WHEN status = 'cancelled' AND DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) AS cancelled_today,
            AVG(
                CASE
                    WHEN status = 'completed' AND DATE(updated_at) = CURDATE()
                    THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at)
                END
            ) AS avg_time
        FROM worklist
    ");
    $stmt->execute();
    $wl = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $qStmt = $connect->prepare("
        SELECT
            (COUNT(CASE WHEN image_quality IN ('excellent', 'acceptable') THEN 1 END) * 100.0
            / NULLIF(COUNT(*), 0)) AS quality_percentage
        FROM quality_control
        WHERE DATE(created_at) = ?
    ");
    $qStmt->execute([$today]);
    $quality = $qStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'pending'          => (int) ($wl['pending'] ?? 0),
        'completed_today'  => (int) ($wl['completed_today'] ?? 0),
        'completed_global' => (int) ($wl['completed_global'] ?? 0),
        'in_progress'      => (int) ($wl['in_progress'] ?? 0),
        'cancelled_today'  => (int) ($wl['cancelled_today'] ?? 0),
        'avg_time'         => $wl['avg_time'] !== null ? (int) round((float) $wl['avg_time']) : 0,
        'quality'          => $quality && $quality['quality_percentage'] !== null
            ? (int) round((float) $quality['quality_percentage'])
            : 0,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('get_technician_stats.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
