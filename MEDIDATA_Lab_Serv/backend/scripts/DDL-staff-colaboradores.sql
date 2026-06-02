-- Personal administrativo y servicios generales (colaboradores RRHH)
USE `medic9ue_medi_data`;

CREATE TABLE IF NOT EXISTS `staff_administrative` (
  `idadm` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `numide` char(14) COLLATE utf8_unicode_ci NOT NULL,
  `nomadm` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
  `apeadm` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
  `nacadm` date NOT NULL,
  `sexadm` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `cargo` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `fere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idadm`),
  UNIQUE KEY `uq_staff_administrative_numide` (`numide`),
  KEY `idx_staff_administrative_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_general_services` (
  `idsg` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `numide` char(14) COLLATE utf8_unicode_ci NOT NULL,
  `nomsg` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
  `apesg` varchar(35) COLLATE utf8_unicode_ci NOT NULL,
  `nacsg` date NOT NULL,
  `sexsg` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `area` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `state` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `fere` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idsg`),
  UNIQUE KEY `uq_staff_general_services_numide` (`numide`),
  KEY `idx_staff_general_services_user` (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Si las tablas ya existían sin id_user:
-- ALTER TABLE staff_administrative ADD COLUMN id_user INT NULL DEFAULT NULL AFTER idadm, ADD KEY idx_staff_administrative_user (id_user);
-- ALTER TABLE staff_general_services ADD COLUMN id_user INT NULL DEFAULT NULL AFTER idsg, ADD KEY idx_staff_general_services_user (id_user);
