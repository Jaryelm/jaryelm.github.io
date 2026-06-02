<?php
/**
 * Etiqueta legible para tipo_transaccion en Diario General (MEDIDATA).
 */
function medidata_etiqueta_tipo_transaccion(?string $codigo): string
{
    $c = $codigo === null || $codigo === '' ? '' : strtoupper(trim($codigo));
    switch ($c) {
        case 'COMPRA_PROVEEDOR':
            return 'Registro de compra';
        case 'PARTIDA_MANUAL':
            return 'Partida manual';
        case 'CIERRE_VENTA':
            return 'Cierre de venta';
        case 'REVERSION_ANULACION':
            return 'Reversión / anulación';
        default:
            return $c !== '' ? $c : 'Otro / sin clasificar';
    }
}
