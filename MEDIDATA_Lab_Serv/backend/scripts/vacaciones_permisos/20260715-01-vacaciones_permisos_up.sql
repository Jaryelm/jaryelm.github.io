CREATE DATABASE IF NOT EXISTS `medic9ue_hr_leaves` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medic9ue_hr_leaves`;

-- 1. Institutional Vacation Policies
CREATE TABLE IF NOT EXISTS `hr_vacation_policies` (
  `policy_id` INT(11) NOT NULL AUTO_INCREMENT,
  `min_seniority_years` INT(11) NOT NULL,
  `max_seniority_years` INT(11) NOT NULL,
  `granted_days` INT(11) NOT NULL,
  `max_accumulated_days` INT(11) DEFAULT NULL COMMENT 'Null means no limit',
  `status` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`policy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Dynamic Approval Workflows (Header)
CREATE TABLE IF NOT EXISTS `hr_approval_workflows` (
  `workflow_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL, -- e.g., 'Manager -> HR', 'Only HR'
  `description` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Dynamic Approval Workflow Steps (Details)
CREATE TABLE IF NOT EXISTS `hr_approval_workflow_steps` (
  `step_id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) NOT NULL,
  `step_order` INT(11) NOT NULL, -- 1, 2, 3... Determines the sequence
  `approver_type` ENUM('Direct_Manager', 'Department_Manager', 'Specific_Role', 'Specific_User') NOT NULL,
  `approver_ref_id` INT(11) DEFAULT NULL, -- Used ONLY if type is Specific_Role or Specific_User
  PRIMARY KEY (`step_id`),
  KEY `idx_workflow_step` (`workflow_id`),
  KEY `idx_approver_ref` (`approver_ref_id`),
  CONSTRAINT `fk_step_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `hr_approval_workflows` (`workflow_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Approval Delegations (Out of Office)
CREATE TABLE IF NOT EXISTS `hr_approval_delegations` (
  `delegation_id` INT(11) NOT NULL AUTO_INCREMENT,
  `delegator_user_id` INT(11) NOT NULL,
  `delegatee_user_id` INT(11) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`delegation_id`),
  KEY `idx_delegation_delegator` (`delegator_user_id`),
  KEY `idx_delegation_delegatee` (`delegatee_user_id`),
  KEY `idx_delegation_active` (`status`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Absence Types Catalog (Permissions, Medical Leaves, Vacations)
CREATE TABLE IF NOT EXISTS `hr_absence_types` (
  `type_id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) DEFAULT NULL, -- Links to the dynamic workflow this absence triggers
  `code` VARCHAR(50) NOT NULL, 
  `name` VARCHAR(255) NOT NULL,
  `category` ENUM('Vacation', 'Permission', 'Medical_Leave') NOT NULL,
  `is_paid` TINYINT(1) DEFAULT 1,
  `deducts_vacation` TINYINT(1) DEFAULT 0,
  `requires_document` TINYINT(1) DEFAULT 0,
  `requires_special_auth` TINYINT(1) DEFAULT 1,
  `status` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `idx_absence_type_code` (`code`),
  KEY `idx_absence_type_workflow` (`workflow_id`),
  CONSTRAINT `fk_type_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `hr_approval_workflows` (`workflow_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Holiday Calendar
CREATE TABLE IF NOT EXISTS `hr_holiday_calendar` (
  `holiday_id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`holiday_id`),
  UNIQUE KEY `idx_holiday_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Employee Vacation Profile (Base dates for calculation, balances calculated on the fly)
CREATE TABLE IF NOT EXISTS `hr_vacation_profile` (
  `profile_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `calculation_start_date` DATE NOT NULL,
  `next_generation_date` DATE NOT NULL,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `idx_vacation_profile_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Transactions: Absence Requests
CREATE TABLE IF NOT EXISTS `hr_absence_requests` (
  `request_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type_id` INT(11) NOT NULL,
  `workflow_id` INT(11) DEFAULT NULL, -- Freezes the workflow version at the time of request
  `current_step_order` INT(11) DEFAULT 1, -- Tracks where the request currently is in the workflow
  `start_date` DATE DEFAULT NULL,
  `start_time` TIME DEFAULT NULL, -- Support for hourly permissions
  `end_date` DATE DEFAULT NULL,
  `end_time` TIME DEFAULT NULL, -- Support for hourly permissions
  `actual_return_date` DATE DEFAULT NULL, -- For medical leaves that extend or end early
  `days_amount` DECIMAL(10,2) NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `issuing_institution` VARCHAR(255) DEFAULT NULL,
  `medical_leave_number` VARCHAR(100) DEFAULT NULL,
  `request_status` ENUM('Pending', 'In_Progress', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
  `final_approver_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `idx_absence_req_user` (`user_id`),
  KEY `idx_absence_req_status` (`request_status`),
  KEY `idx_absence_req_type` (`type_id`),
  KEY `idx_absence_req_workflow` (`workflow_id`),
  KEY `idx_absence_req_final_approver` (`final_approver_id`),
  CONSTRAINT `fk_req_type` FOREIGN KEY (`type_id`) REFERENCES `hr_absence_types` (`type_id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_req_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `hr_approval_workflows` (`workflow_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Vacation Transactions History (Kardex)
CREATE TABLE IF NOT EXISTS `hr_vacation_transactions` (
  `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `transaction_type` ENUM('Annual_Accrual', 'Vacation_Consumption', 'Cash_Payout', 'Manual_HR_Adjustment') NOT NULL,
  `affected_days` DECIMAL(10,2) NOT NULL, 
  `description` VARCHAR(255) NOT NULL,
  `request_id_ref` INT(11) DEFAULT NULL,
  `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_vacation_txn_user` (`user_id`),
  KEY `idx_vacation_txn_req` (`request_id_ref`),
  CONSTRAINT `fk_txn_request` FOREIGN KEY (`request_id_ref`) REFERENCES `hr_absence_requests` (`request_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Dynamic Approval Log / History (Tracks each decision per step)
CREATE TABLE IF NOT EXISTS `hr_absence_approval_logs` (
  `approval_id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `step_id` INT(11) NOT NULL, -- Links to the exact step in the workflow
  `step_order` INT(11) NOT NULL, -- Copied for easier querying (1, 2, 3...)
  `approver_user_id` INT(11) NOT NULL, -- Who actually made the decision
  `decision_status` ENUM('Approved', 'Rejected') NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_id`),
  KEY `idx_absence_approval_req` (`request_id`),
  KEY `idx_absence_approval_step` (`step_id`),
  KEY `idx_absence_approval_approver` (`approver_user_id`),
  CONSTRAINT `fk_log_req` FOREIGN KEY (`request_id`) REFERENCES `hr_absence_requests` (`request_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_step` FOREIGN KEY (`step_id`) REFERENCES `hr_approval_workflow_steps` (`step_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Attached Documents (1 to N)
CREATE TABLE IF NOT EXISTS `hr_absence_attachments` (
  `attachment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `attachment_type` VARCHAR(100) DEFAULT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `format` VARCHAR(50) DEFAULT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_absence_att_req` (`request_id`),
  CONSTRAINT `fk_att_req` FOREIGN KEY (`request_id`) REFERENCES `hr_absence_requests` (`request_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Strict Audit Log
CREATE TABLE IF NOT EXISTS `hr_absence_audit_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `action_user_id` INT(11) NOT NULL,
  `action_executed` VARCHAR(255) NOT NULL,
  `affected_table` VARCHAR(100) NOT NULL,
  `record_id` INT(11) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_absence_audit_date` (`created_at`),
  KEY `idx_absence_audit_user` (`action_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
