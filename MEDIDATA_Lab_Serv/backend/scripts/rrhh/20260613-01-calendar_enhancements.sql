-- Database Migration Script for RRHH Calendar Enhancements
-- Execute manually in your MySQL / MariaDB terminal or GUI

USE medic9ue_medi_rrhh_interviews;

-- 1. Create Catalog for Event Types
CREATE TABLE IF NOT EXISTS rrhh_calendar_event_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    default_color VARCHAR(10) DEFAULT '#035c67'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Seed Data
INSERT IGNORE INTO rrhh_calendar_event_types (id, name, default_color) VALUES 
(1, 'Personal', '#035c67'),
(2, 'Cumpleaños', '#FC3B56'),
(3, 'Reunión', '#06adbf'),
(4, 'Capacitación', '#81D43A'),
(5, 'Otro', '#8D8D8D');

-- 2. Alter rrhh_custom_events to add new fields
-- Note: IGNORE if already executed previously
/*
ALTER TABLE rrhh_custom_events 
    ADD COLUMN id_event_type INT NULL,
    ADD COLUMN description TEXT NULL,
    ADD COLUMN all_day TINYINT(1) DEFAULT 0,
    ADD COLUMN id_user INT NULL,
    ADD COLUMN is_public TINYINT(1) DEFAULT 0;
*/

-- Add Foreign Key for event type
ALTER TABLE rrhh_custom_events 
    ADD CONSTRAINT fk_rrhh_event_type 
    FOREIGN KEY (id_event_type) REFERENCES rrhh_calendar_event_types(id) 
    ON DELETE SET NULL;

-- 3. Alter interviews table to assign specific interviewer
ALTER TABLE interviews 
    ADD COLUMN id_interviewer INT NULL AFTER id_candidate;
