<?php
declare(strict_types=1);

/**
 * Tipos de partida del Diario General editables (líneas + fecha).
 */
if (!function_exists('medidata_diario_tipos_editables')) {
    /** @return list<string> */
    function medidata_diario_tipos_editables(): array
    {
        return [
            'PARTIDA_MANUAL',
            'PAGO_PROVEEDOR',
            'PAGO_HONORARIO_MEDICO',
            'COMPRA_PROVEEDOR',
            'CIERRE_VENTA',
        ];
    }
}

if (!function_exists('medidata_diario_tipo_es_editable')) {
    function medidata_diario_tipo_es_editable(?string $tipo): bool
    {
        $t = strtoupper(trim((string) $tipo));
        return $t !== '' && in_array($t, medidata_diario_tipos_editables(), true);
    }
}

if (!function_exists('medidata_diario_puede_editar_partidas')) {
    function medidata_diario_puede_editar_partidas(?string $rol): bool
    {
        $r = trim((string) $rol);
        return in_array($r, ['Contabilidad', 'Administrador', 'Administracion', 'IT'], true);
    }
}

if (!function_exists('medidata_diario_asegurar_tabla_ediciones')) {
    function medidata_diario_asegurar_tabla_ediciones(PDO $connect): void
    {
        $connect->exec(
            'CREATE TABLE IF NOT EXISTS diario_general_ediciones (
                id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                numero_partida VARCHAR(30) NOT NULL,
                referencia VARCHAR(120) NULL,
                tipo_transaccion VARCHAR(60) NULL,
                fecha_anterior DATE NULL,
                fecha_nueva DATE NULL,
                motivo VARCHAR(255) NOT NULL,
                usuario VARCHAR(120) NOT NULL,
                username VARCHAR(120) NULL,
                ip_origen VARCHAR(64) NULL,
                detalle_json MEDIUMTEXT NULL,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_num (numero_partida)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        static $checkedDetalle = false;
        if ($checkedDetalle) {
            return;
        }
        $checkedDetalle = true;
        try {
            $has = $connect->query("SHOW COLUMNS FROM diario_general_ediciones LIKE 'detalle_json'")->fetch();
            if (!$has) {
                $connect->exec('ALTER TABLE diario_general_ediciones ADD COLUMN detalle_json MEDIUMTEXT NULL');
            }
        } catch (Throwable $e) {
            // ignore
        }
    }
}
