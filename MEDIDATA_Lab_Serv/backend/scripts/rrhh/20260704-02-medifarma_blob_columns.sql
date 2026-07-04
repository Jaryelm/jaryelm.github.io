-- Migración para corregir los campos de documentos en la tabla staff_medifarma
-- Pasan de ser varchar(255) a longblob para poder almacenar los archivos correctamente (igual que doctor, nurse, etc.)

ALTER TABLE staff_medifarma MODIFY url_contrato LONGBLOB;
ALTER TABLE staff_medifarma MODIFY url_solicitud LONGBLOB;
ALTER TABLE staff_medifarma MODIFY url_psicometricas LONGBLOB;
