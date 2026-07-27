USE `medic9ue_medi_data`;

-- Revertir columna de jornada laboral
ALTER TABLE hr_absence_requests
DROP COLUMN `reference_workday_hours`;
