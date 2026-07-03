<?php
session_start();
header('Content-Type: application/json');
require_once '../bd/Conexion.php';

try {
    $usuarioActual = $_SESSION['username'] ?? '';
    
    if (empty($usuarioActual)) {
        echo json_encode(['error' => true, 'message' => 'Usuario no autenticado']);
        exit;
    }
    
    // Obtener fecha local de Honduras
    date_default_timezone_set('America/Tegucigalpa');
    $fechaHonduras = date('Y-m-d');
    
    // Verificar si el usuario tiene un turno activo HOY (fecha local Honduras)
    // Un turno está activo solo si NO tiene un cierre asociado
    $stmt = $connect->prepare("
        SELECT 
            t.id,
            t.fecha_inicio,
            t.turno
        FROM turnos_iniciados t
        WHERE t.usuario = ?
        AND DATE(t.fecha_inicio) = ?
        AND NOT EXISTS (
            SELECT 1 FROM cierre_caja c 
            WHERE c.id_turno_iniciado = t.id
        )
        ORDER BY t.fecha_inicio DESC
        LIMIT 1
    ");
    
    $stmt->execute([$usuarioActual, $fechaHonduras]);
    $turno = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($turno) {
        // Hay un turno activo sin cerrar
        // Formatear fecha para mostrar
        $fechaInicio = new DateTime($turno['fecha_inicio']);
        $fechaFormateada = $fechaInicio->format('d/m/Y H:i');
        
        echo json_encode([
            'turno_iniciado' => true,
            'id_turno' => $turno['id'],
            'efectivo_inicial' => 0.00, // Efectivo inicial siempre será 0.00 (campo eliminado del sistema)
            'fecha_inicio' => $turno['fecha_inicio'],
            'fecha_inicio_formateada' => $fechaFormateada,
            'turno' => $turno['turno'] ?? 'No especificado'
        ]);
    } else {
        // No hay turno activo - todos los turnos del día ya tienen cierre
        echo json_encode([
            'turno_iniciado' => false,
            'message' => 'No hay turno activo'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error al verificar turno: ' . $e->getMessage()
    ]);
}
?>
