<?php
declare(strict_types=1);

require_once __DIR__ . '/partida_compra_proveedor.php';
require_once __DIR__ . '/proveedor_comercial_lib.php';

function medidata_gestion_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function medidata_compra_ref(int $idCompra): string
{
    return 'COMP-' . $idCompra;
}

function medidata_compra_monto_pagado(PDO $connect, int $idCompra): float
{
    $st = $connect->prepare(
        "SELECT COALESCE(SUM(debe), 0) FROM diario_general_transacciones
         WHERE referencia = ? AND cuenta = '210200107' AND debe > 0"
    );
    $st->execute([medidata_compra_ref($idCompra)]);

    return round((float) $st->fetchColumn(), 2);
}

function medidata_compra_tiene_pagos(PDO $connect, int $idCompra): bool
{
    return medidata_compra_monto_pagado($connect, $idCompra) > 0.005;
}

function medidata_compra_asegurar_tabla_historial(PDO $connect): void
{
    static $listo = false;
    if ($listo) {
        return;
    }
    $connect->exec(
        'CREATE TABLE IF NOT EXISTS compra_historial (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            id_compra INT NOT NULL,
            campo VARCHAR(120) NOT NULL,
            valor_anterior TEXT NULL,
            valor_nuevo TEXT NULL,
            fecha_modificacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
    $listo = true;
}

function medidata_compra_registrar_historial(PDO $connect, int $idCompra, string $campo, $anterior, $nuevo): void
{
    try {
        $st = $connect->prepare(
            'INSERT INTO compra_historial (id_compra, campo, valor_anterior, valor_nuevo, fecha_modificacion)
             VALUES (?, ?, ?, ?, NOW())'
        );
        $st->execute([$idCompra, $campo, (string) $anterior, (string) $nuevo]);
    } catch (Throwable $e) {
        error_log('medidata_compra_registrar_historial: ' . $e->getMessage());
    }
}

function medidata_compra_obtener(PDO $connect, int $idCompra): ?array
{
    $st = $connect->prepare(
        'SELECT id_compra, fecha_emision, prov_datos, dato_fac, isv_global, sub_total, total, numero_partida_contable
         FROM compras WHERE id_compra = ? LIMIT 1'
    );
    $st->execute([$idCompra]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function medidata_compra_eliminar_partida(PDO $connect, int $idCompra): void
{
    $ref = medidata_compra_ref($idCompra);
    $st = $connect->prepare(
        "DELETE FROM diario_general_transacciones
         WHERE referencia = ? AND tipo_transaccion = 'COMPRA_PROVEEDOR'"
    );
    $st->execute([$ref]);

    $up = $connect->prepare(
        "UPDATE compras SET numero_partida_contable = NULL WHERE id_compra = ?"
    );
    $up->execute([$idCompra]);
}

function medidata_compra_regenerar_partida(PDO $connect, int $idCompra, string $usuario): string
{
    medidata_compra_eliminar_partida($connect, $idCompra);

    return medidata_generar_partida_desde_compra($connect, $idCompra, $usuario);
}

function medidata_compra_sincronizar_fecha_partida(PDO $connect, int $idCompra, string $fechaYmd): void
{
    $ref = medidata_compra_ref($idCompra);
    $st = $connect->prepare(
        "UPDATE diario_general_transacciones
         SET fecha_ocurrencia = ?
         WHERE referencia = ? AND tipo_transaccion = 'COMPRA_PROVEEDOR'"
    );
    $st->execute([$fechaYmd, $ref]);
}

function medidata_compra_actualizar(
    PDO $connect,
    int $idCompra,
    array $datos,
    string $motivo,
    string $usuario
): array {
    $actual = medidata_compra_obtener($connect, $idCompra);
    if (!$actual) {
        throw new RuntimeException('Compra no encontrada.');
    }

    $fecha = trim((string) ($datos['fecha_emision'] ?? ''));
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        throw new RuntimeException('Fecha de emisión inválida.');
    }

    $proveedor = medidata_proveedor_normalizar($connect, (string) ($datos['prov_datos'] ?? ''));
    $factura = strtoupper(trim((string) ($datos['dato_fac'] ?? '')));
    $subTotal = round((float) ($datos['sub_total'] ?? 0), 2);
    $isv = round((float) ($datos['isv_global'] ?? 0), 2);
    $total = round((float) ($datos['total'] ?? 0), 2);

    if ($proveedor === '') {
        throw new RuntimeException('El proveedor es obligatorio.');
    }
    if ($total <= 0) {
        throw new RuntimeException('El total debe ser mayor a cero.');
    }

    $tienePagos = medidata_compra_tiene_pagos($connect, $idCompra);
    $montosCambiaron = abs((float) $actual['sub_total'] - $subTotal) > 0.005
        || abs((float) $actual['isv_global'] - $isv) > 0.005
        || abs((float) $actual['total'] - $total) > 0.005;

    if ($tienePagos && $montosCambiaron) {
        throw new RuntimeException(
            'Esta compra ya tiene pagos registrados. Solo puede corregir la fecha, proveedor o número de factura.'
        );
    }

    $fechaAnterior = $actual['fecha_emision'] ? date('Y-m-d', strtotime((string) $actual['fecha_emision'])) : '';
    $fechaCambio = $fechaAnterior !== $fecha;

    // DDL (CREATE TABLE) hace commit implícito en MySQL: debe ir fuera de la transacción.
    medidata_compra_asegurar_tabla_historial($connect);

    $connect->beginTransaction();
    try {
        foreach ([
            'fecha_emision' => [$fechaAnterior, $fecha],
            'prov_datos' => [$actual['prov_datos'], $proveedor],
            'dato_fac' => [$actual['dato_fac'], $factura],
            'sub_total' => [$actual['sub_total'], $subTotal],
            'isv_global' => [$actual['isv_global'], $isv],
            'total' => [$actual['total'], $total],
        ] as $campo => [$ant, $nue]) {
            if ((string) $ant !== (string) $nue) {
                medidata_compra_registrar_historial($connect, $idCompra, $campo, $ant, $nue);
            }
        }

        medidata_compra_registrar_historial($connect, $idCompra, 'motivo_correccion', '', $motivo);

        $st = $connect->prepare(
            'UPDATE compras SET
                fecha_emision = ?, prov_datos = ?, dato_fac = ?,
                sub_total = ?, isv_global = ?, total = ?
             WHERE id_compra = ?'
        );
        $st->execute([$fecha, $proveedor, $factura, $subTotal, $isv, $total, $idCompra]);

        if ($montosCambiaron) {
            $numeroPartida = medidata_compra_regenerar_partida($connect, $idCompra, $usuario);
        } elseif ($fechaCambio) {
            medidata_compra_sincronizar_fecha_partida($connect, $idCompra, $fecha);
            $numeroPartida = (string) ($actual['numero_partida_contable'] ?? '');
        } else {
            $numeroPartida = (string) ($actual['numero_partida_contable'] ?? '');
        }

        $connect->commit();

        return [
            'id_compra' => $idCompra,
            'numero_partida' => $numeroPartida,
            'fecha_emision' => $fecha,
        ];
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        throw $e;
    }
}

function medidata_compra_eliminar(PDO $connect, int $idCompra, string $motivo, string $usuario): void
{
    if (medidata_compra_tiene_pagos($connect, $idCompra)) {
        throw new RuntimeException(
            'No se puede eliminar: la compra ya tiene pagos registrados en Cuentas por Pagar.'
        );
    }

    $actual = medidata_compra_obtener($connect, $idCompra);
    if (!$actual) {
        throw new RuntimeException('Compra no encontrada.');
    }

    medidata_compra_asegurar_tabla_historial($connect);

    $connect->beginTransaction();
    try {
        medidata_compra_registrar_historial($connect, $idCompra, 'ELIMINACION', json_encode($actual, JSON_UNESCAPED_UNICODE), $motivo);

        $ref = medidata_compra_ref($idCompra);
        $st = $connect->prepare(
            "DELETE FROM diario_general_transacciones
             WHERE referencia = ? AND tipo_transaccion IN ('COMPRA_PROVEEDOR', 'PAGO_PROVEEDOR')"
        );
        $st->execute([$ref]);

        $stDet = $connect->prepare('DELETE FROM detalle_compras WHERE id_compra = ?');
        $stDet->execute([$idCompra]);

        $stCom = $connect->prepare('DELETE FROM compras WHERE id_compra = ?');
        $stCom->execute([$idCompra]);

        $connect->commit();
    } catch (Throwable $e) {
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        throw $e;
    }
}
