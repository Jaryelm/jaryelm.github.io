-- =====================================================================
-- Recurrencia de eventos del calendario de RRHH (estilo Google Calendar).
-- Fecha: 2026-06-17
--
-- Tabla: medic9ue_medi_rrhh_interviews.rrhh_custom_events
--
-- Agrega:
--   recurrence        -> regla de repeticion a nivel de serie
--                        ('none','daily','weekly','monthly','yearly','weekdays')
--   recurrence_until  -> fecha de fin opcional (NULL = indefinido, con tope
--                        interno de expansion en el servidor/cliente)
--
-- Idempotente: usa ADD COLUMN IF NOT EXISTS.
-- =====================================================================

USE `medic9ue_medi_rrhh_interviews`;

ALTER TABLE `rrhh_custom_events`
    ADD COLUMN `recurrence` VARCHAR(20) NOT NULL DEFAULT 'none' AFTER `is_public`;

ALTER TABLE `rrhh_custom_events`
    ADD COLUMN `recurrence_until` DATE NULL DEFAULT NULL AFTER `recurrence`;
