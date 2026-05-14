-- Table Name: signos_vitales
-- Refactor to English nomenclature and fix column usage mapping
USE `medic9ue_medi_data`;

ALTER TABLE `signos_vitales`
    -- Metadata and Personnel
    CHANGE COLUMN `procesado_por` `processed_by` VARCHAR(100),
    ADD COLUMN `reviews_by` VARCHAR(100) NULL DEFAULT NULL AFTER `processed_by`,
    
    -- Clinical Parameters (Mapping historical misuse to correct names)
    CHANGE COLUMN `fc` `blood_pressure` VARCHAR(50), -- Was storing PA
    CHANGE COLUMN `ta` `map_pressure` VARCHAR(15), -- Was storing PAM
    CHANGE COLUMN `temp` `temperature` VARCHAR(15), -- Was storing TEMP
    CHANGE COLUMN `spo` `heart_rate` VARCHAR(15), -- Was storing FC
    CHANGE COLUMN `peso_kg` `respiratory_rate` VARCHAR(15), -- Was storing FR
    CHANGE COLUMN `talla` `oxygen_saturation` VARCHAR(15), -- Was storing SAT
    
    -- New Clinical Parameters
    ADD COLUMN `weight` VARCHAR(15) AFTER `reviews_by`,
    ADD COLUMN `stature` VARCHAR(15) AFTER `weight`,
    ADD COLUMN `glucose` VARCHAR(15) AFTER `temperature`,
    
    -- Timestamps
    ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    MODIFY COLUMN `fecha` DATE DEFAULT (CURRENT_DATE),
    MODIFY COLUMN `hora` TIME DEFAULT (CURRENT_TIME);

-- Note: 'created_at' and 'idpa' remain unchanged if they already exist.
-- If 'created_at' doesn't exist, it should be added as well.
