-- Datos extendidos editables de colaboradores (solo módulo RRHH).
-- Clave compuesta tipo + ref_id porque la lista de colaboradores es una UNION
-- de doctor / nurse / staff_administrative / staff_general_services / users.
USE `medic9ue_medi_data`;

CREATE TABLE IF NOT EXISTS `rrhh_colaborador_extra` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tipo` VARCHAR(40) COLLATE utf8_unicode_ci NOT NULL,
  `ref_id` INT(11) NOT NULL,
  `fecha_ingreso` DATE DEFAULT NULL,
  `fecha_nacimiento` DATE DEFAULT NULL,
  `cuenta_bac` VARCHAR(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `depto` VARCHAR(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cargo` VARCHAR(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `horario` VARCHAR(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `salario` VARCHAR(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nivel_salarial` VARCHAR(120) COLLATE utf8_unicode_ci DEFAULT NULL,
  `telefono` VARCHAR(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `correo_personal` VARCHAR(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `correo_institucional` VARCHAR(150) COLLATE utf8_unicode_ci DEFAULT NULL,
  `locker` VARCHAR(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `codigo_empleado` VARCHAR(60) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contrato_archivo` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contrato_nombre` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `contrato_pdf` LONGBLOB DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tipo_ref` (`tipo`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
