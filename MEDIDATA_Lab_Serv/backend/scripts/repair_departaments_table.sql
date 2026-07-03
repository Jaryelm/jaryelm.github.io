-- Reparación: error 1932 "Table 'departaments' doesn't exist in engine"
-- Causa: metadatos MySQL huérfanos tras apagados bruscos / corrupción InnoDB.
-- Ejecutar en medic9ue_medi_rrhh_interviews (phpMyAdmin o mysql CLI).
-- NOTA: recrea la tabla vacía; los departamentos deben volver a registrarse.

USE `medic9ue_medi_rrhh_interviews`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `departaments`;

CREATE TABLE `departaments` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    departament_code VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    head_departament VARCHAR(100) NOT NULL DEFAULT '',
    description VARCHAR(700) NOT NULL,
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(10),
    phone_ext VARCHAR(10) DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
