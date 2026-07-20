<?php
declare(strict_types=1);

/**
 * Validación y normalización de proveedores comerciales contra proveedor_comercial.
 */

function medidata_proveedor_normalizar(PDO $connect, string $nombre): string
{
    $nombre = strtoupper(trim($nombre));
    if ($nombre === '' || $nombre === '0') {
        throw new RuntimeException(
            'Debe seleccionar un proveedor registrado en el Directorio Comercial.'
        );
    }

    $st = $connect->prepare(
        'SELECT nombre_empresa FROM proveedor_comercial WHERE UPPER(TRIM(nombre_empresa)) = ? LIMIT 1'
    );
    $st->execute([$nombre]);
    $exacto = $st->fetchColumn();
    if ($exacto !== false && $exacto !== null) {
        return strtoupper(trim((string) $exacto));
    }

    $st = $connect->prepare(
        'SELECT nombre_empresa FROM proveedor_comercial
         WHERE UPPER(TRIM(nombre_empresa)) LIKE CONCAT(?, " %")'
    );
    $st->execute([$nombre]);
    $coincidencias = $st->fetchAll(PDO::FETCH_COLUMN);
    if (count($coincidencias) === 1) {
        return strtoupper(trim((string) $coincidencias[0]));
    }

    throw new RuntimeException(
        'El proveedor "' . $nombre . '" no está en el Directorio Comercial. '
        . 'Regístrelo en Contabilidad → Directorio comercial o selecciónelo de la lista.'
    );
}

function medidata_proveedor_listar(PDO $connect): array
{
    $st = $connect->query(
        'SELECT id, nombre_empresa FROM proveedor_comercial ORDER BY nombre_empresa ASC'
    );

    return $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
}
