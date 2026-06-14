-- Vincular usuario MediDATA con ID del reloj MB360 (uid_text o uid_numeric en biometric_marcas).
-- Ejemplo: UPDATE users SET uid_biometrico = '83' WHERE id = 1;

ALTER TABLE users
  ADD COLUMN uid_biometrico VARCHAR(20) DEFAULT NULL COMMENT 'ID en reloj MB360';

CREATE INDEX idx_uid_biometrico ON users(uid_biometrico);
