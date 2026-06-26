-- Migración para agregar estado a la tabla nurse (Enfermería)
-- Para manejar activos e inactivos al igual que Administrativos y Servicios Generales.
USE `medic9ue_medi_data`;

SET @col_nurse := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'nurse'
      AND COLUMN_NAME = 'state'
);
SET @sql_nurse := IF(@col_nurse = 0,
    'ALTER TABLE `nurse` ADD COLUMN `state` TINYINT(1) NOT NULL DEFAULT 1 AFTER `nacinur`',
    'SELECT "nurse.state ya existe"'
);
PREPARE stmt_nurse FROM @sql_nurse;
EXECUTE stmt_nurse;
DEALLOCATE PREPARE stmt_nurse;
