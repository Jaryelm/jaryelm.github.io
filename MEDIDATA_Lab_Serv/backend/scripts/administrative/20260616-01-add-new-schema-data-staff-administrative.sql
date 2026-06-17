-- Script: 20260616-01-add-new-schema-data-staff-administrative.sql
-- Description: Adds new columns to staff_administrative table for comprehensive HR management.

ALTER TABLE `staff_administrative`
ADD COLUMN `num_empleado` VARCHAR(50) NULL COMMENT 'Número de empleado / Expediente institucional',
ADD COLUMN `tipo_empleado` ENUM('Permanente', 'Temporal', 'Tiempo parcial') NOT NULL DEFAULT 'Permanente' COMMENT 'Tipo de Empleado',
ADD COLUMN `duracion_contrato` VARCHAR(100) NULL COMMENT 'Duración de la contratación (si es temporal o tiempo parcial)',
ADD COLUMN `fecha_ingreso` DATE NULL COMMENT 'Fecha que inicia su trabajo en Medicasa',
ADD COLUMN `cuenta_bac` VARCHAR(50) NULL COMMENT 'Número de cuenta de BAC',
ADD COLUMN `id_departamento` INT NULL COMMENT 'ID de departamento',
ADD COLUMN `id_cargo` INT NULL COMMENT 'ID de cargo (para lista de cargos)',
ADD COLUMN `id_horario` INT NULL COMMENT 'ID de horario',
ADD COLUMN `salario` DECIMAL(10,2) NULL COMMENT 'Salario de la persona',
ADD COLUMN `id_salary_level` INT NULL COMMENT 'Enlace con tabla salary_levels de RRHH',
ADD COLUMN `telefono` VARCHAR(20) NULL COMMENT 'Contacto / Teléfono celular',
ADD COLUMN `correo_personal` VARCHAR(100) NULL COMMENT 'Correo personal del empleado',
ADD COLUMN `correo_institucional` VARCHAR(100) NULL COMMENT 'Correo institucional (opcional)',
ADD COLUMN `num_locker` VARCHAR(20) NULL COMMENT 'Número de locker asignado',
ADD COLUMN `id_biometrico` INT NULL COMMENT 'ID del reloj biométrico',
ADD COLUMN `url_contrato` VARCHAR(255) NULL COMMENT 'Ruta del documento de contrato firmado',
ADD COLUMN `id_candidate_rrhh` INT NULL COMMENT 'Enlace con módulo RRHH (tabla candidates para documentos)';
