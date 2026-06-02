<?php
/**
 * Prefetch orden idord por número de factura (evita N consultas a orders en una página).
 *
 * @param string[] $invoiceNumbers Valores únicos en referencia cuando no es solo dígitos
 * @return array<string,int> invoice_number => idord
 */
function medidata_diario_ordenes_ids_por_invoice_numbers(PDO $connect, array $invoiceNumbers): array
{
    $invoiceNumbers = array_values(array_unique(array_filter(array_map('strval', $invoiceNumbers), static function ($v) {
        return $v !== '';
    })));
    if ($invoiceNumbers === []) {
        return [];
    }
    $map = [];
    foreach (array_chunk($invoiceNumbers, 400) as $chunk) {
        $placeholders = implode(',', array_fill(0, count($chunk), '?'));
        $st = $connect->prepare("SELECT idord, invoice_number FROM orders WHERE invoice_number IN ($placeholders)");
        $st->execute($chunk);
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $inv = isset($r['invoice_number']) ? trim((string) $r['invoice_number']) : '';
            if ($inv !== '' && isset($r['idord'])) {
                $map[$inv] = (int) $r['idord'];
            }
        }
    }

    return $map;
}

/**
 * Resuelve si una fila del Diario General puede abrir detalle de compra (COMP-#) o venta (orden/factura).
 *
 * @param array<string,int> $ordenIdPorFactura Resultado opcional de medidata_diario_ordenes_ids_por_invoice_numbers()
 */
function medidata_diario_resolver_detalle(PDO $connect, ?string $tipoTransaccion, ?string $referencia, array $ordenIdPorFactura = []): array
{
    $tipo = $tipoTransaccion === null || $tipoTransaccion === ''
        ? ''
        : strtoupper(trim($tipoTransaccion));
    $ref = $referencia === null ? '' : trim((string) $referencia);

    if ($ref === '') {
        return ['modo' => '', 'id' => null];
    }

    if ($tipo === 'COMPRA_PROVEEDOR') {
        if (preg_match('/^COMP-(\d+)$/i', $ref, $m)) {
            return ['modo' => 'compra', 'id' => (int) $m[1]];
        }
        return ['modo' => '', 'id' => null];
    }

    if ($tipo === 'CIERRE_VENTA' || $tipo === 'REVERSION_ANULACION') {
        // Cierre de caja: referencia agregada, sin detalle de factura individual
        if (stripos($ref, 'Cierre ') === 0) {
            return ['modo' => '', 'id' => null];
        }
        if (ctype_digit($ref)) {
            return ['modo' => 'venta', 'id' => (int) $ref];
        }
        if ($ordenIdPorFactura !== [] && isset($ordenIdPorFactura[$ref])) {
            return ['modo' => 'venta', 'id' => $ordenIdPorFactura[$ref]];
        }
        $st = $connect->prepare('SELECT idord FROM orders WHERE invoice_number = ? LIMIT 1');
        $st->execute([$ref]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        if ($r && isset($r['idord'])) {
            return ['modo' => 'venta', 'id' => (int) $r['idord']];
        }
        return ['modo' => '', 'id' => null];
    }

    return ['modo' => '', 'id' => null];
}
