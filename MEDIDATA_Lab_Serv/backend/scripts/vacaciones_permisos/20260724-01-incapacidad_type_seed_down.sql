-- Revierte el seed de tipos de incapacidad (solo si no tienen solicitudes asociadas).
USE `medic9ue_medi_data`;

DELETE FROM `hr_absence_types`
WHERE `code` IN ('INC-COMUN', 'INC-LABORAL')
  AND `type_id` NOT IN (SELECT DISTINCT `type_id` FROM `hr_absence_requests`);
