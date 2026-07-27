-- Seed: tipo de INCAPACIDAD (categoría Medical_Leave) para el apartado de incapacidades.
-- Antes solo existía 'Permiso por maternidad' en Medical_Leave. RRHH puede ajustarlo en tipos_ausencia.
-- INSERT IGNORE por `code` (único): no duplica si ya existe.
USE `medic9ue_medi_data`;

INSERT IGNORE INTO `hr_absence_types`
  (`code`, `name`, `category`, `is_paid`, `deducts_vacation`, `requires_document`, `requires_special_auth`, `status`)
VALUES
  ('INC-COMUN',   'Incapacidad por enfermedad común', 'Medical_Leave', 1, 0, 1, 0, 1),
  ('INC-LABORAL', 'Incapacidad por riesgo laboral',   'Medical_Leave', 1, 0, 1, 0, 1);
