-- Database Migration Script to split datetimes in rrhh_custom_events
-- Execute manually in your MySQL / MariaDB terminal or GUI

USE medic9ue_medi_rrhh_interviews;

-- 1. Add the new date and time columns
ALTER TABLE rrhh_custom_events 
    ADD COLUMN start_date DATE NULL AFTER title,
    ADD COLUMN start_time TIME NULL AFTER start_date,
    ADD COLUMN end_date DATE NULL AFTER start_time,
    ADD COLUMN end_time TIME NULL AFTER end_date;

-- 2. Migrate existing data from datetime columns to the new split columns
UPDATE rrhh_custom_events SET 
    start_date = DATE(start_datetime), 
    start_time = TIME(start_datetime),
    end_date = DATE(end_datetime),
    end_time = TIME(end_datetime)
WHERE id > 0 AND start_datetime IS NOT NULL;

-- 3. Drop the old datetime columns
ALTER TABLE rrhh_custom_events 
    DROP COLUMN start_datetime,
    DROP COLUMN end_datetime;
