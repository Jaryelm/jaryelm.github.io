-- Rol "Jefe inmediato" (modelo por departamento): se agrega el jefe REAL (usuario) de cada
-- departamento. Antes solo existía `head_departament` como texto libre (nombre a mano).
-- `id_jefe` referencia el id de un usuario (users.id). El jefe aprueba a los colaboradores
-- de su(s) departamento(s). Ejecutar una sola vez en producción.
USE `medic9ue_medi_rrhh_interviews`;

ALTER TABLE `departaments`
  ADD COLUMN `id_jefe` INT(11) DEFAULT NULL AFTER `head_departament`,
  ADD KEY `idx_departaments_jefe` (`id_jefe`);
