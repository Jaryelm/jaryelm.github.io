-- Revert UP migration by dropping the newly created tables

USE `medic9ue_medi_data`;

DROP TABLE IF EXISTS `hr_absence_audit_log`;
DROP TABLE IF EXISTS `hr_absence_attachments`;
DROP TABLE IF EXISTS `hr_absence_approval_logs`;
DROP TABLE IF EXISTS `hr_absence_requests`;
DROP TABLE IF EXISTS `hr_vacation_transactions`;
DROP TABLE IF EXISTS `hr_vacation_profile`;
DROP TABLE IF EXISTS `hr_holiday_calendar`;
DROP TABLE IF EXISTS `hr_absence_types`;
DROP TABLE IF EXISTS `hr_approval_delegations`;
DROP TABLE IF EXISTS `hr_approval_workflow_steps`;
DROP TABLE IF EXISTS `hr_approval_workflows`;
DROP TABLE IF EXISTS `hr_vacation_policies`;

-- NOTA: las tablas del módulo viven en la base principal `medic9ue_medi_data`;
-- NUNCA eliminar la base de datos.
