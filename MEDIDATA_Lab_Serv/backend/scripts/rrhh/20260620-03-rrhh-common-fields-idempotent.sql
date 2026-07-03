-- =====================================================================
-- 20260620-03-rrhh-common-fields-idempotent.sql
-- Agrega los campos comunes de RRHH a las tablas de personal
-- (staff_administrative, staff_general_services, nurse, doctor).
--
-- Version IDEMPOTENTE (MariaDB): usa ADD COLUMN IF NOT EXISTS, por lo
-- que es segura de re-ejecutar y NO falla aunque el bootstrap de staff
-- (staff_colaborador_bootstrap.php) ya haya creado id_user / fecha_ingreso.
--
-- Reemplaza a las migraciones originales de Joan:
--   - 20260616-01-add-new-schema-data-staff-administrative.sql
--   - 20260620-02-rrhh-common-fields-all.sql
--
-- Requiere MariaDB (XAMPP / cPanel). En MySQL 8 NO existe
-- "ADD COLUMN IF NOT EXISTS"; en ese caso correr las migraciones
-- originales asegurandose de no duplicar id_user / fecha_ingreso.
-- =====================================================================

USE `medic9ue_medi_data`;

-- ---------------------------------------------------------------
-- staff_administrative  (url_* como MEDIUMBLOB)
-- ---------------------------------------------------------------
ALTER TABLE `staff_administrative`
  ADD COLUMN IF NOT EXISTS `num_empleado` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tipo_empleado` varchar(50) NOT NULL DEFAULT 'Permanente',
  ADD COLUMN IF NOT EXISTS `duracion_contrato` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_ingreso` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cuenta_bac` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_departamento` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_cargo` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_horario` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `salario` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_salary_level` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `telefono` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_personal` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_institucional` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_locker` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_biometrico` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_contrato` mediumblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_solicitud` mediumblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_psicometricas` mediumblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_candidate_rrhh` int(11) DEFAULT NULL;

-- ---------------------------------------------------------------
-- staff_general_services  (url_* como longblob)
-- ---------------------------------------------------------------
ALTER TABLE `staff_general_services`
  ADD COLUMN IF NOT EXISTS `id_user` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_empleado` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tipo_empleado` varchar(50) DEFAULT 'Permanente',
  ADD COLUMN IF NOT EXISTS `duracion_contrato` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_ingreso` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_departamento` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_cargo` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_horario` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_salary_level` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `salario` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cuenta_bac` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `telefono` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_personal` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_institucional` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_locker` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_biometrico` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_contrato` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_solicitud` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_psicometricas` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_candidate_rrhh` int(11) DEFAULT NULL;

-- ---------------------------------------------------------------
-- nurse
-- ---------------------------------------------------------------
ALTER TABLE `nurse`
  ADD COLUMN IF NOT EXISTS `id_user` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_empleado` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tipo_empleado` varchar(50) DEFAULT 'Permanente',
  ADD COLUMN IF NOT EXISTS `duracion_contrato` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_ingreso` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_departamento` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_cargo` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_horario` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_salary_level` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `salario` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cuenta_bac` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `telefono` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_personal` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_institucional` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_locker` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_biometrico` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_contrato` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_solicitud` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_psicometricas` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_candidate_rrhh` int(11) DEFAULT NULL;

-- ---------------------------------------------------------------
-- doctor
-- ---------------------------------------------------------------
ALTER TABLE `doctor`
  ADD COLUMN IF NOT EXISTS `id_user` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_empleado` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `tipo_empleado` varchar(50) DEFAULT 'Permanente',
  ADD COLUMN IF NOT EXISTS `duracion_contrato` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_ingreso` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_departamento` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_cargo` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_horario` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_salary_level` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `salario` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cuenta_bac` varchar(50) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `telefono` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_personal` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `correo_institucional` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `num_locker` varchar(20) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_biometrico` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_contrato` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_solicitud` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `url_psicometricas` longblob DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `id_candidate_rrhh` int(11) DEFAULT NULL;
