<?php
/**
 * Obtiene una partida manual por numero_partida
 * Sistema: MEDIDATA
 */

ob_start();
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
header('Content-Type: application/json');
ini_set('display_errors', '0');

function opm_json(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

// Solo Contabilidad y Administrador pueden editar partidas manuales
if (($_SESSION['rol'] ?? '') === 'Auxiliar Contable') {
    opm_json(['success' => false, 'message' => 'No tiene permisos']);
    exit;
}

try {
    $numeroPartida = trim($_GET['numero_partida'] ?? '');
    if (empty($numeroPartida)) {
        opm_json(['success' => false, 'message' => 'Número de partida requerido']);
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
        opm_json(['success' => false, 'message' => 'Partida no encontrada']);
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

    opm_json([
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
    opm_json(['success' => false, 'message' => 'Error al obtener partida']);
}
