-- Revierte la inmutabilidad de la bitácora (quita los triggers de bloqueo).
USE `medic9ue_medi_data`;

DROP TRIGGER IF EXISTS `trg_audit_log_no_update`;
DROP TRIGGER IF EXISTS `trg_audit_log_no_delete`;
