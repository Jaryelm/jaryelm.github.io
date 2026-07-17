-- 1. Institutional Vacation Policies
CREATE TABLE IF NOT EXISTS `hr_vacation_policies` (
  `policy_id` INT(11) NOT NULL AUTO_INCREMENT,
  `min_seniority_years` INT(11) NOT NULL,
  `max_seniority_years` INT(11) NOT NULL,
  `granted_days` INT(11) NOT NULL,
  `status` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`policy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Absence Types Catalog (Permissions, Medical Leaves, Vacations)
CREATE TABLE IF NOT EXISTS `hr_absence_types` (
  `type_id` INT(11) NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL, -- e.g., 'ANNUAL_VACATION', 'MEDICAL_LEAVE', 'UNPAID_LEAVE'
  `name` VARCHAR(255) NOT NULL,
  `category` ENUM('Vacation', 'Permission', 'Medical_Leave') NOT NULL,
  `is_paid` TINYINT(1) DEFAULT 1,
  `deducts_vacation` TINYINT(1) DEFAULT 0,
  `requires_document` TINYINT(1) DEFAULT 0,
  `requires_special_auth` TINYINT(1) DEFAULT 1,
  `status` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`type_id`),
  UNIQUE KEY `idx_absence_type_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Holiday Calendar
CREATE TABLE IF NOT EXISTS `hr_holiday_calendar` (
  `holiday_id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATE NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`holiday_id`),
  UNIQUE KEY `idx_holiday_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Employee Vacation Profile (Base dates for calculation, balances calculated on the fly)
CREATE TABLE IF NOT EXISTS `hr_vacation_profile` (
  `profile_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `calculation_start_date` DATE NOT NULL,
  `next_generation_date` DATE NOT NULL,
  PRIMARY KEY (`profile_id`),
  UNIQUE KEY `idx_vacation_profile_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Vacation Transactions History (Kardex)
CREATE TABLE IF NOT EXISTS `hr_vacation_transactions` (
  `transaction_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `transaction_type` ENUM('Annual_Accrual', 'Vacation_Consumption', 'Cash_Payout', 'Manual_HR_Adjustment') NOT NULL,
  `affected_days` DECIMAL(10,2) NOT NULL, -- Positive for accrual, negative for consumption
  `description` VARCHAR(255) NOT NULL,
  `request_id_ref` INT(11) DEFAULT NULL,
  `transaction_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_vacation_txn_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Transactions: Absence Requests
CREATE TABLE IF NOT EXISTS `hr_absence_requests` (
  `request_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `type_id` INT(11) NOT NULL, -- FK to hr_absence_types
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `days_amount` DECIMAL(10,2) NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `issuing_institution` VARCHAR(255) DEFAULT NULL,
  `medical_leave_number` VARCHAR(100) DEFAULT NULL,
  `request_status` ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
  `final_approver_id` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `idx_absence_req_user` (`user_id`),
  KEY `idx_absence_req_status` (`request_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Multi-level Approval Workflow History
CREATE TABLE IF NOT EXISTS `hr_absence_approvals` (
  `approval_id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `approval_level` INT(11) NOT NULL DEFAULT 1 COMMENT '1=Manager, 2=HR, 3=General_Manager',
  `approver_user_id` INT(11) NOT NULL,
  `decision_status` ENUM('Approved', 'Rejected') NOT NULL,
  `comments` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`approval_id`),
  KEY `idx_absence_approval_req` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Attached Documents (1 to N)
CREATE TABLE IF NOT EXISTS `hr_absence_attachments` (
  `attachment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `attachment_type` VARCHAR(100) DEFAULT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `format` VARCHAR(50) DEFAULT NULL,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_absence_att_req` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Strict Audit Log
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
