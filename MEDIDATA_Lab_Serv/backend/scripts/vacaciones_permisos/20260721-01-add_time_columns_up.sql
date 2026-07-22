ALTER TABLE hr_absence_requests 
ADD COLUMN start_time TIME NULL AFTER end_date,
ADD COLUMN end_time TIME NULL AFTER start_time;
