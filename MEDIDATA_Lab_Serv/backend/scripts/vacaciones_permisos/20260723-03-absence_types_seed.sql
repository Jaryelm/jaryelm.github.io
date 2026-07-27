-- Seed: tipos de permiso/incapacidad de ejemplo (RRHH puede ajustarlos luego en tipos_ausencia).
-- INSERT IGNORE por `code` (único): no duplica si ya existen.
USE `medic9ue_medi_data`;

INSERT IGNORE INTO `hr_absence_types`
  (`code`, `name`, `category`, `is_paid`, `deducts_vacation`, `requires_document`, `requires_special_auth`, `status`)
VALUES
  ('PERM-PERSONAL',   'Permiso personal',            'Permission',    1, 0, 0, 1, 1),
  ('PERM-CITAMED',    'Permiso por cita médica',     'Permission',    1, 0, 1, 1, 1),
  ('PERM-FAMILIAR',   'Permiso familiar',            'Permission',    1, 0, 0, 1, 1),
  ('PERM-SINGOCE',    'Permiso sin goce de sueldo',  'Permission',    0, 0, 0, 1, 1),
  ('PERM-DUELO',      'Permiso por duelo',           'Permission',    1, 0, 1, 1, 1),
  ('PERM-MATERNIDAD', 'Permiso por maternidad',      'Medical_Leave', 1, 0, 1, 1, 1),
  ('PERM-PATERNIDAD', 'Permiso por paternidad',      'Permission',    1, 0, 1, 1, 1);
