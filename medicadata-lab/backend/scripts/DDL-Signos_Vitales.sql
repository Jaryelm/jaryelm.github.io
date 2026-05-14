USE `medic9ue_medi_data`;

ALTER TABLE `signos_vitales`
    CHANGE COLUMN `procesado_por` `processed_by` VARCHAR(100),
    ADD COLUMN `reviews_by` VARCHAR(100) NULL DEFAULT NULL AFTER `processed_by`,
    
    CHANGE COLUMN `fc` `blood_pressure` VARCHAR(50), -- PA (Presión Arterial)
    CHANGE COLUMN `ta` `map_pressure` VARCHAR(15), -- PAM (Presión Arterial Media)
    CHANGE COLUMN `temp` `temperature` VARCHAR(15), -- TEMP (Temperatura)
    CHANGE COLUMN `spo` `heart_rate` VARCHAR(15), -- FC (Frecuencia Cardíaca)
    CHANGE COLUMN `peso_kg` `respiratory_rate` VARCHAR(15), -- FR (Frecuencia Respiratoria)
    CHANGE COLUMN `talla` `oxygen_saturation` VARCHAR(15), -- SAT (Saturación de Oxígeno)
    
    ADD COLUMN `weight` VARCHAR(15) AFTER `reviews_by`,
    ADD COLUMN `stature` VARCHAR(15) AFTER `weight`,
    ADD COLUMN `glucose` VARCHAR(15) AFTER `temperature`,
    
    ADD COLUMN `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    MODIFY COLUMN `fecha` DATE DEFAULT (CURRENT_DATE),
    MODIFY COLUMN `hora` TIME DEFAULT (CURRENT_TIME);
