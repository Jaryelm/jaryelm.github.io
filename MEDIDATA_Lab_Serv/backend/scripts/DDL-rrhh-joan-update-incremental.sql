-- Actualización incremental RRHH (Joan) sobre instalación existente.
-- Ejecutar en phpMyAdmin o mysql CLI cuando ya tiene medic9ue_medi_rrhh_interviews de la integración previa.
-- Revisar errores "Duplicate column" / "Can't DROP" = ya aplicado, continuar.

USE `medic9ue_medi_rrhh_interviews`;

CREATE TABLE IF NOT EXISTS `departaments` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    departament_code VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    head_departament VARCHAR(100) NOT NULL DEFAULT '',
    description VARCHAR(700) NOT NULL,
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(10),
    status ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    observations VARCHAR(300),
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    UNIQUE KEY `idx_departament_code` (`departament_code`),
    UNIQUE KEY `idx_departament_name` (`name`),
    UNIQUE KEY `idx_departament_email` (`email`),
    UNIQUE KEY `idx_departament_phone` (`phone`)
);

CREATE TABLE IF NOT EXISTS `schedules` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS `schedule_details` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_schedule INT NOT NULL,
    day ENUM('Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa', 'Do') NOT NULL,
    entry_time TIME NOT NULL,
    exit_time TIME NOT NULL,
    CONSTRAINT `fk_scheduleDetails_schedules` FOREIGN KEY (id_schedule) REFERENCES `schedules`(id),
    UNIQUE KEY `uq_schedule_day` (id_schedule, day)
);

CREATE TABLE IF NOT EXISTS `salary_levels` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level_name VARCHAR(100) NOT NULL,
    position_category VARCHAR(100) NOT NULL,
    min_salary DECIMAL(10,2) NOT NULL,
    max_salary DECIMAL(10,2) NOT NULL,
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE
);

-- positions_details: migrar departamento texto -> id_departament
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'id_departament');
SET @sql := IF(@col = 0, 'ALTER TABLE `positions_details` ADD `id_departament` INT DEFAULT NULL AFTER id_positions', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'id_salary_level');
SET @sql := IF(@col = 0, 'ALTER TABLE `positions_details` ADD `id_salary_level` INT DEFAULT NULL AFTER id_departament', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'job_profile_file');
SET @sql := IF(@col = 0, 'ALTER TABLE `positions_details` ADD `job_profile_file` MEDIUMBLOB DEFAULT NULL, ADD `job_profile_mime_type` VARCHAR(100) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Si la columna ya existe como TINYBLOB (255 B), ampliar a MEDIUMBLOB
ALTER TABLE `positions_details`
  MODIFY COLUMN `job_profile_file` MEDIUMBLOB DEFAULT NULL;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'vacant_positions' AND COLUMN_NAME = 'id_schedule');
SET @sql := IF(@col = 0, 'ALTER TABLE `vacant_positions` ADD `id_schedule` INT DEFAULT NULL AFTER id_position', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- FKs (ignorar si ya existen)
SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND CONSTRAINT_NAME = 'fk_positionsDetails_departaments');
SET @sql := IF(@fk = 0, 'ALTER TABLE `positions_details` ADD CONSTRAINT `fk_positionsDetails_departaments` FOREIGN KEY (`id_departament`) REFERENCES `departaments` (id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND CONSTRAINT_NAME = 'fk_positionsDetails_salaryLevels');
SET @sql := IF(@fk = 0, 'ALTER TABLE `positions_details` ADD CONSTRAINT `fk_positionsDetails_salaryLevels` FOREIGN KEY (`id_salary_level`) REFERENCES `salary_levels` (id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'vacant_positions' AND CONSTRAINT_NAME = 'fk_vacantPositions_schedules');
SET @sql := IF(@fk = 0, 'ALTER TABLE `vacant_positions` ADD CONSTRAINT `fk_vacantPositions_schedules` FOREIGN KEY (`id_schedule`) REFERENCES `schedules` (id)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Columnas legacy (solo si aún existen)
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'departament');
SET @sql := IF(@col > 0, 'ALTER TABLE `positions_details` DROP COLUMN `departament`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'salary_range');
SET @sql := IF(@col > 0, 'ALTER TABLE `positions_details` DROP COLUMN `salary_range`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'schedule');
SET @sql := IF(@col > 0, 'ALTER TABLE `positions_details` DROP COLUMN `schedule`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'positions_details' AND COLUMN_NAME = 'shift_type');
SET @sql := IF(@col > 0, 'ALTER TABLE `positions_details` DROP COLUMN `shift_type`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'vacant_positions' AND COLUMN_NAME = 'vacant_name');
SET @sql := IF(@col > 0, 'ALTER TABLE `vacant_positions` DROP COLUMN `vacant_name`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'vacant_positions' AND COLUMN_NAME = 'publication_channel');
SET @sql := IF(@col > 0, 'ALTER TABLE `vacant_positions` DROP COLUMN `publication_channel`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'vacant_positions' AND COLUMN_NAME = 'requesting_department');
SET @sql := IF(@col > 0, 'ALTER TABLE `vacant_positions` DROP COLUMN `requesting_department`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = 'medic9ue_medi_rrhh_interviews' AND TABLE_NAME = 'vacant_positions' AND COLUMN_NAME = 'requesting_boss');
SET @sql := IF(@col > 0, 'ALTER TABLE `vacant_positions` DROP COLUMN `requesting_boss`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
