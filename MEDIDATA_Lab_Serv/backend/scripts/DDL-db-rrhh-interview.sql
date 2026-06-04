CREATE DATABASE IF NOT EXISTS `medic9ue_medi_rrhh_interviews`;
USE `medic9ue_medi_rrhh_interviews`;

CREATE TABLE IF NOT EXISTS `positions_details` (
    id INT PRIMARY KEY auto_increment,
    id_positions INT NOT NULL,
    department VARCHAR(200) NOT NULL,
    immediate_boss VARCHAR(200) NOT NULL,
    objective TEXT NOT NULL,
    main_functions TEXT NOT NULL,
    academic_requirements TEXT NOT NULL,
    required_experience TEXT NOT NULL,
    technical_competencies TEXT NOT NULL,
    soft_competencies TEXT NOT NULL,
    schedule VARCHAR(200) NOT NULL,
    shift_type VARCHAR(100) NOT NULL,
    salary_range VARCHAR(100) DEFAULT NULL,
    special_conditions TEXT DEFAULT NULL,
    suggested_psychometric_tests TEXT DEFAULT NULL,
    required_docs_hiring JSON DEFAULT NULL,
    status ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
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
    vacant_name VARCHAR(200) NOT NULL,
    requesting_department VARCHAR(200) NOT NULL,
    available_slots INT NOT NULL DEFAULT 1,
    reason TEXT NOT NULL,
    priority ENUM('Baja', 'Media', 'Alta', 'Urgente') DEFAULT 'Media',
    status ENUM('Abierta', 'En Pausa', 'Cerrada', 'Cancelada') DEFAULT 'Abierta',
    rrhh_responsible VARCHAR(200) DEFAULT NULL,
    requesting_boss VARCHAR(200) DEFAULT NULL,
    internal_observations TEXT DEFAULT NULL,
    publication_channel VARCHAR(200) DEFAULT NULL,
    benefits TEXT NOT NULL,
	init_date DATE not null,
	end_date DATE not null,
	created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,
    constraint `fk_positions_vacantsPositions` foreign key (id_position) references `positions_details` (id)
);

-- Preguntas filtro por vacante
CREATE TABLE IF NOT EXISTS `vacant_filter_questions` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_vacant INT NOT NULL,
    question TEXT NOT NULL,
    question_type ENUM('Abierta', 'Selección', 'Booleana') DEFAULT 'Abierta',
    options JSON DEFAULT NULL, -- Para tipos de selección
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE,
    CONSTRAINT `fk_filter_vacant` FOREIGN KEY (id_vacant) REFERENCES `vacant_positions` (id)
);

CREATE TABLE IF NOT EXISTS `candidates` (
	id INT primary key auto_increment,
	id_vacant_position int not null,
	fullname VARCHAR(200) not null, 
	dni VARCHAR(13) not null,
    birthdate DATE DEFAULT NULL,
    marital_status VARCHAR(50) DEFAULT NULL,
	phonenumber VARCHAR(20) not null,
	email VARCHAR(100) not null,
	direction VARCHAR(500) not null,
    academic_level VARCHAR(100) DEFAULT NULL,
    profession VARCHAR(100) DEFAULT NULL,
    previous_experience TEXT DEFAULT NULL,
    last_job VARCHAR(200) DEFAULT NULL,
    reason_leaving_last_job TEXT DEFAULT NULL,
    salary_expectation DECIMAL(10,2) DEFAULT NULL,
	isAvailability bool not null DEFAULT true,
    schedule_availability VARCHAR(200) DEFAULT NULL,
    referral_source VARCHAR(200) DEFAULT NULL,
    referred_by VARCHAR(200) DEFAULT NULL,
	status ENUM("En Espera", "Formulario Empleados", "Entrevista", "Agendado", "Entrevistado", "Pruebas Psicometricas", "Descartado", "Llenando Expediente", "Contratado") DEFAULT "En Espera",
    
    -- Gestión del Proceso (Campos adicionales del PDF sección 5)
    assigned_interviewer VARCHAR(200) DEFAULT NULL,
    interview_result TEXT DEFAULT NULL,
    rrhh_observations TEXT DEFAULT NULL,
    immediate_boss_observations TEXT DEFAULT NULL,
    psychometric_result TEXT DEFAULT NULL,
    labor_references_result TEXT DEFAULT NULL,
    overall_score DECIMAL(5,2) DEFAULT NULL,
    discard_reason TEXT DEFAULT NULL,
    discard_hire_date DATE DEFAULT NULL,

    created_by VARCHAR(100) not null,
    updated_by VARCHAR(100) default null,
    created_at TIMESTAMP default CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP null on update CURRENT_TIMESTAMP(),
    deleted bool default false,	
    constraint `fk_candidates_vacantPositions` foreign key (id_vacant_position) references `vacant_positions` (id),
    constraint `uq_candidates_vacantPositions` unique key (`id_vacant_position`, `dni`)
);

