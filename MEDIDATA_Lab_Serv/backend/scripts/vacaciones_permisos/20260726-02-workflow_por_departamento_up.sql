-- =====================================================================
-- FLUJOS POR DEPARTAMENTO: el flujo de aprobación deja de asignarse por rol
-- del usuario (hr_workflow_roles) o por aprobadores específicos (usuario X)
-- y pasa a determinarse por el DEPARTAMENTO del solicitante. El aprobador
-- "Jefe de Departamento" se resuelve dinámicamente con el jefe asignado en
-- medic9ue_medi_rrhh_interviews.departaments.id_jefe (migración 20260724-03).
--
-- Requiere haber ejecutado antes 20260726-01 (o instalación nueva ya
-- centralizada en medic9ue_medi_data).
-- =====================================================================
USE `medic9ue_medi_data`;

-- 1. Mapa departamento -> flujo (un departamento solo puede tener UN flujo).
--    department_id referencia medic9ue_medi_rrhh_interviews.departaments.id
--    (puntero lógico entre bases: sin FOREIGN KEY, se cruza en PHP).
CREATE TABLE IF NOT EXISTS `hr_workflow_departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) NOT NULL,
  `department_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wfdept_department` (`department_id`),
  KEY `idx_wfdept_workflow` (`workflow_id`),
  CONSTRAINT `fk_wfdept_workflow` FOREIGN KEY (`workflow_id`) REFERENCES `hr_approval_workflows` (`workflow_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Convertir pasos existentes al nuevo modelo:
--    - "Jefe Inmediato" y "Empleado Específico" pasan a "Jefe de Departamento"
--      (se resuelve con el jefe del departamento del solicitante).
--    - De los roles específicos solo se conservan Recursos_Humanos y
--      Administrador; cualquier otro rol libre pasa a "Jefe de Departamento".
UPDATE `hr_approval_workflow_steps`
SET `approver_type` = 'Department_Manager',
    `approver_role_name` = NULL,
    `approver_user_id` = NULL
WHERE `approver_type` IN ('Direct_Manager', 'Specific_User');

UPDATE `hr_approval_workflow_steps`
SET `approver_type` = 'Department_Manager',
    `approver_role_name` = NULL
WHERE `approver_type` = 'Specific_Role'
  AND (`approver_role_name` IS NULL OR `approver_role_name` NOT IN ('Recursos_Humanos', 'Administrador'));

-- 3. Restringir el esquema de pasos al nuevo modelo
ALTER TABLE `hr_approval_workflow_steps`
  DROP COLUMN `approver_user_id`,
  MODIFY COLUMN `approver_type` ENUM('Department_Manager', 'Specific_Role') NOT NULL;

-- 4. Eliminar la asignación de flujos por rol (modelo anterior)
DROP TABLE IF EXISTS `hr_workflow_roles`;
