-- Arquitectura de Relación Débil para Biométricos
-- MedicaData Lab

USE `medic9ue_medi_data`;

-- 1. Tabla de mapeo de empleados (Relación Débil)
-- Esta tabla vincula los códigos del reloj biométrico con las tablas maestras
CREATE TABLE IF NOT EXISTS bio_employee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employeeType ENUM('Doctor', 'Nurse', 'User') NOT NULL,
    idEmployee INT NOT NULL,
    employeeCode VARCHAR(15) NOT NULL,

    -- Necesario para la FK: el código debe ser único en la tabla de mapeo
    UNIQUE KEY unq_bio_code (employeeCode),
    UNIQUE KEY unq_employee_identity (employeeType, idEmployee),
    INDEX idx_employee_type (employeeType),
    INDEX idx_employee_id (idEmployee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabla de registros de asistencia
-- Almacena las marcaciones brutas del dispositivo
CREATE TABLE IF NOT EXISTS attendance_log_employee (
    idatt BIGINT AUTO_INCREMENT PRIMARY KEY,
    employeeCode VARCHAR(15) NOT NULL,
    typeDailing ENUM('Entrada', 'Salida', 'Salida_Almuerzo', 'Entrada_Almuerzo') NOT NULL,
    dTime DATETIME NOT NULL,
    
    -- Campos de auditoría
    created_by VARCHAR(150) NOT NULL DEFAULT 'SYSTEM_SYNC',
    updated_by VARCHAR(150),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    isDeleted BOOL DEFAULT 0,
    
    -- Restricción de Integridad Referencial
    CONSTRAINT fk_attendance_employee_code 
        FOREIGN KEY (employeeCode) 
        REFERENCES bio_employee(employeeCode) 
        ON DELETE CASCADE ON UPDATE CASCADE,

    -- Evita duplicados en sincronizaciones repetitivas
    UNIQUE KEY unq_employee_time (employeeCode, dTime),
    INDEX idx_employee (employeeCode),
    INDEX idx_date (dTime)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
