-- Vistas legacy (puestos_trabajo, vacantes_trabajo, postulantes)
-- Esquema RRHH v2: sin schedule/shift_type/salary_range ni vacant_name.
-- Ejecutar DESPUÉS de DDL-db-rrhh-interview.sql y DDL-rrhh-joan-update-incremental.sql
USE `medic9ue_medi_rrhh_interviews`;

DROP VIEW IF EXISTS `postulantes`;
DROP VIEW IF EXISTS `vacantes_trabajo`;
DROP VIEW IF EXISTS `puestos_trabajo`;

CREATE VIEW `puestos_trabajo` AS
SELECT
    pd.id,
    p.name,
    pd.id_positions,
    pd.id_departament,
    pd.id_salary_level,
    COALESCE(d.name, pd.department) AS department,
    pd.immediate_boss,
    pd.objective,
    pd.main_functions,
    pd.academic_requirements,
    pd.required_experience,
    pd.technical_competencies,
    pd.soft_competencies,
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
INNER JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id
LEFT JOIN departaments d ON pd.id_departament = d.id;

CREATE VIEW `vacantes_trabajo` AS
SELECT
    vp.id,
    vp.id_position,
    vp.id_schedule,
    p.name AS position_name,
    COALESCE(d.name, pd.department) AS department_name,
    vp.available_slots,
    vp.reason,
    vp.priority,
    vp.status,
    vp.rrhh_responsible,
    vp.internal_observations,
    vp.benefits,
    vp.init_date,
    vp.end_date,
    vp.created_by,
    vp.updated_by,
    vp.created_at,
    vp.updated_at,
    vp.deleted
FROM vacant_positions vp
LEFT JOIN positions_details pd ON vp.id_position = pd.id
LEFT JOIN medic9ue_medi_data.positions p ON pd.id_positions = p.id
LEFT JOIN departaments d ON pd.id_departament = d.id;

CREATE VIEW `postulantes` AS
SELECT * FROM candidates;
