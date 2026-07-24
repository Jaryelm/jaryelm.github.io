-- Migration DOWN: revertir la categoría 'License'
-- (Reasignar antes cualquier tipo con category='License' a 'Permission' para no perder filas.)
USE `medic9ue_hr_leaves`;

UPDATE `hr_absence_types` SET `category` = 'Permission' WHERE `category` = 'License';

ALTER TABLE `hr_absence_types`
MODIFY COLUMN `category` ENUM('Vacation', 'Permission', 'Medical_Leave') NOT NULL;
