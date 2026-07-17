-- 1. Políticas institucionales de vacaciones
CREATE TABLE IF NOT EXISTS `rrhh_vacaciones_politicas` (
  `id_politica` INT(11) NOT NULL AUTO_INCREMENT,
  `anios_min_antiguedad` INT(11) NOT NULL,
  `anios_max_antiguedad` INT(11) NOT NULL,
  `dias_otorgados` INT(11) NOT NULL,
  `estado` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id_politica`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Catálogo de tipos de ausencias (Permisos, Incapacidades, Vacaciones)
CREATE TABLE IF NOT EXISTS `rrhh_ausencias_tipos` (
  `id_tipo` INT(11) NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `categoria` ENUM('Vacaciones', 'Permiso', 'Incapacidad') NOT NULL,
  `es_remunerado` TINYINT(1) DEFAULT 1,
  `descuenta_vacaciones` TINYINT(1) DEFAULT 0,
  `requiere_documento` TINYINT(1) DEFAULT 0,
  `requiere_autorizacion_especial` TINYINT(1) DEFAULT 1,
  `estado` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Calendario de feriados
CREATE TABLE IF NOT EXISTS `rrhh_calendario_feriados` (
  `id_feriado` INT(11) NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_feriado`),
  UNIQUE KEY `idx_fecha_feriado` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ficha de perfil de vacaciones del colaborador
CREATE TABLE IF NOT EXISTS `rrhh_vacaciones_perfil` (
  `id_perfil` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `fecha_ingreso_calculo` DATE NOT NULL,
  `dias_otorgados_acumulados` DECIMAL(10,2) DEFAULT 0.00,
  `dias_disfrutados` DECIMAL(10,2) DEFAULT 0.00,
  `dias_pagados` DECIMAL(10,2) DEFAULT 0.00,
  `saldo_dias_pendientes` DECIMAL(10,2) DEFAULT 0.00,
  `fecha_ultimo_disfrute` DATE DEFAULT NULL,
  `proxima_fecha_generacion` DATE NOT NULL,
  PRIMARY KEY (`id_perfil`),
  UNIQUE KEY `idx_usuario_perfil` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Historial/Kardex de movimientos de vacaciones
CREATE TABLE IF NOT EXISTS `rrhh_vacaciones_movimientos` (
  `id_movimiento` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `tipo_movimiento` ENUM('Acreditacion_Anual', 'Consumo_Vacaciones', 'Pago_Efectivo', 'Ajuste_Manual_RRHH') NOT NULL,
  `dias_afectados` DECIMAL(10,2) NOT NULL,
  `descripcion` VARCHAR(255) NOT NULL,
  `id_solicitud_ref` INT(11) DEFAULT NULL,
  `fecha_movimiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_movimiento_usuario` (`id_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Transacciones: Solicitudes de ausencias
CREATE TABLE IF NOT EXISTS `rrhh_ausencias_solicitudes` (
  `id_solicitud` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario` INT(11) NOT NULL,
  `id_tipo` INT(11) NOT NULL,
  `fecha_inicio` DATE DEFAULT NULL,
  `fecha_fin` DATE DEFAULT NULL,
  `cantidad_dias` DECIMAL(10,2) NOT NULL,
  `comentarios` TEXT DEFAULT NULL,
  `institucion_emisora` VARCHAR(255) DEFAULT NULL,
  `numero_incapacidad` VARCHAR(100) DEFAULT NULL,
  `estado_solicitud` ENUM('Pendiente', 'Aprobada', 'Rechazada', 'Cancelada') DEFAULT 'Pendiente',
  `id_aprobador_final` INT(11) DEFAULT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_solicitud`),
  KEY `idx_solicitud_usuario` (`id_usuario`),
  KEY `idx_solicitud_estado` (`estado_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Historial de flujos de aprobación (Multinivel)
CREATE TABLE IF NOT EXISTS `rrhh_ausencias_aprobaciones` (
  `id_aprobacion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` INT(11) NOT NULL,
  `nivel_aprobacion` INT(11) NOT NULL DEFAULT 1 COMMENT '1=Jefe, 2=RRHH, 3=Gerencia',
  `id_usuario_aprobador` INT(11) NOT NULL,
  `estado_decision` ENUM('Aprobado', 'Rechazado') NOT NULL,
  `comentarios` TEXT DEFAULT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_aprobacion`),
  KEY `idx_aprobacion_solicitud` (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Documentos adjuntos (1 a N)
CREATE TABLE IF NOT EXISTS `rrhh_ausencias_adjuntos` (
  `id_adjunto` INT(11) NOT NULL AUTO_INCREMENT,
  `id_solicitud` INT(11) NOT NULL,
  `tipo_adjunto` VARCHAR(100) DEFAULT NULL,
  `nombre_archivo_original` VARCHAR(255) NOT NULL,
  `ruta_archivo` VARCHAR(255) NOT NULL,
  `formato` VARCHAR(50) DEFAULT NULL,
  `fecha_subida` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_adjunto`),
  KEY `idx_adjunto_solicitud` (`id_solicitud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Bitácora estricta de auditoría
CREATE TABLE IF NOT EXISTS `rrhh_ausencias_auditoria` (
  `id_log` INT(11) NOT NULL AUTO_INCREMENT,
  `id_usuario_accion` INT(11) NOT NULL,
  `accion_ejecutada` VARCHAR(255) NOT NULL,
  `tabla_afectada` VARCHAR(100) NOT NULL,
  `registro_id` INT(11) DEFAULT NULL,
  `valor_anterior` TEXT DEFAULT NULL,
  `valor_nuevo` TEXT DEFAULT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_auditoria_fecha` (`fecha_hora`),
  KEY `idx_auditoria_usuario` (`id_usuario_accion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
