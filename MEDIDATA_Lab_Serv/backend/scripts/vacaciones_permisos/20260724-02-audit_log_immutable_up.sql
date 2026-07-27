-- Inmutabilidad de la bitácora de auditoría (requisito 16): el historial no debe poder
-- modificarse ni eliminarse. Se bloquea a nivel de BD con triggers que abortan cualquier
-- UPDATE o DELETE sobre hr_absence_audit_log. Sólo se permite INSERT (registrar).
USE `medic9ue_medi_data`;

DROP TRIGGER IF EXISTS `trg_audit_log_no_update`;
DROP TRIGGER IF EXISTS `trg_audit_log_no_delete`;

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

DELIMITER ;
