-- Migración para unificar la collation de las tablas de RRHH a utf8mb4_unicode_ci
-- Esto resuelve el error: Conversion from collation utf8mb4_unicode_ci into utf8mb3_unicode_ci impossible

ALTER TABLE doctor CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE nurse CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE staff_administrative CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE staff_general_services CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE staff_medifarma CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
