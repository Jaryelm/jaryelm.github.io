-- =============================================================================
-- RRHH · Ampliar job_profile_file: TINYBLOB (255 B) → MEDIUMBLOB (16 MB)
-- =============================================================================
-- TINYBLOB solo admite 255 bytes; un .docx/.pdf de perfil de puesto lo supera
-- y provoca: SQLSTATE[22001] Data too long for column 'job_profile_file'
--
-- Ejecutar en medic9ue_medi_rrhh_interviews (local y producción).
-- =============================================================================

USE `medic9ue_medi_rrhh_interviews`;

ALTER TABLE `positions_details`
  MODIFY COLUMN `job_profile_file` MEDIUMBLOB DEFAULT NULL;
