-- Solo aplica a instalaciones creadas ANTES de que 20260715-01 incluyera estas
-- columnas en el CREATE TABLE base (en una instalación nueva este ALTER falla
-- con columna duplicada y puede omitirse).
USE `medic9ue_medi_data`;

ALTER TABLE hr_absence_requests
ADD COLUMN start_time TIME NULL AFTER end_date,
ADD COLUMN end_time TIME NULL AFTER start_time;
