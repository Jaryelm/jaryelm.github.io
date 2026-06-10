CREATE DATABASE IF NOT EXISTS `medic9ue_medi_rrhh_interviews`;
USE `medic9ue_medi_rrhh_interviews`;

CREATE TABLE IF NOT EXISTS `rrhh_custom_events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `start_datetime` DATETIME NOT NULL,
    `end_datetime` DATETIME NOT NULL,
    `color` VARCHAR(50) DEFAULT '#035c67',
    `created_by` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `deleted` TINYINT(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
