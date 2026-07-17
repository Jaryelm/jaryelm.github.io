-- Migration: 20260703-01-staff_medifarma_table.sql
-- Descripción: Crear tabla staff_medifarma con todos los campos necesarios
-- Fecha: 2026-07-03

USE `medic9ue_medi_data`;

CREATE TABLE IF NOT EXISTS `staff_medifarma` (
    `idmf` int(11) NOT NULL AUTO_INCREMENT,
    `id_user` int(11) DEFAULT NULL,
    `numide` char(14) COLLATE utf8mb3_unicode_ci NOT NULL,
    `nommf` varchar(35) COLLATE utf8mb3_unicode_ci NOT NULL,
    `apemf` varchar(35) COLLATE utf8mb3_unicode_ci NOT NULL,
    `nacmf` date NOT NULL,
    `sexmf` varchar(15) COLLATE utf8mb3_unicode_ci NOT NULL,
    
    -- Campos del sistema unificado
    `num_empleado` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `tipo_empleado` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT 'Permanente',
    `duracion_contrato` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `fecha_ingreso` date DEFAULT NULL,
    `id_departamento` int(11) DEFAULT NULL,
    `id_cargo` int(11) DEFAULT NULL,
    `id_horario` int(11) DEFAULT NULL,
    `id_salary_level` int(11) DEFAULT NULL,
    `salario` decimal(10,2) DEFAULT NULL,
    `cuenta_bac` varchar(50) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    
    -- Contacto y Accesos
    `telefono` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `correo_personal` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `correo_institucional` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `num_locker` varchar(20) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `id_biometrico` int(11) DEFAULT NULL,
    `id_candidate_rrhh` int(11) DEFAULT NULL,
    
    -- Documentos (URL/Path)
    `url_contrato` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `url_solicitud` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `url_psicometricas` varchar(255) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    
    `area` varchar(100) COLLATE utf8mb3_unicode_ci DEFAULT NULL,
    `state` char(1) COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT '1',
    `fere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`idmf`),
    UNIQUE KEY `uq_staff_medifarma_numide` (`numide`),
    KEY `idx_staff_medifarma_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