-- Respuestas a preguntas filtro
CREATE TABLE IF NOT EXISTS `candidate_filter_responses` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_candidate INT NOT NULL,
    id_question INT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    CONSTRAINT `fk_response_candidate` FOREIGN KEY (id_candidate) REFERENCES `candidates` (id),
    CONSTRAINT `fk_response_question` FOREIGN KEY (id_question) REFERENCES `vacant_filter_questions` (id)
);

-- Historial Interno / Alertas de Candidatos (Basado en DNI)
CREATE TABLE IF NOT EXISTS `candidate_internal_history` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    dni VARCHAR(13) NOT NULL UNIQUE,
    alerts TEXT DEFAULT NULL,
    internal_observations TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    created_by VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS `candidates_docs` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    id_candidate INT NOT NULL,

    -- 1. Currículum Vitae (CV)
    cv_file LONGBLOB DEFAULT NULL,
    cv_mime_type ENUM('application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') DEFAULT NULL,

    -- 2. DNI
    dni_file LONGBLOB DEFAULT NULL,
    dni_mime_type ENUM('application/pdf', 'image/jpeg', 'image/png') DEFAULT NULL,

    -- 3. Títulos o diplomas (Permite .zip o .rar para múltiples archivos)
    diplomas_file LONGBLOB DEFAULT NULL,
    diplomas_mime_type ENUM('application/pdf', 'image/jpeg', 'image/png', 'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed') DEFAULT NULL,

    -- 4. Antecedentes penales
    criminal_record_file LONGBLOB DEFAULT NULL,
    criminal_record_mime_type ENUM('application/pdf', 'image/jpeg', 'image/png') DEFAULT NULL,

    -- 5. Antecedentes policiales
    police_record_file LONGBLOB DEFAULT NULL,
    police_record_mime_type ENUM('application/pdf', 'image/jpeg', 'image/png') DEFAULT NULL,

    -- 6. Constancias laborales (Permite .zip o .rar para múltiples archivos)
    work_certificates_file LONGBLOB DEFAULT NULL,
    work_certificates_mime_type ENUM('application/pdf', 'image/jpeg', 'image/png', 'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed') DEFAULT NULL,

    -- 7. Otros documentos requeridos según el puesto (Permite .zip o .rar)
    other_docs_file LONGBLOB DEFAULT NULL,
    other_docs_mime_type ENUM('application/pdf', 'image/jpeg', 'image/png', 'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed') DEFAULT NULL,

    -- Extra: Foto de perfil (Mantenida del esquema original)
    img_profile LONGBLOB DEFAULT NULL,
    img_profile_mime_type ENUM('image/jpeg', 'image/png') DEFAULT NULL,

    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    deleted BOOL DEFAULT FALSE,
    CONSTRAINT `fk_candidatesDocs_candidates` FOREIGN KEY (`id_candidate`) REFERENCES `candidates` (id)
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

CREATE TABLE IF NOT EXISTS `departaments` (
    id INT PRIMARY KEY AUTO_INCREMENT,
    departament_code VARCHAR(100) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(700) NOT NULL,
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(10),
    status ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
    observations VARCHAR(300),
    created_by VARCHAR(100) NOT NULL,
    updated_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP(),
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP(),
    CONSTRAINT `idx_departament_code` UNIQUE KEY (`departament_code`),
    CONSTRAINT `idx_departament_name` UNIQUE KEY (`name`),
    CONSTRAINT `idx_departament_email` UNIQUE KEY (`email`),
    CONSTRAINT `idx_departament_phone` UNIQUE KEY (`phone`)
);

ALTER TABLE `departaments`
ADD head_departament VARCHAR(100) NOT NULL BEFORE description;

ALTER TABLE `positions_details`
DROP COLUMN `departament`;

ALTER TABLE `positions_details`
ADD `id_departament` INT DEFAULT NULL AFTER id_positions;

ALTER TABLE `positions_details`
ADD CONSTRAINT `fk_positionsDetails_departaments` FOREIGN KEY (`id_departament`) REFERENCES `departaments` (id);