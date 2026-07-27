-- Migration DOWN
USE `medic9ue_medi_data`;

ALTER TABLE `hr_approval_workflow_steps`
DROP COLUMN `approver_user_id`;

ALTER TABLE `hr_approval_workflow_steps`
CHANGE COLUMN `approver_role_name` `approver_ref_id` INT(11) DEFAULT NULL;
