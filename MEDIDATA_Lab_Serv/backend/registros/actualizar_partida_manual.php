<?php
/**
 * Actualiza una partida manual existente
 * Sistema: MEDIDATA
 */

ob_start();
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/funciones_diario_general.php';

header('Content-Type: application/json');
ini_set('display_errors', '0');

function apm_json(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

// Solo Contabilidad y Administrador pueden editar partidas manuales
$rol = $_SESSION['rol'] ?? '';
if ($rol === 'Auxiliar Contable') {
    apm_json(['success' => false, 'message' => 'No tiene permisos para editar partidas manuales']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apm_json(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $numeroPartida = trim($input['numero_partida'] ?? '');
    $fechaOcurrencia = trim($input['fecha_ocurrencia'] ?? '');
    $referencia = trim($input['referencia'] ?? '');
    $descripcionGeneral = trim($input['descripcion_general'] ?? '');
    $unidadServicio = trim($input['unidad_servicio'] ?? 'RRHH');
    $lineas = $input['lineas'] ?? [];

    if (empty($numeroPartida)) {
        apm_json(['success' => false, 'message' => 'Número de partida requerido']);
        exit;
    }

    if (empty($fechaOcurrencia)) {
        apm_json(['success' => false, 'message' => 'La fecha de ocurrencia es obligatoria']);
        exit;
    }

    if (empty($referencia)) {
        apm_json(['success' => false, 'message' => 'La referencia es obligatoria']);
        exit;
    }

    if (empty($descripcionGeneral)) {
        apm_json(['success' => false, 'message' => 'La descripción general es obligatoria']);
        exit;
    }

    if (!is_array($lineas) || count($lineas) < 2) {
        apm_json(['success' => false, 'message' => 'Debe agregar al menos 2 líneas a la partida']);
        exit;
    }

    // Verificar que la partida existe y es manual
    $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM diario_general_transacciones WHERE numero_partida = :np AND tipo_transaccion = 'PARTIDA_MANUAL'");
    $stmtCheck->execute([':np' => $numeroPartida]);
    if ($stmtCheck->fetchColumn() == 0) {
        apm_json(['success' => false, 'message' => 'Partida no encontrada o no es editable']);
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
            apm_json(['success' => false, 'message' => "Línea " . ($i + 1) . ": Cuenta y nombre son obligatorios"]);
            exit;
        }

        if ($debe <= 0 && $haber <= 0) {
            apm_json(['success' => false, 'message' => "Línea " . ($i + 1) . ": Debe o Haber debe ser mayor a 0"]);
            exit;
        }

        if ($debe > 0 && $haber > 0) {
            apm_json(['success' => false, 'message' => "Línea " . ($i + 1) . ": Solo debe tener Debe O Haber, no ambos"]);
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
        apm_json([
            'success' => false,
            'message' => 'La partida no está balanceada. Total Debe: L. ' . number_format($totalDebe, 2) . ' | Total Haber: L. ' . number_format($totalHaber, 2) . ' | Diferencia: L. ' . number_format($diferencia, 2)
        ]);
        exit;
    }

    $connect->beginTransaction();
    try {
        // Eliminar transacciones anteriores de esta partida
        $stmtDel = $connect->prepare("DELETE FROM diario_general_transacciones WHERE numero_partida = :np AND tipo_transaccion = 'PARTIDA_MANUAL'");
        $stmtDel->execute([':np' => $numeroPartida]);

        // Insertar las nuevas transacciones con el mismo numero_partida
        $fechaRegistro = date('Y-m-d H:i:s');
        foreach ($transacciones as $t) {
            $datos = array_merge($t, [
                'numero_partida' => $numeroPartida,
                'fecha_ocurrencia' => $fechaOcurrencia,
                'fecha_registro' => $fechaRegistro
            ]);
            registrarTransaccionContable($datos);
        }

        $connect->commit();
        apm_json([
            'success' => true,
            'message' => 'Partida actualizada correctamente',
            'numero_partida' => $numeroPartida
        ]);
    } catch (Exception $e) {
        $connect->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error actualizar_partida_manual: " . $e->getMessage());
    apm_json([
        'success' => false,
        'message' => 'Error al actualizar: ' . $e->getMessage()
    ]);
}
