-- Índices recomendados para worklist (lista de trabajo MH-PACS)
-- Ejecutar en producción si las consultas siguen lentas tras paginación en servidor.

ALTER TABLE worklist
  ADD INDEX idx_worklist_status (status),
  ADD INDEX idx_worklist_study_date (study_date),
  ADD INDEX idx_worklist_status_date (status, study_date);

-- quality_control: búsquedas por study_id (ya debería existir en optimización PACS)
-- ALTER TABLE quality_control ADD INDEX idx_quality_study_id (study_id);
