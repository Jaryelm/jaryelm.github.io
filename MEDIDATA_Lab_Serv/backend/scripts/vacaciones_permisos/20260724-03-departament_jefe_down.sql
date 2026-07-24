-- Revierte la columna de jefe de departamento.
USE `medic9ue_medi_rrhh_interviews`;

ALTER TABLE `departaments`
  DROP KEY `idx_departaments_jefe`,
  DROP COLUMN `id_jefe`;
