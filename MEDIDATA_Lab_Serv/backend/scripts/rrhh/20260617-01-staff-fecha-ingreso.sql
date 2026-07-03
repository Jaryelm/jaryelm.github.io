-- =====================================================================
-- Migración: agregar columna fecha_ingreso a colaboradores (RRHH)
-- Fecha: 2026-06-17
-- Objetivo: registrar la fecha de ingreso del personal administrativo y
--           de servicios generales para generar los aniversarios laborales
--           en el calendario de Recursos Humanos.
-- Idempotente: solo agrega la columna si aún no existe.
-- =====================================================================

USE `medic9ue_medi_data`;

-- staff_administrative.fecha_ingreso
SET @col_adm := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'staff_administrative'
      AND COLUMN_NAME = 'fecha_ingreso'
);
SET @sql_adm := IF(@col_adm = 0,
    'ALTER TABLE `staff_administrative` ADD COLUMN `fecha_ingreso` date DEFAULT NULL AFTER `nacadm`',
    'SELECT "staff_administrative.fecha_ingreso ya existe"'
);
PREPARE stmt_adm FROM @sql_adm;
EXECUTE stmt_adm;
DEALLOCATE PREPARE stmt_adm;

-- staff_general_services.fecha_ingreso
SET @col_sg := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'staff_general_services'
      AND COLUMN_NAME = 'fecha_ingreso'
);
SET @sql_sg := IF(@col_sg = 0,
    'ALTER TABLE `staff_general_services` ADD COLUMN `fecha_ingreso` date DEFAULT NULL AFTER `nacsg`',
    'SELECT "staff_general_services.fecha_ingreso ya existe"'
);
PREPARE stmt_sg FROM @sql_sg;
EXECUTE stmt_sg;
DEALLOCATE PREPARE stmt_sg;
