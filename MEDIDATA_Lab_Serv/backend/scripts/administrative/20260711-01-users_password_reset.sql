-- Recuperación de contraseña MEDIDATA (ejecutar en phpMyAdmin, BD medic9ue_medi_data)

CREATE TABLE IF NOT EXISTS `users_password_reset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `request_ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_upr_token` (`token_hash`),
  KEY `idx_upr_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users_password_reset_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_hash` char(64) NOT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_upra_login_time` (`login_hash`, `attempted_at`),
  KEY `idx_upra_ip_time` (`request_ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
