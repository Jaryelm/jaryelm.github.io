<?php
/**
 * Registra una partida manual en el Diario General
 * Sistema: MEDIDATA
 */

include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/funciones_diario_general.php';

if (!function_exists('existePartidaDuplicada')) {
    error_log('registrar_partida_manual: falta existePartidaDuplicada() — despliegue backend/php/funciones_diario_general.php actualizado. Anti-duplicado desactivado.');
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $fechaOcurrencia = trim($input['fecha_ocurrencia'] ?? '');
    $referencia = trim($input['referencia'] ?? '');
    $descripcionGeneral = trim($input['descripcion_general'] ?? '');
    $unidadServicio = trim($input['unidad_servicio'] ?? 'RRHH');
    $lineas = $input['lineas'] ?? [];

    if (empty($fechaOcurrencia)) {
        echo json_encode(['success' => false, 'message' => 'La fecha de ocurrencia es obligatoria']);
        exit;
    }

    if (empty($referencia)) {
        echo json_encode(['success' => false, 'message' => 'La referencia es obligatoria']);
        exit;
    }

    if (empty($descripcionGeneral)) {
        echo json_encode(['success' => false, 'message' => 'La descripción general es obligatoria']);
        exit;
    }

    if (!is_array($lineas) || count($lineas) < 2) {
        echo json_encode(['success' => false, 'message' => 'Debe agregar al menos 2 líneas a la partida']);
        exit;
    }

    $usuario = $_SESSION['name'] ?? 'Sistema';
    $transacciones = [];
    $totalDebe = 0;
    $totalHaber = 0;

    foreach ($lineas as $i => $linea) {
        $cuenta = trim($linea['cuenta'] ?? '');
        $nombreCuenta = trim($linea['nombre_cuenta'] ?? '');
        $debe = floatval(str_replace(',', '', $linea['debe'] ?? 0));
        $haber = floatval(str_replace(',', '', $linea['haber'] ?? 0));
        $descripcionLinea = trim($linea['descripcion'] ?? $descripcionGeneral);

        if (empty($cuenta) || empty($nombreCuenta)) {
            echo json_encode(['success' => false, 'message' => "Línea " . ($i + 1) . ": Cuenta y nombre son obligatorios"]);
            exit;
        }

        if ($debe <= 0 && $haber <= 0) {
            echo json_encode(['success' => false, 'message' => "Línea " . ($i + 1) . ": Debe o Haber debe ser mayor a 0"]);
            exit;
        }

        if ($debe > 0 && $haber > 0) {
            echo json_encode(['success' => false, 'message' => "Línea " . ($i + 1) . ": Solo debe tener Debe O Haber, no ambos"]);
            exit;
        }

        $totalDebe += $debe;
        $totalHaber += $haber;

        $transacciones[] = [
            'unidad_servicio' => $unidadServicio ?: 'RRHH',
            'cuenta' => $cuenta,
            'nombre_cuenta' => $nombreCuenta,
            'descripcion' => $descripcionLinea,
            'debe' => $debe,
            'haber' => $haber,
            'usuario' => $usuario,
            'tipo_transaccion' => 'PARTIDA_MANUAL',
            'referencia' => $referencia
        ];
    }

    $diferencia = abs($totalDebe - $totalHaber);
    if ($diferencia > 0.01) {
        echo json_encode([
            'success' => false,
            'message' => 'La partida no está balanceada. Total Debe: L. ' . number_format($totalDebe, 2) . ' | Total Haber: L. ' . number_format($totalHaber, 2) . ' | Diferencia: L. ' . number_format($diferencia, 2)
        ]);
        exit;
    }

    // Bloqueo para evitar partidas duplicadas por doble clic o latencia
    $lockAcquired = $connect->query("SELECT GET_LOCK('partida_manual_registro', 5)")->fetchColumn();
    if ($lockAcquired != 1) {
        echo json_encode([
            'success' => false,
            'message' => 'Sistema ocupado. Espere unos segundos e intente de nuevo.'
        ]);
        exit;
    }

    try {
        // Validar partida duplicada (doble clic, latencia, etc.) — solo si existe en funciones_diario_general.php desplegado
        if (function_exists('existePartidaDuplicada') && existePartidaDuplicada($referencia, $fechaOcurrencia, $totalDebe, $totalHaber, $usuario, 'PARTIDA_MANUAL', 90)) {
            $connect->query("SELECT RELEASE_LOCK('partida_manual_registro')");
            echo json_encode([
                'success' => false,
                'message' => 'Ya existe una partida idéntica registrada recientemente. Evite doble clic en el botón Guardar. Solo se permite guardar una partida a la vez.'
            ]);
            exit;
        }

        $numeroPartida = registrarPartidaCompleta($transacciones, $fechaOcurrencia);
        $connect->query("SELECT RELEASE_LOCK('partida_manual_registro')");

        echo json_encode([
            'success' => true,
            'message' => 'Partida registrada correctamente',
            'numero_partida' => $numeroPartida
        ]);
    } finally {
        @$connect->query("SELECT RELEASE_LOCK('partida_manual_registro')");
    }

} catch (Exception $e) {
    error_log("Error registrar_partida_manual: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar: ' . $e->getMessage()
    ]);
}
