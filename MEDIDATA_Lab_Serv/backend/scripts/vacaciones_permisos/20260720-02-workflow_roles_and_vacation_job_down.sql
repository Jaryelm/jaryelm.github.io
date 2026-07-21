USE `medic9ue_hr_leaves`;

-- 3. Drop Scheduled Job (Event)
DROP EVENT IF EXISTS `job_update_annual_vacations`;

-- 2. Revert column rename in hr_vacation_profile
ALTER TABLE `hr_vacation_profile` 
CHANGE COLUMN `new_vacations_date` `next_generation_date` DATE NOT NULL;

-- 1. Drop table for workflow roles mapping
DROP TABLE IF EXISTS `hr_workflow_roles`;
