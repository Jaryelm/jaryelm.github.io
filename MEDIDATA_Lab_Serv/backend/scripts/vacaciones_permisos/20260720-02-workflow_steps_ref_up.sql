-- Migration UP
USE `medic9ue_hr_leaves`;

ALTER TABLE `hr_approval_workflow_steps`
CHANGE COLUMN `approver_ref_id` `approver_role_name` VARCHAR(100) DEFAULT NULL COMMENT 'Stores string role if approver_type is Specific_Role';

ALTER TABLE `hr_approval_workflow_steps`
ADD COLUMN `approver_user_id` INT(11) DEFAULT NULL COMMENT 'Stores user ID if approver_type is Specific_User' AFTER `approver_role_name`;
