USE `medic9ue_hr_leaves`;

-- Revertir columna de jornada laboral
ALTER TABLE hr_absence_requests
DROP COLUMN `reference_workday_hours`;
