<?php
/**
 * Datos extendidos editables de colaboradores (solo módulo RRHH).
 * Tabla: rrhh_colaborador_extra (clave tipo + ref_id).
 */

if (!function_exists('medidata_colab_extra_text_fields')) {
    /** Campos de texto/fecha editables inline (whitelist). */
    function medidata_colab_extra_text_fields(): array
    {
        return [
            'fecha_ingreso',
            'fecha_nacimiento',
            'cuenta_bac',
            'depto',
            'cargo',
            'horario',
            'salario',
            'nivel_salarial',
            'telefono',
            'correo_personal',
            'correo_institucional',
            'locker',
            'codigo_empleado',
        ];
    }
}

if (!function_exists('medidata_colab_extra_all_columns')) {
    function medidata_colab_extra_all_columns(): array
    {
        return array_merge(medidata_colab_extra_text_fields(), ['contrato_archivo']);
    }
}

if (!function_exists('medidata_colab_extra_tipos')) {
    /** Tipos válidos (coinciden con Tipo_Empleado del fetch). */
    function medidata_colab_extra_tipos(): array
    {
        return ['Doctor', 'Enfermero', 'Administrativo', 'Servicios Generales', 'Usuario'];
    }
}

if (!function_exists('medidata_colab_extra_ensure_table')) {
    function medidata_colab_extra_ensure_table(PDO $connect): void
    {
        try {
            $connect->exec(
                "CREATE TABLE IF NOT EXISTS `rrhh_colaborador_extra` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `tipo` VARCHAR(40) NOT NULL,
                    `ref_id` INT(11) NOT NULL,
                    `fecha_ingreso` DATE DEFAULT NULL,
                    `fecha_nacimiento` DATE DEFAULT NULL,
                    `cuenta_bac` VARCHAR(60) DEFAULT NULL,
                    `depto` VARCHAR(120) DEFAULT NULL,
                    `cargo` VARCHAR(120) DEFAULT NULL,
                    `horario` VARCHAR(120) DEFAULT NULL,
                    `salario` VARCHAR(60) DEFAULT NULL,
                    `nivel_salarial` VARCHAR(120) DEFAULT NULL,
                    `telefono` VARCHAR(60) DEFAULT NULL,
                    `correo_personal` VARCHAR(150) DEFAULT NULL,
                    `correo_institucional` VARCHAR(150) DEFAULT NULL,
                    `locker` VARCHAR(60) DEFAULT NULL,
                    `codigo_empleado` VARCHAR(60) DEFAULT NULL,
                    `contrato_archivo` VARCHAR(255) DEFAULT NULL,
                    `contrato_nombre` VARCHAR(255) DEFAULT NULL,
                    `contrato_pdf` LONGBLOB DEFAULT NULL,
                    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_tipo_ref` (`tipo`, `ref_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
            );
            // Auto-sanado para tablas creadas antes de agregar el contrato BLOB.
            foreach (['contrato_nombre' => "VARCHAR(255) DEFAULT NULL", 'contrato_pdf' => 'LONGBLOB DEFAULT NULL'] as $col => $def) {
                $chk = $connect->query("SHOW COLUMNS FROM rrhh_colaborador_extra LIKE '$col'");
                if (!$chk || !$chk->fetch()) {
                    $connect->exec("ALTER TABLE rrhh_colaborador_extra ADD COLUMN `$col` $def");
                }
            }
        } catch (Throwable $e) {
            error_log('medidata_colab_extra_ensure_table: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_colab_extra_save_contrato')) {
    /** Guarda el PDF del contrato como BLOB en la tabla. */
    function medidata_colab_extra_save_contrato(PDO $connect, string $tipo, int $refId, string $binary, string $nombre): bool
    {
        if (!in_array($tipo, medidata_colab_extra_tipos(), true) || $refId <= 0) {
            return false;
        }
        medidata_colab_extra_ensure_table($connect);

        $sql = "INSERT INTO rrhh_colaborador_extra (tipo, ref_id, contrato_nombre, contrato_pdf)
                VALUES (:tipo, :ref_id, :nombre, :pdf)
                ON DUPLICATE KEY UPDATE contrato_nombre = VALUES(contrato_nombre), contrato_pdf = VALUES(contrato_pdf)";
        $stmt = $connect->prepare($sql);
        $stmt->bindValue(':tipo', $tipo, PDO::PARAM_STR);
        $stmt->bindValue(':ref_id', $refId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':pdf', $binary, PDO::PARAM_LOB);
        return $stmt->execute();
    }
}

if (!function_exists('medidata_colab_extra_upsert')) {
    /**
     * Guarda un campo del colaborador. $field debe venir de la whitelist.
     */
    function medidata_colab_extra_upsert(PDO $connect, string $tipo, int $refId, string $field, ?string $value): bool
    {
        if (!in_array($field, medidata_colab_extra_all_columns(), true)) {
            return false;
        }
        if (!in_array($tipo, medidata_colab_extra_tipos(), true) || $refId <= 0) {
            return false;
        }
        medidata_colab_extra_ensure_table($connect);

        $value = ($value === '' ? null : $value);

        $sql = "INSERT INTO rrhh_colaborador_extra (tipo, ref_id, `$field`)
                VALUES (:tipo, :ref_id, :val)
                ON DUPLICATE KEY UPDATE `$field` = VALUES(`$field`)";
        $stmt = $connect->prepare($sql);
        return $stmt->execute([':tipo' => $tipo, ':ref_id' => $refId, ':val' => $value]);
    }
}
