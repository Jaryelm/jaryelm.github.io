-- Migration UP: añadir la categoría 'License' (Licencia) al catálogo de tipos de ausencia
USE `medic9ue_medi_data`;

ALTER TABLE `hr_absence_types`
MODIFY COLUMN `category` ENUM('Vacation', 'Permission', 'Medical_Leave', 'License') NOT NULL;
