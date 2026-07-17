-- Revert UP migration by dropping the newly created tables

DROP TABLE IF EXISTS `hr_absence_audit_log`;
DROP TABLE IF EXISTS `hr_absence_attachments`;
DROP TABLE IF EXISTS `hr_absence_approvals`;
DROP TABLE IF EXISTS `hr_absence_requests`;
DROP TABLE IF EXISTS `hr_vacation_transactions`;
DROP TABLE IF EXISTS `hr_vacation_profile`;
DROP TABLE IF EXISTS `hr_holiday_calendar`;
DROP TABLE IF EXISTS `hr_absence_types`;
DROP TABLE IF EXISTS `hr_vacation_policies`;
