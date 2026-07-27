-- =====================================================================
-- REVERSIÓN de flujos por departamento: restaura el esquema anterior
-- (asignación por rol y aprobadores específicos). Los datos convertidos por
-- el UP no se pueden reconstruir automáticamente.
-- =====================================================================
USE `medic9ue_medi_data`;

-- 1. Restaurar el esquema anterior de pasos
ALTER TABLE `hr_approval_workflow_steps`
  MODIFY COLUMN `approver_type` ENUM('Direct_Manager', 'Department_Manager', 'Specific_Role', 'Specific_User') NOT NULL,
  ADD COLUMN `approver_user_id` INT(11) DEFAULT NULL AFTER `approver_role_name`;

-- 2. Restaurar la tabla de asignación por rol
CREATE TABLE IF NOT EXISTS `hr_workflow_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) NOT NULL,
  `role_name` VARCHAR(100) NOT NULL COMMENT 'Nombre del rol, ej. Jefe Inmediato, Colaborador, Administrador',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_workflow_role` (`workflow_id`, `role_name`),
  CONSTRAINT `fk_wr_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `hr_approval_workflows` (`workflow_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Eliminar el mapa departamento -> flujo
DROP TABLE IF EXISTS `hr_workflow_departments`;
