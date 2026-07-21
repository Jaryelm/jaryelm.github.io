<?php
/**
 * Obtiene una partida del Diario General (manual o automática editable).
 */
ob_start();
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/diario_edicion_lib.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function opd_json(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

if (!medidata_diario_puede_editar_partidas($_SESSION['rol'] ?? null)) {
    opd_json(['success' => false, 'message' => 'No tiene permisos para editar partidas del diario.']);
    exit;
}

try {
    $numeroPartida = trim((string) ($_GET['numero_partida'] ?? ''));
    if ($numeroPartida === '') {
        opd_json(['success' => false, 'message' => 'Número de partida requerido']);
        exit;
    }

    $tipos = medidata_diario_tipos_editables();
    $placeholders = implode(',', array_fill(0, count($tipos), '?'));
    $params = array_merge([$numeroPartida], $tipos);

    $stmt = $connect->prepare(
        "SELECT id, numero_partida, fecha_ocurrencia, fecha_registro, unidad_servicio,
                cuenta, nombre_cuenta, descripcion, debe, haber, referencia, tipo_transaccion, usuario
         FROM diario_general_transacciones
         WHERE numero_partida = ?
           AND UPPER(COALESCE(tipo_transaccion, '')) IN ($placeholders)
         ORDER BY id ASC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        opd_json(['success' => false, 'message' => 'Partida no encontrada o no es editable.']);
        exit;
    }

    $tipo = strtoupper(trim((string) ($rows[0]['tipo_transaccion'] ?? '')));
    $lineas = [];
    $fechaOcurrencia = null;
    $referenciaHeader = '';
    $unidadServicio = '';
    $descripcionGeneral = '';

    foreach ($rows as $r) {
        $fechaOcurrencia = $fechaOcurrencia ?? $r['fecha_ocurrencia'];
        if ($referenciaHeader === '' && trim((string) ($r['referencia'] ?? '')) !== '') {
            $referenciaHeader = (string) $r['referencia'];
        }
        if ($unidadServicio === '' && trim((string) ($r['unidad_servicio'] ?? '')) !== '') {
            $unidadServicio = (string) $r['unidad_servicio'];
        }
        if ($descripcionGeneral === '' && trim((string) ($r['descripcion'] ?? '')) !== '') {
            $descripcionGeneral = (string) $r['descripcion'];
        }
        $lineas[] = [
            'cuenta' => $r['cuenta'],
            'nombre_cuenta' => $r['nombre_cuenta'],
            'debe' => (float) $r['debe'],
            'haber' => (float) $r['haber'],
            'descripcion' => $r['descripcion'] ?? '',
            'referencia' => $r['referencia'] ?? '',
        ];
    }

    // Fecha solo día
    if ($fechaOcurrencia && preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $fechaOcurrencia, $m)) {
        $fechaOcurrencia = $m[1];
    }

    opd_json([
        'success' => true,
        'numero_partida' => $numeroPartida,
        'tipo_transaccion' => $tipo,
        'fecha_ocurrencia' => $fechaOcurrencia,
        'referencia' => $referenciaHeader,
        'unidad_servicio' => $unidadServicio,
        'descripcion_general' => $descripcionGeneral,
        'lineas' => $lineas,
        'requiere_motivo' => $tipo !== 'PARTIDA_MANUAL',
    ]);
} catch (Throwable $e) {
    error_log('obtener_partida_diario: ' . $e->getMessage());
    opd_json(['success' => false, 'message' => 'Error al obtener la partida.']);
}
