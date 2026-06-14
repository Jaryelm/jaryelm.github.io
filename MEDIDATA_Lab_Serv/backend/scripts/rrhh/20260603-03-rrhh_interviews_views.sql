-- Vistas de compatibilidad: nombres legacy usados en pantallas RRHH de Joan
-- Ejecutar DESPUÉS de DDL-db-rrhh-interview.sql
USE `medic9ue_medi_rrhh_interviews`;

DROP VIEW IF EXISTS `postulantes`;
DROP VIEW IF EXISTS `vacantes_trabajo`;
DROP VIEW IF EXISTS `puestos_trabajo`;

CREATE VIEW `puestos_trabajo` AS
SELECT
    pd.id,
    p.name,
    pd.id_positions,
    pd.department,
    pd.immediate_boss,
    pd.objective,
    pd.main_functions,
    pd.academic_requirements,
    pd.required_experience,
    pd.technical_competencies,
    pd.soft_competencies,
    pd.schedule,
    pd.shift_type,
    pd.salary_range,
    pd.special_conditions,
    pd.suggested_psychometric_tests,
    pd.required_docs_hiring,
    pd.status,
    pd.created_by,
    pd.updated_by,
    pd.created_at,
    pd.updated_at,
    pd.deleted
FROM positions_details pd
INNER JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id;

CREATE VIEW `vacantes_trabajo` AS
SELECT * FROM vacant_positions;

CREATE VIEW `postulantes` AS
SELECT * FROM candidates;
