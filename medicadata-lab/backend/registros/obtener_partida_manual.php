<?php
/**
 * Obtiene una partida manual por numero_partida
 * Sistema: MEDIDATA
 */

include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
header('Content-Type: application/json');

// Solo Contabilidad y Administrador pueden editar partidas manuales
if (($_SESSION['rol'] ?? '') === 'Auxiliar Contable') {
    echo json_encode(['success' => false, 'message' => 'No tiene permisos']);
    exit;
}

try {
    $numeroPartida = trim($_GET['numero_partida'] ?? '');
    if (empty($numeroPartida)) {
        echo json_encode(['success' => false, 'message' => 'Número de partida requerido']);
        exit;
    }

    $stmt = $connect->prepare("
        SELECT id, numero_partida, fecha_ocurrencia, fecha_registro, unidad_servicio,
               cuenta, nombre_cuenta, descripcion, debe, haber, referencia
        FROM diario_general_transacciones
        WHERE numero_partida = :np AND tipo_transaccion = 'PARTIDA_MANUAL'
        ORDER BY id ASC
    ");
    $stmt->execute([':np' => $numeroPartida]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo json_encode(['success' => false, 'message' => 'Partida no encontrada']);
        exit;
    }

    $lineas = [];
    $fechaOcurrencia = null;
    $referencia = '';
    $unidadServicio = '';
    $descripcionGeneral = '';

    foreach ($rows as $r) {
        $fechaOcurrencia = $fechaOcurrencia ?? $r['fecha_ocurrencia'];
        $referencia = $referencia ?: ($r['referencia'] ?? '');
        $unidadServicio = $unidadServicio ?: ($r['unidad_servicio'] ?? '');
        $descripcionGeneral = $descripcionGeneral ?: ($r['descripcion'] ?? '');
        $lineas[] = [
            'cuenta' => $r['cuenta'],
            'nombre_cuenta' => $r['nombre_cuenta'],
            'debe' => floatval($r['debe']),
            'haber' => floatval($r['haber']),
            'descripcion' => $r['descripcion'] ?? ''
        ];
    }

    echo json_encode([
        'success' => true,
        'numero_partida' => $numeroPartida,
        'fecha_ocurrencia' => $fechaOcurrencia,
        'referencia' => $referencia,
        'unidad_servicio' => $unidadServicio,
        'descripcion_general' => $descripcionGeneral,
        'lineas' => $lineas
    ]);

} catch (Exception $e) {
    error_log("Error obtener_partida_manual: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener partida']);
}
