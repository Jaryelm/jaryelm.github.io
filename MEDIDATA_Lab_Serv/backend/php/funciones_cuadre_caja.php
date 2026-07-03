<?php

/**
 * Totales por método de pago a partir del listado de facturas del cierre.
 *
 * @param array<int, array<string, mixed>> $facturas
 * @return array<string, float>
 */
function medidata_totales_por_metodo_desde_facturas(array $facturas): array
{
    $totales = [];
    foreach ($facturas as $fac) {
        $metodo = trim((string) ($fac['method'] ?? 'Efectivo'));
        if ($metodo === '') {
            $metodo = 'Efectivo';
        }
        $totales[$metodo] = ($totales[$metodo] ?? 0) + (float) ($fac['total_price'] ?? 0);
    }
    return $totales;
}

/**
 * @param array<string, float>|string|null $metodos
 */
function medidata_cuadre_monto_metodo($metodos, string $metodo): float
{
    if (is_string($metodos)) {
        $data = json_decode($metodos, true);
        $metodos = is_array($data) ? $data : [];
    }
    if (!is_array($metodos)) {
        return 0.0;
    }
    return (float) ($metodos[$metodo] ?? 0);
}

/**
 * @param array<string, float>|string|null $metodos
 */
function medidata_cuadre_otros_metodos($metodos): float
{
    if (is_string($metodos)) {
        $data = json_decode($metodos, true);
        $metodos = is_array($data) ? $data : [];
    }
    if (!is_array($metodos)) {
        return 0.0;
    }
    $suma = 0.0;
    foreach ($metodos as $metodo => $monto) {
        if ($metodo === 'Efectivo' || $metodo === 'Tarjeta') {
            continue;
        }
        $suma += (float) $monto;
    }
    return $suma;
}

/**
 * Facturas cobradas atribuibles a un cajero dentro de la ventana del turno/cierre.
 *
 * @return array<int, array<string, mixed>>
 */
function medidata_facturas_cierre_turno(
    PDO $connect,
    string $usuarioCierre,
    string $nombreCajero,
    string $fechaInicioTurno,
    string $fechaFinCierre,
    ?string $fechaDesdeCierre = null
): array {
    $desde = $fechaDesdeCierre ?: $fechaInicioTurno;
    $fechaDiaTurno = date('Y-m-d', strtotime($fechaInicioTurno));

    $stmt = $connect->prepare("
        SELECT idord, invoice_number, total_price, updated_at, method, discount_amount,
               banco_emisor, tipo_tarjeta, monto_efectivo_mixto, monto_tarjeta_mixto, tipo_pago_mixto
        FROM orders
        WHERE DATE(placed_on) = :fechaDiaTurno
          AND updated_at >= :fechaDesdeCierre
          AND updated_at <= :fechaFinCierre
          AND invoice_status = 'Cobrada'
          AND (
              updated_by = :usuarioCierre
              OR processed_by = :nombreCajero
          )
        ORDER BY updated_at ASC
    ");
    $stmt->execute([
        ':fechaDiaTurno' => $fechaDiaTurno,
        ':fechaDesdeCierre' => $desde,
        ':fechaFinCierre' => $fechaFinCierre,
        ':usuarioCierre' => $usuarioCierre,
        ':nombreCajero' => $nombreCajero,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Filas del reporte Cuadre Caja (misma base que conciliación manual: placed_on + processed_by).
 *
 * @return array<int, array{fecha: string, fecha_orden: string, nombre: string, metodos: array<string, float>}>
 */
function medidata_filas_cuadre_caja(PDO $connect, ?string $desde = null, ?string $hasta = null): array
{
    $sql = "
        SELECT DATE(placed_on) AS fecha, processed_by AS nombre, method, SUM(total_price) AS total
        FROM orders
        WHERE invoice_status = 'Cobrada'
          AND invoice_number IS NOT NULL
          AND invoice_number <> ''
          AND processed_by IS NOT NULL
          AND processed_by <> ''
    ";
    $params = [];

    if ($desde && $hasta) {
        $sql .= " AND DATE(placed_on) BETWEEN :desde AND :hasta";
        $params[':desde'] = $desde;
        $params[':hasta'] = $hasta;
    } elseif ($desde) {
        $sql .= " AND DATE(placed_on) >= :desde";
        $params[':desde'] = $desde;
    } elseif ($hasta) {
        $sql .= " AND DATE(placed_on) <= :hasta";
        $params[':hasta'] = $hasta;
    }

    $sql .= " GROUP BY DATE(placed_on), processed_by, method ORDER BY fecha DESC, nombre ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute($params);

    $agrupado = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fecha = (string) $row['fecha'];
        $nombre = (string) $row['nombre'];
        $clave = $fecha . '|' . $nombre;

        if (!isset($agrupado[$clave])) {
            $agrupado[$clave] = [
                'fecha' => $fecha,
                'fecha_orden' => $fecha . ' 23:59:59',
                'nombre' => $nombre,
                'metodos' => [],
            ];
        }

        $metodo = trim((string) ($row['method'] ?? 'Efectivo')) ?: 'Efectivo';
        $agrupado[$clave]['metodos'][$metodo] = ($agrupado[$clave]['metodos'][$metodo] ?? 0) + (float) $row['total'];
    }

    return array_values($agrupado);
}

/**
 * Recalcula totales de un registro cierre_caja según facturas reales del turno.
 *
 * @return array{total_ventas: float, facturas_cobradas: int, total_por_metodo: string}|null
 */
function medidata_recalcular_datos_cierre(PDO $connect, array $cierre): ?array
{
    $usuario = (string) ($cierre['usuario_cierre'] ?? '');
    $nombre = (string) ($cierre['nombre_completo'] ?? '');
    $fechaCierre = (string) ($cierre['fecha_cierre'] ?? '');
    $idTurno = (int) ($cierre['id_turno_iniciado'] ?? 0);

    if ($usuario === '' || $nombre === '' || $fechaCierre === '') {
        return null;
    }

    $fechaInicioTurno = date('Y-m-d 00:00:00', strtotime($fechaCierre));
    $fechaDesdeCierre = $fechaInicioTurno;

    if ($idTurno > 0) {
        $stmtTurno = $connect->prepare('SELECT fecha_inicio FROM turnos_iniciados WHERE id = ? LIMIT 1');
        $stmtTurno->execute([$idTurno]);
        $inicio = $stmtTurno->fetchColumn();
        if ($inicio) {
            $fechaInicioTurno = (string) $inicio;
            $fechaDesdeCierre = $fechaInicioTurno;
        }
    }

    $facturas = medidata_facturas_cierre_turno(
        $connect,
        $usuario,
        $nombre,
        $fechaInicioTurno,
        $fechaCierre,
        $fechaDesdeCierre
    );

    $metodos = medidata_totales_por_metodo_desde_facturas($facturas);

    return [
        'total_ventas' => array_sum(array_column($facturas, 'total_price')),
        'facturas_cobradas' => count($facturas),
        'total_por_metodo' => json_encode($metodos),
    ];
}
