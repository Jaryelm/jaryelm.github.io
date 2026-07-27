-- =====================================================================
-- CENTRALIZACIÓN: mover las tablas del módulo Vacaciones y Permisos de la
-- base separada `medic9ue_hr_leaves` a la base principal `medic9ue_medi_data`.
--
-- EJECUTAR SOLO en instalaciones donde `medic9ue_hr_leaves` ya existe con
-- datos (producción / dev con el esquema anterior). En una instalación
-- nueva NO se ejecuta: las migraciones 20260715-01 en adelante ya crean
-- todo directamente en `medic9ue_medi_data`.
--
-- RENAME TABLE entre esquemas conserva datos, índices y FKs, pero MySQL no
-- permite mover una tabla con triggers, por eso primero se eliminan los
-- triggers y el evento en el esquema origen y se recrean en el destino.
-- =====================================================================

-- 1. Quitar evento y triggers del esquema origen
DROP EVENT IF EXISTS `medic9ue_hr_leaves`.`job_update_annual_vacations`;
DROP TRIGGER IF EXISTS `medic9ue_hr_leaves`.`trg_audit_log_no_update`;
DROP TRIGGER IF EXISTS `medic9ue_hr_leaves`.`trg_audit_log_no_delete`;

-- 2. Mover las tablas (una sola sentencia = operación atómica)
RENAME TABLE
  `medic9ue_hr_leaves`.`hr_vacation_policies`      TO `medic9ue_medi_data`.`hr_vacation_policies`,
  `medic9ue_hr_leaves`.`hr_approval_workflows`     TO `medic9ue_medi_data`.`hr_approval_workflows`,
  `medic9ue_hr_leaves`.`hr_approval_workflow_steps` TO `medic9ue_medi_data`.`hr_approval_workflow_steps`,
  `medic9ue_hr_leaves`.`hr_workflow_roles`         TO `medic9ue_medi_data`.`hr_workflow_roles`,
  `medic9ue_hr_leaves`.`hr_approval_delegations`   TO `medic9ue_medi_data`.`hr_approval_delegations`,
  `medic9ue_hr_leaves`.`hr_absence_types`          TO `medic9ue_medi_data`.`hr_absence_types`,
  `medic9ue_hr_leaves`.`hr_holiday_calendar`       TO `medic9ue_medi_data`.`hr_holiday_calendar`,
  `medic9ue_hr_leaves`.`hr_vacation_profile`       TO `medic9ue_medi_data`.`hr_vacation_profile`,
  `medic9ue_hr_leaves`.`hr_absence_requests`       TO `medic9ue_medi_data`.`hr_absence_requests`,
  `medic9ue_hr_leaves`.`hr_vacation_transactions`  TO `medic9ue_medi_data`.`hr_vacation_transactions`,
  `medic9ue_hr_leaves`.`hr_absence_approval_logs`  TO `medic9ue_medi_data`.`hr_absence_approval_logs`,
  `medic9ue_hr_leaves`.`hr_absence_attachments`    TO `medic9ue_medi_data`.`hr_absence_attachments`,
  `medic9ue_hr_leaves`.`hr_absence_audit_log`      TO `medic9ue_medi_data`.`hr_absence_audit_log`;

-- 3. Recrear los triggers de inmutabilidad de la bitácora en el destino
USE `medic9ue_medi_data`;

DELIMITER $$

CREATE TRIGGER `trg_audit_log_no_update`
BEFORE UPDATE ON `hr_absence_audit_log`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La bitácora de auditoría es inmutable: no se permite modificar registros.';
END$$

CREATE TRIGGER `trg_audit_log_no_delete`
BEFORE DELETE ON `hr_absence_audit_log`
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La bitácora de auditoría es inmutable: no se permite eliminar registros.';
END$$

-- 4. Recrear el evento de acumulación anual en el destino
DROP EVENT IF EXISTS `job_update_annual_vacations`$$

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

        SET v_seniority = TIMESTAMPDIFF(YEAR, v_calc_date, v_new_vac_date);
        SET v_granted_days = 0;

        SELECT granted_days INTO v_granted_days
        FROM hr_vacation_policies
        WHERE status = 1
          AND min_seniority_years <= v_seniority
          AND max_seniority_years >= v_seniority
        LIMIT 1;

        IF v_granted_days > 0 THEN
            INSERT INTO hr_vacation_transactions (
                user_id, transaction_type, affected_days, description, transaction_date
            ) VALUES (
                v_user_id, 'Annual_Accrual', v_granted_days, CONCAT('Generación automática anual de vacaciones - Año ', v_seniority), NOW()
            );

            UPDATE hr_vacation_profile
            SET new_vacations_date = DATE_ADD(v_new_vac_date, INTERVAL 1 YEAR)
            WHERE user_id = v_user_id;
        END IF;

    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

-- 5. (Opcional, tras verificar que la app funciona) eliminar la base vacía:
-- DROP DATABASE IF EXISTS `medic9ue_hr_leaves`;
