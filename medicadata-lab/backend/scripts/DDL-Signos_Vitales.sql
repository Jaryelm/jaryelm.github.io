USE `medic9ue_medi_data`;

-- Estructura Original del ALTER para signos_vitales
ALTER TABLE `signos_vitales`
    CHANGE COLUMN `procesado_por` `processed_by` VARCHAR(100),
    ADD COLUMN `reviews_by` VARCHAR(100) NULL DEFAULT NULL AFTER `processed_by`,
    
    CHANGE COLUMN `fc` `blood_pressure` VARCHAR(50), -- PA (Presión Arterial)
    CHANGE COLUMN `ta` `map_pressure` VARCHAR(15) DEFAULT 'N/A', -- PAM (Presión Arterial Media)
    CHANGE COLUMN `temp` `temperature` VARCHAR(15), -- TEMP (Temperatura)
    CHANGE COLUMN `spo` `heart_rate` VARCHAR(15), -- FC (Frecuencia Cardíaca)
    CHANGE COLUMN `peso_kg` `respiratory_rate` VARCHAR(15), -- FR (Frecuencia Respiratoria)
    CHANGE COLUMN `talla` `oxygen_saturation` VARCHAR(15), -- SAT (Saturación de Oxígeno)
    
    ADD COLUMN `weight` VARCHAR(15) AFTER `reviews_by`,
    ADD COLUMN `stature` VARCHAR(15) AFTER `weight`,
    ADD COLUMN `glucose` VARCHAR(15) AFTER `temperature`,
    
    ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP;

-- Modificaciones de columnas adicionales por separado
ALTER TABLE `signos_vitales`
    MODIFY COLUMN `fecha` DATE DEFAULT (CURRENT_DATE),
    MODIFY COLUMN `hora` TIME DEFAULT (CURRENT_TIME);

-- Nueva tabla para pacientes ambulatorios (externos)
CREATE TABLE IF NOT EXISTS `signos_vitales_outpatients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_outpatient` INT NOT NULL, -- Relación con la tabla patients_ambulatorios
    `blood_pressure` VARCHAR(50) NULL, -- PA
    `map_pressure` VARCHAR(15) DEFAULT 'N/A', -- PAM (Opcional por defecto N/A)
    `temperature` VARCHAR(15) NULL,
    `glucose` VARCHAR(15) NULL,
    `heart_rate` VARCHAR(15) NULL, -- FC
    `respiratory_rate` VARCHAR(15) NULL, -- FR
    `oxygen_saturation` VARCHAR(15) NULL, -- SAT
    `weight` VARCHAR(15) NULL,
    `stature` VARCHAR(15) NULL,
    `processed_by` VARCHAR(100) NULL,
    `reviews_by` VARCHAR(100) NULL,
    `fecha` DATE DEFAULT (CURRENT_DATE),
    `hora` TIME DEFAULT (CURRENT_TIME),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Agregar constraints de FK para ambas tablas
ALTER TABLE `signos_vitales`
    ADD CONSTRAINT `fk_signos_vitales_patients`
    FOREIGN KEY (`idpa`) REFERENCES `patients`(`idpa`)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `signos_vitales_outpatients`
    ADD CONSTRAINT `fk_signos_vitales_outpatients`
    FOREIGN KEY (`id_outpatient`) REFERENCES `patients_ambulatorios`(`id`)
    ON DELETE CASCADE ON UPDATE CASCADE;