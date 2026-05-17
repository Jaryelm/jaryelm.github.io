-- Migración: vínculos de usuario y sello temporal para aprobación de signos vitales.
-- Ejecutar después del DDL que renombra columnas y agrega reviews_by (p. ej. DDL-Signos_Vitales.sql).

ALTER TABLE `signos_vitales`
    ADD COLUMN `processed_by_user_id` INT(11) NULL DEFAULT NULL AFTER `reviews_by`,
    ADD COLUMN `reviewed_by_user_id` INT(11) NULL DEFAULT NULL AFTER `processed_by_user_id`,
    ADD COLUMN `reviewed_at` DATETIME NULL DEFAULT NULL AFTER `reviewed_by_user_id`,
    ADD KEY `idx_signos_vit_processed_user` (`processed_by_user_id`),
    ADD KEY `idx_signos_vit_reviewed_user` (`reviewed_by_user_id`);
