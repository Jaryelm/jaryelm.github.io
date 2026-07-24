-- Migration UP: persistir la solicitud de pago en efectivo de vacaciones (vacaciones pagadas)
USE `medic9ue_hr_leaves`;

ALTER TABLE `hr_absence_requests`
ADD COLUMN `is_cash_payout` TINYINT(1) NOT NULL DEFAULT 0
COMMENT 'Solicitud de pago en efectivo de vacaciones (vacaciones pagadas)'
AFTER `days_amount`;
