<?php
declare(strict_types=1);

/**
 * Cuentas de inventario permitidas en compras de almacén (perfil Compras).
 * Evita selección de cuentas inválidas y partidas con cuenta 0.
 */

if (!function_exists('medidata_cuentas_compra_inventario_codigos')) {
    /** @return list<string> */
    function medidata_cuentas_compra_inventario_codigos(): array
    {
        return ['110400102', '110400103'];
    }
}

if (!function_exists('medidata_extraer_codigo_cuenta_cat')) {
    function medidata_extraer_codigo_cuenta_cat(string $valor): string
    {
        $valor = trim($valor);
        if ($valor === '' || $valor === '0') {
            return '';
        }
        if (preg_match('/\b(\d{6,12})\b/', $valor, $m)) {
            return $m[1];
        }
        return '';
    }
}

if (!function_exists('medidata_cuenta_compra_inventario_permitida')) {
    function medidata_cuenta_compra_inventario_permitida(string $valorCatCuenta): bool
    {
        $codigo = medidata_extraer_codigo_cuenta_cat($valorCatCuenta);
        return $codigo !== '' && in_array($codigo, medidata_cuentas_compra_inventario_codigos(), true);
    }
}

if (!function_exists('medidata_validar_cuenta_compra_inventario')) {
    function medidata_validar_cuenta_compra_inventario(string $valorCatCuenta, int $linea): string
    {
        $valor = strtoupper(trim($valorCatCuenta));
        if ($valor === '' || $valor === '0') {
            throw new Exception(
                'Línea ' . $linea . ': debe seleccionar una cuenta de inventario (Insumos o Consumibles).'
            );
        }
        if (!medidata_cuenta_compra_inventario_permitida($valor)) {
            throw new Exception(
                'Línea ' . $linea . ': solo se permiten las cuentas Inventario de Insumos (110400102) '
                . 'e Inventario de Consumibles (110400103).'
            );
        }
        return $valor;
    }
}

if (!function_exists('medidata_render_options_cuentas_compra_inventario')) {
    function medidata_render_options_cuentas_compra_inventario(PDO $connect): void
    {
        echo '<option value="">Seleccione cuenta</option>';

        $codigos = medidata_cuentas_compra_inventario_codigos();
        $placeholders = implode(',', array_fill(0, count($codigos), '?'));
        $stmt = $connect->prepare(
            "SELECT tipo_cuenta, cuenta, nombre
             FROM cuentas_catalogo
             WHERE cuenta IN ($placeholders)
             ORDER BY cuenta ASC"
        );
        $stmt->execute($codigos);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tipo = (string) ($row['tipo_cuenta'] ?? '');
            $cuenta = (string) ($row['cuenta'] ?? '');
            $nombre = (string) ($row['nombre'] ?? '');
            $value = $tipo . '  ' . $cuenta . '  ' . $nombre;
            $label = $tipo . ' - ' . $cuenta . ' - ' . $nombre;
            echo '<option value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</option>';
        }
    }
}
