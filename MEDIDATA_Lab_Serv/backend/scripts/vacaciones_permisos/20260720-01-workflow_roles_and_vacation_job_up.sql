USE `medic9ue_medi_data`;

-- 1. Create table to map workflows to specific roles
CREATE TABLE IF NOT EXISTS `hr_workflow_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) NOT NULL,
  `role_name` VARCHAR(100) NOT NULL COMMENT 'Nombre del rol, ej. Jefe Inmediato, Colaborador, Administrador',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_workflow_role` (`workflow_id`, `role_name`),
  CONSTRAINT `fk_wr_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `hr_approval_workflows` (`workflow_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Rename column in hr_vacation_profile to match requested 'new_vacations_date'
ALTER TABLE `hr_vacation_profile` 
CHANGE COLUMN `next_generation_date` `new_vacations_date` DATE NOT NULL;

-- 3. Create Scheduled Job (Event) to process annual vacations at 3 AM daily
DELIMITER //

DROP EVENT IF EXISTS `job_update_annual_vacations` //

CREATE EVENT `job_update_annual_vacations`
ON SCHEDULE EVERY 1 DAY
STARTS (CURRENT_DATE + INTERVAL 3 HOUR)
DO
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_user_id INT;
    DECLARE v_calc_date DATE;
    DECLARE v_new_vac_date DATE;
    DECLARE v_seniority INT;
    DECLARE v_granted_days INT;
    
    -- Select all employees who have reached or passed their vacation anniversary
    DECLARE cur CURSOR FOR 
        SELECT user_id, calculation_start_date, new_vacations_date 
        FROM hr_vacation_profile 
        WHERE new_vacations_date <= CURRENT_DATE;
        
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_user_id, v_calc_date, v_new_vac_date;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Calculate seniority in years from calculation_start_date to new_vacations_date
        SET v_seniority = TIMESTAMPDIFF(YEAR, v_calc_date, v_new_vac_date);
        
        -- Default granted days
        SET v_granted_days = 0;
        
        -- Get granted days from policies based on seniority
        SELECT granted_days INTO v_granted_days
        FROM hr_vacation_policies
        WHERE status = 1 
          AND min_seniority_years <= v_seniority 
          AND max_seniority_years >= v_seniority
        LIMIT 1;
        
        -- If a valid policy was found
        IF v_granted_days > 0 THEN
            -- Grant the days in Kardex
            INSERT INTO hr_vacation_transactions (
                user_id, transaction_type, affected_days, description, transaction_date
            ) VALUES (
                v_user_id, 'Annual_Accrual', v_granted_days, CONCAT('Generación automática anual de vacaciones - Año ', v_seniority), NOW()
            );
            
            -- Update profile to the next year
            UPDATE hr_vacation_profile 
            SET new_vacations_date = DATE_ADD(v_new_vac_date, INTERVAL 1 YEAR)
            WHERE user_id = v_user_id;
        END IF;
        
    END LOOP;
    
    CLOSE cur;
END //

DELIMITER ;
