<?php
require_once('../../backend/bd/Conexion.php');
session_start();
header('Content-Type: application/json');

// Configurar la zona horaria en PHP
date_default_timezone_set('America/Tegucigalpa');

try {
    // Obtener el ID del técnico autenticado
    $technician_id = $_SESSION['id'] ?? null;

    if (empty($technician_id)) {
        http_response_code(401); // No autorizado
        echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Inicia sesión para continuar.']);
        exit;
    }

    // Obtener la fecha actual en la zona horaria de Tegucigalpa
    $today = date('Y-m-d'); // Fecha actual en formato YYYY-MM-DD

    // Pendientes: estudios en worklist con status 'pending'
    $stmt = $connect->prepare("
        SELECT COUNT(*) as pending
        FROM worklist
        WHERE status = 'pending'
    ");
    $stmt->execute();
    $pending = $stmt->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;

    // Completados hoy: estudios en worklist con status 'completed' y fecha de hoy
    $stmt = $connect->prepare("
        SELECT COUNT(*) as completed_today
        FROM worklist
        WHERE status = 'completed'
        AND DATE(updated_at) = CURDATE()
    ");
    $stmt->execute();
    $completed_today = $stmt->fetch(PDO::FETCH_ASSOC)['completed_today'] ?? 0;

    // Completados globales: estudios en worklist con status 'completed' (sin importar la fecha)
    $stmt = $connect->prepare("
        SELECT COUNT(*) as completed_global
        FROM worklist
        WHERE status = 'completed'
    ");
    $stmt->execute();
    $completed_global = $stmt->fetch(PDO::FETCH_ASSOC)['completed_global'] ?? 0;

    // En progreso: estudios en worklist con status 'in_progress'
    $stmt = $connect->prepare("
        SELECT COUNT(*) as in_progress
        FROM worklist
        WHERE status = 'in_progress'
    ");
    $stmt->execute();
    $in_progress = $stmt->fetch(PDO::FETCH_ASSOC)['in_progress'] ?? 0;

    // Cancelados hoy: estudios en worklist con status 'cancelled' y fecha de hoy
    $stmt = $connect->prepare("
        SELECT COUNT(*) as cancelled_today
        FROM worklist
        WHERE status = 'cancelled'
        AND DATE(updated_at) = CURDATE()
    ");
    $stmt->execute();
    $cancelled_today = $stmt->fetch(PDO::FETCH_ASSOC)['cancelled_today'] ?? 0;

    // Obtener tiempo promedio por estudio
    $stmt = $connect->prepare("
        SELECT 
            CASE 
                WHEN COUNT(*) > 0 THEN 
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at))
                ELSE 0 
            END as avg_time
        FROM worklist 
        WHERE status = 'completed' 
        AND DATE(updated_at) = ?
    ");
    $stmt->execute([$today]);
    $avg_time = $stmt->fetch(PDO::FETCH_ASSOC)['avg_time'];

    // Obtener calidad promedio
    $stmt = $connect->prepare("
        SELECT 
            (COUNT(CASE WHEN image_quality = 'excellent' OR image_quality = 'acceptable' THEN 1 END) * 100.0 / 
            NULLIF(COUNT(*), 0)) as quality_percentage
        FROM quality_control
        WHERE DATE(created_at) = ?
    ");
    $stmt->execute([$today]);
    $quality = $stmt->fetch(PDO::FETCH_ASSOC)['quality_percentage'];

    // Devolver las estadísticas en formato JSON
    echo json_encode([
        'pending' => (int)$pending,
        'completed_today' => (int)$completed_today,
        'completed_global' => (int)$completed_global,
        'in_progress' => (int)$in_progress,
        'cancelled_today' => (int)$cancelled_today,
        'avg_time' => $avg_time ? round($avg_time) : 0,
        'quality' => $quality ? round($quality) : 0
    ]);

} catch (Exception $e) {
    // Manejar errores
    http_response_code(500); // Error interno del servidor
    echo json_encode(['error' => $e->getMessage()]);
}
?>