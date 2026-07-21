<?php
/**
 * Actualiza una partida del Diario General (manual o automática editable).
 * Reemplaza todos los renglones de la partida manteniendo el mismo numero_partida y tipo.
 */
ob_start();
include_once __DIR__ . '/session_check.php';
include_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../php/funciones_diario_general.php';
require_once __DIR__ . '/../php/diario_edicion_lib.php';

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

function apd_json(array $payload): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

if (!medidata_diario_puede_editar_partidas($_SESSION['rol'] ?? null)) {
    apd_json(['success' => false, 'message' => 'No tiene permisos para editar partidas del diario.']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        apd_json(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode((string) file_get_contents('php://input'), true) ?? $_POST;

    $numeroPartida = trim((string) ($input['numero_partida'] ?? ''));
    $tipoTransaccion = strtoupper(trim((string) ($input['tipo_transaccion'] ?? '')));
    $fechaOcurrencia = trim((string) ($input['fecha_ocurrencia'] ?? ''));
    $referenciaHeader = trim((string) ($input['referencia'] ?? ''));
    $descripcionGeneral = trim((string) ($input['descripcion_general'] ?? ''));
    $unidadServicio = trim((string) ($input['unidad_servicio'] ?? 'Hospital Medicasa'));
    $motivo = trim((string) ($input['motivo'] ?? ''));
    $sincronizarCompra = !empty($input['sync_compra_fecha_emision']);
    $lineas = $input['lineas'] ?? [];

    if ($numeroPartida === '') {
        apd_json(['success' => false, 'message' => 'Número de partida requerido']);
        exit;
    }
    if (!medidata_diario_tipo_es_editable($tipoTransaccion)) {
        apd_json(['success' => false, 'message' => 'Tipo de partida no editable: ' . $tipoTransaccion]);
        exit;
    }
    if ($fechaOcurrencia === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaOcurrencia)) {
        apd_json(['success' => false, 'message' => 'Fecha de ocurrencia inválida']);
        exit;
    }
    if ($descripcionGeneral === '') {
        apd_json(['success' => false, 'message' => 'La descripción general es obligatoria']);
        exit;
    }
    if ($tipoTransaccion !== 'PARTIDA_MANUAL' && $motivo === '') {
        apd_json(['success' => false, 'message' => 'Debe indicar el motivo de la corrección.']);
        exit;
    }
    if ($motivo === '' && $tipoTransaccion === 'PARTIDA_MANUAL') {
        $motivo = 'Actualización de partida manual';
    }
    if (!is_array($lineas) || count($lineas) < 2) {
        apd_json(['success' => false, 'message' => 'Debe agregar al menos 2 líneas a la partida']);
        exit;
    }

    // Verificar existencia y tipo actual
    $stCheck = $connect->prepare(
        'SELECT COUNT(*) AS c, MIN(fecha_ocurrencia) AS fecha_ant
         FROM diario_general_transacciones
         WHERE numero_partida = ? AND UPPER(COALESCE(tipo_transaccion, \'\')) = ?'
    );
    $stCheck->execute([$numeroPartida, $tipoTransaccion]);
    $meta = $stCheck->fetch(PDO::FETCH_ASSOC);
    if ((int) ($meta['c'] ?? 0) <= 0) {
        apd_json(['success' => false, 'message' => 'Partida no encontrada o el tipo no coincide.']);
        exit;
    }
    $fechaAnterior = (string) ($meta['fecha_ant'] ?? $fechaOcurrencia);
    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $fechaAnterior, $fm)) {
        $fechaAnterior = $fm[1];
    }

    $usuario = (string) ($_SESSION['name'] ?? 'Sistema');
    $username = (string) ($_SESSION['username'] ?? '');
    $transacciones = [];
    $totalDebe = 0.0;
    $totalHaber = 0.0;

    foreach ($lineas as $i => $linea) {
        $cuenta = trim((string) ($linea['cuenta'] ?? ''));
        $nombreCuenta = trim((string) ($linea['nombre_cuenta'] ?? ''));
        $debe = (float) str_replace(',', '', (string) ($linea['debe'] ?? 0));
        $haber = (float) str_replace(',', '', (string) ($linea['haber'] ?? 0));
        $descripcionLinea = trim((string) ($linea['descripcion'] ?? $descripcionGeneral));
        $refLinea = trim((string) ($linea['referencia'] ?? ''));
        if ($refLinea === '') {
            $refLinea = $referenciaHeader;
        }

        if ($cuenta === '' || $nombreCuenta === '') {
            apd_json(['success' => false, 'message' => 'Línea ' . ($i + 1) . ': Cuenta y nombre son obligatorios']);
            exit;
        }
        if ($debe <= 0 && $haber <= 0) {
            apd_json(['success' => false, 'message' => 'Línea ' . ($i + 1) . ': Debe o Haber debe ser mayor a 0']);
            exit;
        }
        if ($debe > 0 && $haber > 0) {
            apd_json(['success' => false, 'message' => 'Línea ' . ($i + 1) . ': Solo Debe o Haber, no ambos']);
            exit;
        }
        if ($refLinea === '') {
            apd_json(['success' => false, 'message' => 'Línea ' . ($i + 1) . ': Referencia obligatoria']);
            exit;
        }

        $totalDebe += $debe;
        $totalHaber += $haber;

        $transacciones[] = [
            'unidad_servicio' => $unidadServicio !== '' ? $unidadServicio : 'Hospital Medicasa',
            'cuenta' => $cuenta,
            'nombre_cuenta' => $nombreCuenta,
            'descripcion' => $descripcionLinea !== '' ? $descripcionLinea : $descripcionGeneral,
            'debe' => $debe,
            'haber' => $haber,
            'usuario' => $usuario,
            'tipo_transaccion' => $tipoTransaccion,
            'referencia' => $refLinea,
        ];
    }

    if (abs($totalDebe - $totalHaber) > 0.01) {
        apd_json([
            'success' => false,
            'message' => 'La partida no está balanceada. Debe: L. ' . number_format($totalDebe, 2)
                . ' | Haber: L. ' . number_format($totalHaber, 2),
        ]);
        exit;
    }

    // DDL fuera de transacción
    medidata_diario_asegurar_tabla_ediciones($connect);

    $connect->beginTransaction();
    try {
        $stPrevLines = $connect->prepare(
            'SELECT id, cuenta, debe, haber, referencia, descripcion
             FROM diario_general_transacciones
             WHERE numero_partida = ? AND UPPER(COALESCE(tipo_transaccion, \'\')) = ?
             ORDER BY id'
        );
        $stPrevLines->execute([$numeroPartida, $tipoTransaccion]);
        $antes = $stPrevLines->fetchAll(PDO::FETCH_ASSOC);

        $stDel = $connect->prepare(
            'DELETE FROM diario_general_transacciones
             WHERE numero_partida = ? AND UPPER(COALESCE(tipo_transaccion, \'\')) = ?'
        );
        $stDel->execute([$numeroPartida, $tipoTransaccion]);

        $fechaRegistro = date('Y-m-d H:i:s');
        foreach ($transacciones as $t) {
            registrarTransaccionContable(array_merge($t, [
                'numero_partida' => $numeroPartida,
                'fecha_ocurrencia' => $fechaOcurrencia,
                'fecha_registro' => $fechaRegistro,
            ]));
        }

        $idCompra = null;
        if ($sincronizarCompra && $tipoTransaccion === 'COMPRA_PROVEEDOR') {
            foreach ($transacciones as $t) {
                if (preg_match('/^COMP-(\d+)$/i', (string) $t['referencia'], $m)) {
                    $idCompra = (int) $m[1];
                    break;
                }
            }
            if ($idCompra > 0) {
                $stCompra = $connect->prepare('UPDATE compras SET fecha_emision = ? WHERE id_compra = ?');
                $stCompra->execute([$fechaOcurrencia, $idCompra]);
            }
        }

        $idOrden = null;
        if ($tipoTransaccion === 'CIERRE_VENTA') {
            $ref0 = $transacciones[0]['referencia'] ?? $referenciaHeader;
            if (ctype_digit((string) $ref0)) {
                $idOrden = (int) $ref0;
            } else {
                $stOrd = $connect->prepare('SELECT idord FROM orders WHERE invoice_number = ? LIMIT 1');
                $stOrd->execute([$ref0]);
                $idOrden = (int) ($stOrd->fetchColumn() ?: 0);
            }
            if ($idOrden > 0) {
                $stOrden = $connect->prepare('UPDATE orders SET placed_on = ? WHERE idord = ?');
                $stOrden->execute([$fechaOcurrencia . ' 00:00:00', $idOrden]);
            }
        }

        $detalle = json_encode([
            'antes' => $antes,
            'despues' => $transacciones,
            'total_debe' => $totalDebe,
            'total_haber' => $totalHaber,
        ], JSON_UNESCAPED_UNICODE);

        $stAudit = $connect->prepare(
            'INSERT INTO diario_general_ediciones
             (numero_partida, referencia, tipo_transaccion, fecha_anterior, fecha_nueva, motivo, usuario, username, ip_origen, detalle_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stAudit->execute([
            $numeroPartida,
            $referenciaHeader !== '' ? $referenciaHeader : ($transacciones[0]['referencia'] ?? null),
            $tipoTransaccion,
            $fechaAnterior,
            $fechaOcurrencia,
            $motivo,
            $usuario,
            $username !== '' ? $username : null,
            trim((string) ($_SERVER['REMOTE_ADDR'] ?? '')) ?: null,
            $detalle,
        ]);

        $connect->commit();
        apd_json([
            'success' => true,
            'message' => 'Partida actualizada correctamente.',
            'numero_partida' => $numeroPartida,
            'compra_sincronizada' => $idCompra,
            'orden_sincronizada' => $idOrden,
        ]);
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        throw $e;
    }
} catch (Throwable $e) {
    error_log('actualizar_partida_diario: ' . $e->getMessage());
    apd_json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
