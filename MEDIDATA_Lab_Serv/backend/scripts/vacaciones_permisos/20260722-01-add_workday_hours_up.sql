USE `medic9ue_hr_leaves`;

-- Añadir columna para guardar la jornada laboral del empleado al momento de pedir el permiso
ALTER TABLE hr_absence_requests
ADD COLUMN `reference_workday_hours` DECIMAL(5,2) DEFAULT NULL COMMENT 'Horas de la jornada laboral del usuario al momento de la solicitud (ej. 8.00)' AFTER `end_time`;
