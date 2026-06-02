-- Tabla de marcaciones ingeridas por el agente biométrico (sede → MediDATA central).
-- Ejecutar en MySQL de producción y de laboratorio local.

CREATE TABLE IF NOT EXISTS biometric_marcas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  site_code VARCHAR(48) NOT NULL,
  uid_text VARCHAR(64) NOT NULL DEFAULT '',
  uid_numeric VARCHAR(32) NOT NULL DEFAULT '',
  estado VARCHAR(64) NOT NULL DEFAULT '',
  marca_datetime DATETIME NOT NULL,
  device_serial VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_site_marca (site_code, uid_numeric, marca_datetime),
  KEY idx_site_datetime (site_code, marca_datetime DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
