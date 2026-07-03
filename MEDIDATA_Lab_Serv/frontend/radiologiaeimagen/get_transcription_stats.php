<?php
header('Content-Type: application/json');
require_once('../../backend/bd/Conexion.php');

try {
    $today = date('Y-m-d');

    // Pendientes: todos los registros en report_transcriptions con status 'pending' o 'in_progress'
    $stmt = $connect->prepare("
        SELECT COUNT(*) as pending
        FROM report_transcriptions
        WHERE status IN ('pending', 'in_progress')
    ");
    $stmt->execute();
    $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;

    // Completados hoy: registros con status 'completed' y fecha de hoy
    $stmt = $connect->prepare("
        SELECT COUNT(*) as today
        FROM report_transcriptions
        WHERE status = 'completed'
        AND DATE(completed_at) = CURDATE()
    ");
    $stmt->execute();
    $today = $stmt->fetch(PDO::FETCH_ASSOC)['today'] ?? 0;

    // Completados globales: registros con status 'completed' (sin importar la fecha)
    $stmt = $connect->prepare("
        SELECT COUNT(*) as completed_global
        FROM report_transcriptions
        WHERE status = 'completed'
    ");
    $stmt->execute();
    $completed_global = $stmt->fetch(PDO::FETCH_ASSOC)['completed_global'] ?? 0;

    // Tiempo promedio de transcripción - CORREGIDO
    $stmt = $connect->prepare("
        SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) as avg_time
        FROM report_transcriptions
        WHERE status = 'completed'
        AND completed_at IS NOT NULL
        AND completed_at != '0000-00-00 00:00:00'
        AND completed_at > created_at
        AND completed_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $avg_time = round($stmt->fetch(PDO::FETCH_ASSOC)['avg_time'] ?? 0);
    
    // Validar que el tiempo promedio sea razonable (máximo 24 horas = 1440 minutos)
    if ($avg_time > 1440) {
        $avg_time = 0; // Si es mayor a 24 horas, mostrar 0
    }
    
    // DEBUG: Obtener información detallada de tiempos para análisis
    $stmt = $connect->prepare("
        SELECT 
            id,
            created_at,
            completed_at,
            TIMESTAMPDIFF(MINUTE, created_at, completed_at) as tiempo_minutos,
            status
        FROM report_transcriptions
        WHERE status = 'completed'
        AND completed_at IS NOT NULL
        AND completed_at != '0000-00-00 00:00:00'
        ORDER BY completed_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $debug_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tasa de completado (porcentaje de transcripciones completadas)
    $stmt = $connect->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'completed' THEN 1 END) * 100.0 / COUNT(*) as accuracy
        FROM report_transcriptions
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute();
    $accuracy = round($stmt->fetch(PDO::FETCH_ASSOC)['accuracy'] ?? 0);

    echo json_encode([
        'pending' => $pending,
        'today' => $today,
        'completed_global' => (int)$completed_global,
        'avgTime' => $avg_time,
        'accuracy' => $accuracy,
        'debug_info' => $debug_info // Información para debug
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
    http_response_code(500);
}
?> 