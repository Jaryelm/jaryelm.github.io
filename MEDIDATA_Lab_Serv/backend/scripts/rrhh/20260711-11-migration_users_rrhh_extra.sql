-- =========================================================================
-- MIGRATION SCRIPT: users_rrhh_extra -> staff tables
-- Este script inserta la información capturada incorrectamente en users_rrhh_extra
-- hacia las tablas correspondientes de RRHH.
-- Se incluyen instrucciones ROLLBACK al final en caso de error.
-- =========================================================================

START TRANSACTION;

-- === INSERTANDO EN TABLA: staff_administrative ===
INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199313326', 'Moisés', 'Castillo', 'Masculino', '1900-01-01', 'Tiempo parcial', NULL, 4, 5, '83', NULL, '2025-06-26', '88428095', NULL, 'moises.castillo@medicasa.hn', '0', 1, (SELECT id FROM users WHERE cedula='0801199313326' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0819199000151', 'Melissa Isabel', 'Martinez Cabrera', 'Femenino', '1900-01-01', 'Permanente', 10, 28, 3, '28', '100528676', '2005-10-12', '9796-7900', NULL, 'melissa.cabrera@medicasa.hn', '45', 1, (SELECT id FROM users WHERE cedula='0819-1990-00151' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199423437', 'Carlos Fernando', 'Torres Matheu', 'Masculino', '1900-01-01', 'Permanente', 10, 12, 3, '4', '754926141', '2024-10-19', '9707-4671', NULL, 'carlos.torres@medicasa.hn', '80', 1, (SELECT id FROM users WHERE cedula='0801-1994-23437' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199314568', 'Lidia Leticia', 'Molina Godoy', 'Femenino', '1900-01-01', 'Permanente', 3, 20, 4, '10', '753755351', '2024-07-08', '3182-1484', NULL, 'lidia.molina@medicasa.hn', '35', 1, (SELECT id FROM users WHERE cedula='0801-1993-14568' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199913517', 'Andrea Sophia', 'Roque Puerto', 'Femenino', '1900-01-01', 'Permanente', 10, 11, 1, '50', '757851821', '2024-08-01', '8824-1013', NULL, 'ventas2@medicasa.hn', '3', 1, (SELECT id FROM users WHERE cedula='0801-1999-13517' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0401199800147', 'Kimberly Janeth', 'Flores Dominguez', 'Femenino', '1900-01-01', 'Permanente', 10, 11, 1, '29', '754853661', '2024-10-29', '9855-8384', NULL, 'ventascaja1@medicasa.hn', '81', 1, (SELECT id FROM users WHERE cedula='0401-1998-00147' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801198602782', 'Jose Roberto', 'Pineda Herrera', 'Masculino', '1900-01-01', 'Permanente', 10, 29, 4, '7', '748030641', '2025-08-04', '3227-6339', NULL, 'jose.pidena@medicasa.hn', '59', 1, (SELECT id FROM users WHERE cedula='0801-1986-02782' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199221409', 'Itzel Pamela', 'Andino Cruz', 'Femenino', '1900-01-01', 'Permanente', 10, 11, 1, '56', '758698681', '2025-10-16', '8965-3126', NULL, 'ventas3@medicasa.hn', '64', 1, (SELECT id FROM users WHERE cedula='0801-1992-21409' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199701937', 'Geycer Iveth', 'Montalvan Herrera', 'Femenino', '1900-01-01', 'Permanente', 22, 2, 1, '26', '743551601', '2024-09-23', '3302-1254', NULL, 'geycer.montalvan@medicasa.hn', '4', 1, (SELECT id FROM users WHERE cedula='0801-1997-01937' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('1701199900384', 'Gabriela Sofia', 'Gutiérrez Ramos', 'Femenino', '1900-01-01', 'Permanente', 10, 12, 3, '42', '756482101', '2025-09-22', '8971-3683', NULL, 'gabriela.gutierres@medicasa.hn', '57', 1, (SELECT id FROM users WHERE cedula='1701-1999-00384' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0805199600290', 'Diana Carolina', 'Oliva Chacón', 'Femenino', '1900-01-01', 'Permanente', 10, 11, 1, '30', '756512741', '2025-05-03', '3317-9458', NULL, 'caja.dianan@gmail.com', '15', 1, (SELECT id FROM users WHERE cedula='0805-1996-00290' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199801274', 'Esmeralda Stephania', 'Perez Corcio', 'Femenino', '1900-01-01', 'Permanente', 22, 25, 1, '9', '748945971', '2022-11-07', '32155239', NULL, 'esmeralda.perez@medicasa.hn', '52', 1, (SELECT id FROM users WHERE cedula='0801-1998-01274' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0307200400115', 'Jason Josiel', 'Mendonza', 'Masculino', '1900-01-01', 'Permanente', 12, 17, 1, '12', '748781611', '2025-06-23', '9848-6998', NULL, 'jason.mendoza@medicasa.hn', '73', 1, (SELECT id FROM users WHERE cedula='0307-2004-00115' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0615199200522', 'Julio Emilio', 'Portillo Ruiz', 'Masculino', '1900-01-01', 'Permanente', 6, 35, 4, '16', '750642681', '2024-03-01', '9687-9077', NULL, 'julio.portillo@medicasa.hn', '39', 1, (SELECT id FROM users WHERE cedula='0615-1992-00522' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199316976', 'Lester Danilo', 'Midence Marquez', 'Masculino', '1900-01-01', 'Permanente', 3, 22, 1, '35', '747774821', '2022-02-02', '9767-4078', NULL, 'lester.midence@medicasa.hn', '28', 1, (SELECT id FROM users WHERE cedula='0801-1993-16976' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199614192', 'Jorge Armando', 'Flores Flores', 'Masculino', '1900-01-01', 'Permanente', 3, 22, 1, '68', '748302391', '2022-07-28', '3194-7878', NULL, 'jorge.flores@medicasa.hn', '13', 1, (SELECT id FROM users WHERE cedula='0801-1996-14192' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199000980', 'Joselyn Marisol', 'Funes Alvarez', 'Femenino', '1900-01-01', 'Permanente', 10, 11, 1, '71', '750528501', '2025-11-03', '9644-5099', NULL, 'joselyn.funes@medicasa.hn', NULL, 1, (SELECT id FROM users WHERE cedula='0801-1990-00980' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0601198502925', 'Laura Amelia', 'Martinez Salinas', 'Femenino', '1900-01-01', 'Permanente', 15, 31, 5, '110', '103201498', '2011-01-01', '9885-0998', NULL, 'laura.martinez@medicasa.hn', NULL, 1, (SELECT id FROM users WHERE cedula='0601-1985-02925' LIMIT 1));

INSERT INTO `staff_administrative` (`numide`, `nomadm`, `apeadm`, `sexadm`, `nacadm`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801200107848', 'Iris Elizabeth', 'Mendoza Ponce', 'Femenino', '1900-01-01', 'Permanente', 26, 32, 3, '47', NULL, '2025-09-02', '3272-0556', NULL, 'iris.mendoza@medicasa.hn', '70', 1, (SELECT id FROM users WHERE cedula='0801-2001-07848' LIMIT 1));

-- === INSERTANDO EN TABLA: nurse ===
INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199400602', 'Francis Esmeralda', 'Medina Castellanos', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '8', '743906861', '2020-04-23', '9861-6199', NULL, 'francis.medina@medicasa.hn', '8', 1, (SELECT id FROM users WHERE cedula='0801-1994-00602' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199111518', 'Gabriela Alejandra', 'Martinez Mena', 'Femenino', '1900-01-01', 'Permanente', 8, 16, 3, '14', '747348051', '2024-01-04', '8979-4422', NULL, 'gabriela.martinez@medicasa.hn', '78', 1, (SELECT id FROM users WHERE cedula='0801-1991-11518' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0318199501034', 'Jimena Suyapa', 'Baires Herrera', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 3, '15', '745341651', '2021-03-24', '9586-8777', NULL, 'jimena.baires@medicasa.hn', '34', 1, (SELECT id FROM users WHERE cedula='0318-1995-01034' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801200022199', 'Alejandra Gisell', 'Velasquez Meza', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '18', '746320481', '2021-07-16', '3327-8536', NULL, 'alejandra.velasquez@medicasa.hn', '18', 1, (SELECT id FROM users WHERE cedula='0801-2000-22199' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801200200975', 'Josue David', 'Reyes Aguilar', 'Masculino', '1900-01-01', 'Permanente', 8, 3, 1, '19', '755401541', '2025-01-09', '3163-9847', NULL, 'josue.reyes@medicasa.hn', '62', 1, (SELECT id FROM users WHERE cedula='0801-2002-00975' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199212422', 'Keydi Jackeline', 'Martinez Martinez', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '36', '745662481', '2021-05-04', '8779-8273', NULL, 'keydi.martinez@medicasa.hn', '24', 1, (SELECT id FROM users WHERE cedula='0801-1992-12422' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801198407434', 'Jessica Patricia', 'Flores Ramirez', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '44', '741500071', '2019-06-12', '9555-8883', NULL, 'jessica.flores@medicasa.hn', '28', 1, (SELECT id FROM users WHERE cedula='0801-1984-07434' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0824200000708', 'Julio Alexander', 'Gomez Pineda', 'Masculino', '1900-01-01', 'Permanente', 8, 3, 1, '48', '751755231', '2025-07-01', '3342-7061', NULL, 'julio.gomez@medicasa.hn', '41', 1, (SELECT id FROM users WHERE cedula='0824-2000-00708' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199703518', 'Isis Claudeth', 'Chavarria Medina', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '49', '3387-2513', '2022-09-01', '3387-2513', NULL, 'isis.medina@medicasa.hn', '42', 1, (SELECT id FROM users WHERE cedula='0801-1997-03518' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0806200500354', 'Jose Alfredo', 'Jimenez Romero', 'Masculino', '1900-01-01', 'Permanente', 8, 3, 1, '53', NULL, '2026-05-04', '9537-1829', NULL, 'jose.jimenez@medicasa.hn', '84', 1, (SELECT id FROM users WHERE cedula='0806-2005-00354' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801200315223', 'July Merari', 'Fonseca Amador', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '54', '753137011', '2025-05-01', '9492-3826', NULL, 'july.fonseca@medicasa.hn', '23', 1, (SELECT id FROM users WHERE cedula='0801-2003-15223' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801200201068', 'Gabriela Esther', 'Izaguirre Bothel', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '67', '754524601', '2024-09-16', '9457-6506', NULL, 'gabriela.izaguirre@medicasa.hn', '14', 1, (SELECT id FROM users WHERE cedula='0801-2002-01068' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0703199903980', 'Johny Jusue', 'Reyes Jimenez', 'Masculino', '1900-01-01', 'Permanente', 8, 3, 1, '70', '750068711', '2023-02-18', '3156-4395', NULL, 'johny.reyes@medicasa.hn', '53', 1, (SELECT id FROM users WHERE cedula='0703-1999-03980' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0824199100887', 'Idalia Rosibel', 'Matute Gallo', 'Femenino', '1900-01-01', 'Permanente', 8, 3, 1, '73', '745660201', '2021-05-31', '3233-6564', NULL, 'idalia.matute@medicasa.hn', '17', 1, (SELECT id FROM users WHERE cedula='0824-1991-00887' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199515349', 'Gerardo Efrain', 'Lopez Perez', 'Masculino', '1900-01-01', 'Permanente', 8, 3, 1, '74', '744035331', '2020-08-01', '8905-1031', NULL, 'gerardo.lopez@medicasa.hn', '2', 1, (SELECT id FROM users WHERE cedula='0801-1995-15349' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199113638', 'Ana Rebeca', 'Ortiz Barrientos', 'Femenino', '1900-01-01', 'Permanente', 8, 16, 3, '82', '747925781', '2021-03-21', '8793-6479', NULL, 'ana.ortiz@medicasa.hn', '12', 1, (SELECT id FROM users WHERE cedula='0801-1991-13638' LIMIT 1));

INSERT INTO `nurse` (`numide`, `nomnur`, `apenur`, `sexnur`, `nacinur`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199612100', 'Kimberlyn Carolina', 'Flores Giron', 'Femenino', '1900-01-01', 'Permanente', 26, 33, 1, '46', NULL, '2026-04-06', '9488-5887', NULL, 'kimberlyn.flores@medicasa.hn', '19', 1, (SELECT id FROM users WHERE cedula='0801-1996-12100' LIMIT 1));

-- === INSERTANDO EN TABLA: staff_general_services ===
INSERT INTO `staff_general_services` (`numide`, `nomsg`, `apesg`, `sexsg`, `nacsg`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801198306798', 'Hector Ariel', 'Irias Trejo', 'Femenino', '1900-01-01', 'Permanente', 9, 24, 4, '13', '750001201', '2024-10-07', '9728-6434', NULL, 'hector.irias@medicasa.hn', '55', 1, (SELECT id FROM users WHERE cedula='0801-1983-06798' LIMIT 1));

INSERT INTO `staff_general_services` (`numide`, `nomsg`, `apesg`, `sexsg`, `nacsg`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199023025', 'Jefry Isael', 'Servellon Rios', 'Masculino', '1900-01-01', 'Permanente', 9, 38, 1, '32', '725377901', '2026-03-16', '9896-6603', NULL, 'jefry.servellon@medicasa.hn', '31', 1, (SELECT id FROM users WHERE cedula='0801-1990-23025' LIMIT 1));

INSERT INTO `staff_general_services` (`numide`, `nomsg`, `apesg`, `sexsg`, `nacsg`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('1206199800454', 'Evelin Tatiana', 'Hernandez Martinez', 'Femenino', '1900-01-01', 'Permanente', 9, 13, 1, '17', '755740201', '2025-01-23', '9239-2203', NULL, 'evelin.hernandez@medicasa.hn', '77', 1, (SELECT id FROM users WHERE cedula='1206-1998-00454' LIMIT 1));

INSERT INTO `staff_general_services` (`numide`, `nomsg`, `apesg`, `sexsg`, `nacsg`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0715198400910', 'Jose Angel', 'Fonseca Duarte', 'Masculino', '1900-01-01', 'Permanente', 9, 23, 1, '40', '752226631', '2024-01-06', '9476-1156', NULL, 'angel.fonseca@medicasa.hn', '67', 1, (SELECT id FROM users WHERE cedula='0715-1984-00910' LIMIT 1));

INSERT INTO `staff_general_services` (`numide`, `nomsg`, `apesg`, `sexsg`, `nacsg`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0803199700291', 'Carlos Fabian', 'Rivera Valdez', 'Masculino', '1900-01-01', 'Permanente', 9, 18, 3, '69', NULL, '2026-06-01', '8942-4837', NULL, 'carlos.rivera@medicasa.hn', NULL, 1, (SELECT id FROM users WHERE cedula='0803-1997-00291' LIMIT 1));

-- === INSERTANDO EN TABLA: staff_medifarma ===
INSERT INTO `staff_medifarma` (`numide`, `nommf`, `apemf`, `sexmf`, `nacmf`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199508042', 'Jose Carlos', 'Baca Romero', 'Masculino', '1900-01-01', 'Permanente', 5, 8, 4, '37', NULL, '2026-04-21', '9532-5205', NULL, 'jose.baca@medicasa.hn', '49', 1, (SELECT id FROM users WHERE cedula='0801-1995-08042' LIMIT 1));

INSERT INTO `staff_medifarma` (`numide`, `nommf`, `apemf`, `sexmf`, `nacmf`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0801199605873', 'Danny Fernando', 'Quevedo Elvir', 'Masculino', '1900-01-01', 'Permanente', 12, 17, 1, '39', '752102911', '2023-12-18', '9502-6778', NULL, 'danny.quevedo@medicasa.hn', '51', 1, (SELECT id FROM users WHERE cedula='0801-1996-05873' LIMIT 1));

INSERT INTO `staff_medifarma` (`numide`, `nommf`, `apemf`, `sexmf`, `nacmf`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`)
VALUES ('0601199501418', 'Brandyn Fabricio', 'Guevara Guillen', 'Masculino', '1900-01-01', 'Permanente', 12, 17, 1, '52', '748214221', '2018-10-15', '959-19554', NULL, 'brandyn.guevara@medicasa.hn', '47', 1, (SELECT id FROM users WHERE cedula='0601-1995-01418' LIMIT 1));

-- === INSERTANDO EN TABLA: doctor ===
INSERT INTO `doctor` (`ceddoc`, `nodoc`, `apdoc`, `sexd`, `nacd`, `tipo_empleado`, `id_departamento`, `id_cargo`, `id_salary_level`, `id_biometrico`, `cuenta_bac`, `fecha_ingreso`, `telefono`, `correo_personal`, `correo_institucional`, `num_locker`, `state`, `id_user`, `nomesp`, `direcd`, `phd`, `corr`)
VALUES ('0706199200115', 'Angel Noe', 'Elvir Rivas', 'Masculino', '1900-01-01', 'Permanente', 11, 30, 4, '57', '751873751', '2024-03-12', '8830-8995', NULL, 'angel.elvir@medicasa.hn', '79', 1, (SELECT id FROM users WHERE cedula='0706-1992-00115' LIMIT 1), '', '', '', '');


-- =========================================================================
-- PARA APLICAR LA MIGRACIÓN, EJECUTE: 
-- COMMIT;
-- =========================================================================

-- =========================================================================
-- SCRIPT DE ROLLBACK (Ejecutar solo si se requiere deshacer todo)
-- =========================================================================
/*
START TRANSACTION;
DELETE FROM `staff_administrative` WHERE `numide` IN ('0801199313326', '0819199000151', '0801199423437', '0801199314568', '0801199913517', '0401199800147', '0801198602782', '0801199221409', '0801199701937', '1701199900384', '0805199600290', '0801199801274', '0307200400115', '0615199200522', '0801199316976', '0801199614192', '0801199000980', '0601198502925', '0801200107848');
DELETE FROM `nurse` WHERE `numide` IN ('0801199400602', '0801199111518', '0318199501034', '0801200022199', '0801200200975', '0801199212422', '0801198407434', '0824200000708', '0801199703518', '0806200500354', '0801200315223', '0801200201068', '0703199903980', '0824199100887', '0801199515349', '0801199113638', '0801199612100');
DELETE FROM `staff_general_services` WHERE `numide` IN ('0801198306798', '0801199023025', '1206199800454', '0715198400910', '0803199700291');
DELETE FROM `staff_medifarma` WHERE `numide` IN ('0801199508042', '0801199605873', '0601199501418');
DELETE FROM `doctor` WHERE `ceddoc` IN ('0706199200115');
COMMIT;
*/
