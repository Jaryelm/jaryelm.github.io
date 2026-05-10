<?php
/**
 * Partida contable automática al registrar compra en almacén (todos los términos de pago).
 * Inventarios + ISV al debe; Proveedores Comerciales al haber. Código: COMPRA_PROVEEDOR.
 */

require_once __DIR__ . '/funciones_diario_general.php';

/** Haber siempre Proveedores Comerciales (ejemplo cliente: Pda registro de compras). */
function medidata_cuenta_haber_registro_compra(): string
{
    return '210200107';
}

/**
 * Genera la partida y actualiza compras.numero_partida_contable.
 * Aplica a cualquier cred_cont (Crédito, Contado, Prima, Consignación, etc.).
 * Idempotente si ya existe numero_partida_contable o ya hay líneas en diario con referencia COMP-{id}.
 *
 * @return string número de partida
 */
function medidata_generar_partida_desde_compra(
    PDO $connect,
    int $idCompra,
    string $usuarioNombre,
    string $unidadServicio = 'Hospital Medicasa'
): string {
    $st = $connect->prepare('SELECT * FROM compras WHERE id_compra = ? LIMIT 1');
    $st->execute([$idCompra]);
    $compra = $st->fetch(PDO::FETCH_ASSOC);
    if (!$compra) {
        throw new Exception('Compra no encontrada (id ' . $idCompra . ').');
    }

    $ref = 'COMP-' . $idCompra;

    if (!empty($compra['numero_partida_contable'] ?? '')) {
        return (string) $compra['numero_partida_contable'];
    }

    $qEx = $connect->prepare(
        'SELECT numero_partida FROM diario_general_transacciones
         WHERE referencia = ? AND tipo_transaccion = \'COMPRA_PROVEEDOR\'
         ORDER BY id ASC LIMIT 1'
    );
    $qEx->execute([$ref]);
    $rowEx = $qEx->fetch(PDO::FETCH_ASSOC);
    if ($rowEx && !empty($rowEx['numero_partida'])) {
        $np = (string) $rowEx['numero_partida'];
        $up = $connect->prepare(
            'UPDATE compras SET numero_partida_contable = ? WHERE id_compra = ? AND (numero_partida_contable IS NULL OR numero_partida_contable = \'\') LIMIT 1'
        );
        $up->execute([$np, $idCompra]);
        return $np;
    }

    $st2 = $connect->prepare(
        'SELECT cat_cuenta, subtotal, isv, total_item, descripcion
         FROM detalle_compras WHERE id_compra = ?'
    );
    $st2->execute([$idCompra]);
    $lineas = $st2->fetchAll(PDO::FETCH_ASSOC);
    if (!$lineas) {
        throw new Exception('La compra no tiene líneas en detalle_compras.');
    }

    $subTotalHeader = round((float) ($compra['sub_total'] ?? 0), 2);
    $isvHeader = round((float) ($compra['isv_global'] ?? 0), 2);
    $totalHeader = round((float) ($compra['total'] ?? 0), 2);

    $byCuenta = [];
    foreach ($lineas as $ln) {
        $cuenta = trim((string) ($ln['cat_cuenta'] ?? ''));
        if ($cuenta === '') {
            $cuenta = '110400102';
        }
        $byCuenta[$cuenta] = ($byCuenta[$cuenta] ?? 0) + round((float) ($ln['subtotal'] ?? 0), 2);
    }

    $sumSub = round(array_sum($byCuenta), 2);
    $tolerance = 0.06;
    if (abs($sumSub + $isvHeader - $totalHeader) > $tolerance) {
        $byCuenta = ['110400102' => $subTotalHeader];
    }

    $fact = trim((string) ($compra['dato_fac'] ?? ''));
    $prov = trim((string) ($compra['prov_datos'] ?? ''));
    $descBase = 'Compra proveedores OC ' . $idCompra
        . ($fact !== '' ? ' Fact ' . $fact : '')
        . ($prov !== '' ? ' | ' . $prov : '');

    $fechaOcc = !empty($compra['fecha_emision'])
        ? date('Y-m-d', strtotime((string) $compra['fecha_emision']))
        : date('Y-m-d', strtotime((string) ($compra['fecha_registro'] ?? 'now')));

    $fechaReg = !empty($compra['fecha_registro'])
        ? (string) $compra['fecha_registro']
        : date('Y-m-d H:i:s');

    $trans = [];
    foreach ($byCuenta as $cuentaCod => $monto) {
        if ($monto <= 0) {
            continue;
        }
        $trans[] = [
            'unidad_servicio' => $unidadServicio,
            'cuenta' => $cuentaCod,
            'nombre_cuenta' => obtenerNombreCuenta($cuentaCod),
            'descripcion' => $descBase,
            'debe' => round($monto, 2),
            'haber' => 0,
            'turno' => null,
            'usuario' => $usuarioNombre,
            'tipo_transaccion' => 'COMPRA_PROVEEDOR',
            'referencia' => $ref,
        ];
    }

    if ($isvHeader > 0.01) {
        $trans[] = [
            'unidad_servicio' => $unidadServicio,
            'cuenta' => '110600101',
            'nombre_cuenta' => obtenerNombreCuenta('110600101'),
            'descripcion' => $descBase . ' — ISV s/compras',
            'debe' => round($isvHeader, 2),
            'haber' => 0,
            'turno' => null,
            'usuario' => $usuarioNombre,
            'tipo_transaccion' => 'COMPRA_PROVEEDOR',
            'referencia' => $ref,
        ];
    }

    $haberCta = medidata_cuenta_haber_registro_compra();
    $trans[] = [
        'unidad_servicio' => $unidadServicio,
        'cuenta' => $haberCta,
        'nombre_cuenta' => obtenerNombreCuenta($haberCta),
        'descripcion' => $descBase,
        'debe' => 0,
        'haber' => $totalHeader,
        'turno' => null,
        'usuario' => $usuarioNombre,
        'tipo_transaccion' => 'COMPRA_PROVEEDOR',
        'referencia' => $ref,
    ];

    if (count($trans) < 2) {
        throw new Exception('No se pudo armar la partida contable de la compra.');
    }

    $numero = registrarPartidaCompleta($trans, $fechaOcc, $fechaReg);

    $up = $connect->prepare(
        'UPDATE compras SET numero_partida_contable = ? WHERE id_compra = ? AND (numero_partida_contable IS NULL OR numero_partida_contable = \'\') LIMIT 1'
    );
    $up->execute([$numero, $idCompra]);

    return $numero;
}
