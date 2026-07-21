-- Tabla catálogo de posiciones (medic9ue_medi_data)
-- Prerrequisito para positions_details en medic9ue_medi_rrhh_interviews
USE `medic9ue_medi_data`;

CREATE TABLE IF NOT EXISTS `positions` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(200) NOT NULL,
    `state` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    `created_by` VARCHAR(100) DEFAULT NULL,
    `updated_by` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_positions_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
