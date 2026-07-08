<?php
header('Content-Type: application/json');
require_once('../../backend/bd/Conexion.php');
require_once __DIR__ . '/transcription_queue_lib.php';

try {
    medidata_sync_transcription_queue($connect);

    // Pendientes: misma lógica que la tabla de transcriptores
    $stmt = $connect->prepare("
        SELECT COUNT(DISTINCT r.id) AS pending
        FROM radiology_reports r
        INNER JOIN worklist w ON w.study_id = r.study_id
        LEFT JOIN report_transcriptions rt ON r.id = rt.report_id
        WHERE (rt.id IS NULL OR rt.status IN ('pending', 'in_progress'))
          AND (
              r.status IN ('pending_transcription', 'final')
              OR rt.id IS NOT NULL
          )
    ");
    $stmt->execute();
    $pending = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0);

    $stmt = $connect->prepare("
        SELECT COUNT(*) AS today
        FROM report_transcriptions
        WHERE status = 'completed'
          AND DATE(completed_at) = CURDATE()
    ");
    $stmt->execute();
    $todayCount = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['today'] ?? 0);

    $stmt = $connect->prepare("
        SELECT COUNT(*) AS completed_global
        FROM report_transcriptions
        WHERE status = 'completed'
    ");
    $stmt->execute();
    $completedGlobal = (int) ($stmt->fetch(PDO::FETCH_ASSOC)['completed_global'] ?? 0);

    $stmt = $connect->prepare("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) AS avg_time
        FROM report_transcriptions
        WHERE status = 'completed'
          AND completed_at IS NOT NULL
          AND completed_at != '0000-00-00 00:00:00'
          AND completed_at > created_at
          AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $avgTime = (int) round($stmt->fetch(PDO::FETCH_ASSOC)['avg_time'] ?? 0);
    if ($avgTime > 1440) {
        $avgTime = 0;
    }

    $stmt = $connect->prepare("
        SELECT
            COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0) AS accuracy
        FROM report_transcriptions
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $accuracy = (int) round($stmt->fetch(PDO::FETCH_ASSOC)['accuracy'] ?? 0);

    echo json_encode([
        'pending' => $pending,
        'today' => $todayCount,
        'completed_global' => $completedGlobal,
        'avgTime' => $avgTime,
        'accuracy' => $accuracy,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage(),
    ]);
}
