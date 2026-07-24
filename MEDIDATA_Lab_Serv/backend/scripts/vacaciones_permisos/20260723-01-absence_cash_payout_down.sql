-- Migration DOWN: revertir la columna de pago en efectivo
USE `medic9ue_hr_leaves`;

ALTER TABLE `hr_absence_requests` DROP COLUMN `is_cash_payout`;
