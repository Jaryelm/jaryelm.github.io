CREATE DATABASE IF NOT EXISTS `medic9ue_medi_rrhh_interviews`;
USE `medic9ue_medi_rrhh_interviews`;

CREATE TABLE IF NOT EXISTS `positions_details` (
    id INT PRIMARY KEY auto_increment,
    id_positions INT NOT NULL,
    description VARCHAR(1000) not null,
    requirements VARCHAR(1000) not null,
    created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,
    constraint `fk_medi_data_positions` foreign key (id_positions) references `medic9ue_medi_data`.`positions` (id)
);
CREATE TABLE IF NOT EXISTS `vacant_positions` (
	id INT primary key auto_increment,
	id_position int not null,
    benefits VARCHAR(1000) not null,
	init_date DATE not null,
	end_date DATE not null,
	created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,
    constraint `fk_positions_vacantsPositions` foreign key (id_position) references `positions_details` (id)
);

CREATE TABLE IF NOT EXISTS `candidates` (
	id INT primary key auto_increment,
	id_vacant_position int not null,
	fullname VARCHAR(200) not null, 
	dni VARCHAR(13) not null,
	phonenumber VARCHAR(20) not null,
	direction VARCHAR(500) not null,
	email VARCHAR(100) not null,
	isAvailability bool not null,
	status ENUM("En Espera", "Formulario Empleados", "Entrevista", "Agendado", "Entrevistado", "Pruebas Psicometricas", "Descartado", "Llenando Expediente", "Contratado"),
    created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,	
    constraint `fk_candidates_vacantPositions` foreign key (id_vacant_position) references `vacant_positions` (id),
    constraint `uq_candidates_vacantPositions` unique key (`id_vacant_position`, `dni`)
);

create table if not exists `candidates_docs` (
	id int primary key auto_increment,
	img_profile tinyblob not null,
	cv tinyblob not null,
	id_candidate int not null,
	created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,
    constraint `fk_candidatesDocs_candidates` foreign key (`id_candidate`) references `candidates` (id)
);

CREATE TABLE IF NOT EXISTS `interviews` (
	id int primary key auto_increment,
	id_candidate int not null,
	date_interview DATE not null,
	time_interview TIME not null,
	status ENUM("Programada", "En Proceso", "Terminada"),
	created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,
    constraint `fk_interviews_candidates` foreign key (`id_candidate`) references `candidates` (id)
);

-- 1. Tabla para el Formulario de Empleados (Modelo Híbrido)
CREATE TABLE IF NOT EXISTS `employees_form` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_candidate INT NOT NULL,
    payload JSON NOT NULL, -- Aquí irá todo el contenido dinámico del formulario
    status ENUM('Borrador', 'Enviado', 'Revisado') DEFAULT 'Borrador',
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE,
    CONSTRAINT `fk_employeesForm_candidates` FOREIGN KEY (`id_candidate`) REFERENCES `candidates` (id)
);

-- 2. Tabla para el Formulario de Preguntas de RRHH (Modelo Híbrido)
CREATE TABLE IF NOT EXISTS `rrhh_questions_form` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_candidate INT NOT NULL,
    id_interview INT DEFAULT NULL, -- Vinculado a la entrevista si aplica
    payload JSON NOT NULL, -- Todas las respuestas de RRHH
    status ENUM('Pendiente', 'Completado') DEFAULT 'Pendiente',
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE,
    CONSTRAINT `fk_rrhhQuestions_candidates` FOREIGN KEY (`id_candidate`) REFERENCES `candidates` (id),
    CONSTRAINT `fk_rrhhQuestions_interviews` FOREIGN KEY (`id_interview`) REFERENCES `interviews` (id)
);

-- 3. Tabla para la Prueba Psicométrica (Campos Generales a la espera de la data)
CREATE TABLE IF NOT EXISTS `psychometric_question_form` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_candidate INT NOT NULL,
    score DECIMAL(5,2) DEFAULT NULL, -- Puntaje general provisional
    payload JSON DEFAULT NULL, -- Dejado opcional (NULL) hasta que compartan la estructura
    status ENUM('Pendiente', 'En Proceso', 'Completado') DEFAULT 'Pendiente',
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE,
    CONSTRAINT `fk_psychometric_candidates` FOREIGN KEY (`id_candidate`) REFERENCES `candidates` (id)
);

-- 4. Tabla de Requisitos de Contratación (Basada en la imagen, usando tinyblob temporalmente)
CREATE TABLE IF NOT EXISTS `hiring_requirements` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_candidate INT NOT NULL,
    
    -- Documentos solicitados
    birth_cert_children tinyblob DEFAULT NULL,     -- Copia de partida de nacimiento de hijos
    photo_id_card tinyblob DEFAULT NULL,           -- Foto (Para su Carnet)
    id_document tinyblob DEFAULT NULL,             -- Documento de identidad (revés y derecho)
    utility_bill tinyblob DEFAULT NULL,            -- Copia de recibo (agua, luz teléfono) mas reciente
    criminal_record tinyblob DEFAULT NULL,         -- Antecedentes Penales
    police_record tinyblob DEFAULT NULL,           -- Antecedentes Policiales
    personal_references tinyblob DEFAULT NULL,     -- 2 referencias personales
    professional_references tinyblob DEFAULT NULL, -- 2 referencias profesionales
    diplomas tinyblob DEFAULT NULL,                -- Diplomas o títulos recibidos
    home_sketch tinyblob DEFAULT NULL,             -- Croquis de vivienda
    
    -- Control y Auditoría
    status ENUM('Incompleto', 'En Revisión', 'Completado') DEFAULT 'Incompleto',
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE,
    CONSTRAINT `fk_hiringReq_candidates` FOREIGN KEY (`id_candidate`) REFERENCES `candidates` (id)
);