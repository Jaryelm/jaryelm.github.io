-- Puente postulaciones web (medic9ue_postulaciones.aplica) ↔ proceso RRHH (candidates)
-- Ejecutar en el servidor correspondiente.

USE `medic9ue_postulaciones`;

ALTER TABLE `aplica`
    ADD COLUMN IF NOT EXISTS `estado_rrhh` ENUM('Pendiente','Incorporado','Descartado') NOT NULL DEFAULT 'Pendiente' AFTER `seleccionado`,
    ADD COLUMN IF NOT EXISTS `id_candidate_rrhh` INT NULL DEFAULT NULL AFTER `estado_rrhh`,
    ADD COLUMN IF NOT EXISTS `motivo_descarte` VARCHAR(500) NULL DEFAULT NULL AFTER `id_candidate_rrhh`,
    ADD COLUMN IF NOT EXISTS `fecha_gestion_rrhh` DATETIME NULL DEFAULT NULL AFTER `motivo_descarte`;

UPDATE `aplica`
SET `estado_rrhh` = 'Incorporado'
WHERE `seleccionado` = 1 AND (`estado_rrhh` IS NULL OR `estado_rrhh` = 'Pendiente');

USE `medic9ue_medi_rrhh_interviews`;

ALTER TABLE `candidates`
    ADD COLUMN IF NOT EXISTS `id_aplica` INT NULL DEFAULT NULL AFTER `id_vacant_position`;

CREATE INDEX IF NOT EXISTS `idx_candidates_id_aplica` ON `candidates` (`id_aplica`);
