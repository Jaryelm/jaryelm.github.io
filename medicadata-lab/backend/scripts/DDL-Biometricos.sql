USE `medic9ue_medi_data`;
-- ALTER TABLE doctor 
--     ADD COLUMN employeeCode VARCHAR(15);

-- ALTER TABLE doctor 
--     ADD CONSTRAINT uqk_employee_code UNIQUE (employeeCode);

-- ALTER TABLE doctor 
--     ADD INDEX idx_employee_code (employeeCode);    

-- ALTER TABLE nurse
-- 	ADD COLUMN employeeCode VARCHAR(15);
    
-- ALTER TABLE nurse
-- 	ADD CONSTRAINT uqk_employee_code UNIQUE (employeeCode);

-- ALTER TABLE nurse
-- 	ADD CONSTRAINT idx_employee_code INDEX (employeeCode);
    
-- ALTER TABLE users
-- 	ADD COLUMN employeeCode VARCHAR(15);
    
-- ALTER TABLE users
-- 	ADD CONSTRAINT uqk_employee_code UNIQUE (employeeCode);

-- ALTER TABLE users
-- 	ADD CONSTRAINT idx_employee_code INDEX (employeeCode);
    
CREATE TABLE bio_employee (
	id INT AUTO_INCREMENT PRIMARY KEY,
	employeeType ENUM("Doctor","Nurse","User"),
	idEmployee INT NOT NULL.
	employeeCode VARCHAR(15) NOT NULL,

	UNIQUE KEY unq_employee_code (employeeType, idEmployee, employeeCode),
	INDEX idx_employee_type (employeeType),
	INDEX idx_employee_id (idEmployee),
	INDEX idx_employee_code (employeeCode)
);

CREATE TABLE attendance_log_employee (
	idatt BIGINT AUTO_INCREMENT PRIMARY KEY,
	employeeCode INT NOT NULL,
	typeDailing ENUM("Entrada","Salida","Salida_Almuerzo","Entrada_Almuerzo") NOT NULL,
	dTime DATETIME NOT NULL,
	
	-- Campos de auditoría
	created_by VARCHAR(150) NOT NULL DEFAULT 'SYSTEM_SYNC',
	updated_by VARCHAR(150),
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
	isDeleted BOOL DEFAULT 0,
	
	-- Evita duplicados si sincronizas varias veces los mismos datos
	UNIQUE KEY unq_employee_time (employeeCode, dTime),
	INDEX idx_employee (employeeCode),
	INDEX idx_date (dTime)
);