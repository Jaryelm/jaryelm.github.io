<?php
declare(strict_types=1);

require_once __DIR__ . '/funciones_diario_general.php';

function medidata_ingreso_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function medidata_ingreso_referencia(array $orden): string
{
    $ref = trim((string) ($orden['invoice_number'] ?? ''));
    if ($ref === '') {
        $ref = (string) ($orden['idord'] ?? '');
    }

    return $ref;
}

function medidata_ingreso_obtener(PDO $connect, int $idord): ?array
{
    $st = $connect->prepare(
        "SELECT idord, placed_on, invoice_number, method, processed_by, total_price, invoice_status
         FROM orders WHERE idord = ? LIMIT 1"
    );
    $st->execute([$idord]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function medidata_ingreso_eliminar_partida(PDO $connect, string $referencia): void
{
    $st = $connect->prepare(
        "DELETE FROM diario_general_transacciones
         WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA'"
    );
    $st->execute([$referencia]);
}

function medidata_ingreso_regenerar_partida(PDO $connect, int $idord): string
{
    $orden = medidata_ingreso_obtener($connect, $idord);
    if (!$orden) {
        throw new RuntimeException('Ingreso no encontrado.');
    }
    if (strtolower((string) ($orden['invoice_status'] ?? '')) !== 'cobrada') {
        throw new RuntimeException('Solo se pueden corregir ingresos con estado Cobrada.');
    }

    $referencia = medidata_ingreso_referencia($orden);
    medidata_ingreso_eliminar_partida($connect, $referencia);

    return registrarTransaccionesFacturaCobrada($idord);
}

function medidata_ingreso_sincronizar_fecha_partida(PDO $connect, string $referencia, string $fechaYmd): void
{
    $st = $connect->prepare(
        "UPDATE diario_general_transacciones
         SET fecha_ocurrencia = ?
         WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA'"
    );
    $st->execute([$fechaYmd, $referencia]);
}

function medidata_ingreso_actualizar(
    PDO $connect,
    int $idord,
    array $datos,
    string $motivo,
    string $usuario
): array {
    $actual = medidata_ingreso_obtener($connect, $idord);
    if (!$actual) {
        throw new RuntimeException('Ingreso no encontrado.');
    }
    if (strtolower((string) ($actual['invoice_status'] ?? '')) !== 'cobrada') {
        throw new RuntimeException('Solo se pueden corregir ingresos con estado Cobrada.');
    }

    $fecha = trim((string) ($datos['placed_on'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new RuntimeException('Fecha inválida.');
    }

    $factura = trim((string) ($datos['invoice_number'] ?? ''));
    $metodo = trim((string) ($datos['method'] ?? ''));
    $total = round((float) ($datos['total_price'] ?? 0), 2);

    if ($total <= 0) {
        throw new RuntimeException('El total debe ser mayor a cero.');
    }

    $referenciaAnterior = medidata_ingreso_referencia($actual);
    $fechaAnterior = $actual['placed_on'] ? date('Y-m-d', strtotime((string) $actual['placed_on'])) : '';
    $fechaCambio = $fechaAnterior !== $fecha;
    $totalCambio = abs((float) $actual['total_price'] - $total) > 0.005;
    $facturaCambio = (string) ($actual['invoice_number'] ?? '') !== $factura;
    $metodoCambio = (string) ($actual['method'] ?? '') !== $metodo;

    $connect->beginTransaction();
    try {
        $st = $connect->prepare(
            'UPDATE orders SET placed_on = ?, invoice_number = ?, method = ?, total_price = ? WHERE idord = ?'
        );
        $st->execute([$fecha . ' 00:00:00', $factura, $metodo, $total, $idord]);

        $ordenActualizada = medidata_ingreso_obtener($connect, $idord);
        $referenciaNueva = medidata_ingreso_referencia($ordenActualizada ?: $actual);

        if ($facturaCambio && $referenciaAnterior !== $referenciaNueva) {
            $stRef = $connect->prepare(
                "UPDATE diario_general_transacciones SET referencia = ?
                 WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA'"
            );
            $stRef->execute([$referenciaNueva, $referenciaAnterior]);
        }

        if ($totalCambio || $metodoCambio) {
            $numeroPartida = medidata_ingreso_regenerar_partida($connect, $idord);
        } elseif ($fechaCambio) {
            medidata_ingreso_sincronizar_fecha_partida($connect, $referenciaNueva, $fecha);
            $stPart = $connect->prepare(
                "SELECT numero_partida FROM diario_general_transacciones
                 WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA' ORDER BY id DESC LIMIT 1"
            );
            $stPart->execute([$referenciaNueva]);
            $numeroPartida = (string) ($stPart->fetchColumn() ?: '');
        } else {
            $stPart = $connect->prepare(
                "SELECT numero_partida FROM diario_general_transacciones
                 WHERE referencia = ? AND tipo_transaccion = 'CIERRE_VENTA' ORDER BY id DESC LIMIT 1"
            );
            $stPart->execute([$referenciaNueva]);
            $numeroPartida = (string) ($stPart->fetchColumn() ?: '');
        }

        $connect->commit();

        return [
            'idord' => $idord,
            'numero_partida' => $numeroPartida,
            'placed_on' => $fecha,
            'invoice_number' => $factura,
        ];
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        throw $e;
    }
}

function medidata_ingreso_eliminar(PDO $connect, int $idord, string $motivo): void
{
    $actual = medidata_ingreso_obtener($connect, $idord);
    if (!$actual) {
        throw new RuntimeException('Ingreso no encontrado.');
    }

    $referencia = medidata_ingreso_referencia($actual);

    $connect->beginTransaction();
    try {
        medidata_ingreso_eliminar_partida($connect, $referencia);

        $st = $connect->prepare(
            "UPDATE orders SET invoice_status = 'Pendiente', updated_by = NULL, updated_at = NULL WHERE idord = ?"
        );
        $st->execute([$idord]);

        $connect->commit();
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        throw $e;
    }
}
