-- Esqueleto laboratorio: reclutamiento / CV (no incluido en medic9ue_medi_data)
-- Importar después de medic9ue_medi_data.sql si vas a probar reclutamiento en local.
-- Ajusta la estructura de `aplica` según el dump real de producción si lo tienes.

CREATE DATABASE IF NOT EXISTS `medic9ue_postulaciones` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `medic9ue_postulaciones`;

-- Tabla mínima para que no falle login de páginas; amplía columnas según tu dump real
CREATE TABLE IF NOT EXISTS `aplica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cv` varchar(255) DEFAULT NULL COMMENT 'nombre de archivo relativo a uploads postulaciones',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
