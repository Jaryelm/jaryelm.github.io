<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../php/staff_colaborador_bootstrap.php';
require_once __DIR__ . '/colaborador_extra_lib.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('Conexión a base de datos no disponible.');
    }

    medidata_staff_ensure_tables($connect);
    medidata_colab_extra_ensure_table($connect);

    // Asegurar columnas en users
    foreach (['state' => "CHAR(1) NOT NULL DEFAULT '1'", 'cedula' => 'VARCHAR(30) NULL DEFAULT NULL', 'sexo' => 'VARCHAR(20) NULL DEFAULT NULL'] as $col => $def) {
        try {
            $chk = $connect->query("SHOW COLUMNS FROM users LIKE '$col'");
            if (!$chk || !$chk->fetch()) {
                $connect->exec("ALTER TABLE users ADD COLUMN $col $def");
            }
        } catch (Throwable $e) {
            error_log("fetch_collaborators_rrhh ensure $col: " . $e->getMessage());
        }
    }

    $linkedUsers = medidata_staff_linked_user_ids_subquery();

    $base = "SELECT idodc AS ID, idodc AS RealID, ceddoc AS Cedula, nodoc AS Nombre,
                    'Doctor' AS Tipo_Empleado, sexd AS Sexo, nomesp AS Especialidad, state AS Estado,
                    nodoc AS Nombres, apdoc AS Apellidos, nacd AS FechaNac, NULL AS FechaIngreso,
                    phd AS Telefono, corr AS CorreoPersonal
             FROM doctor
             UNION ALL
             SELECT idnur AS ID, idnur AS RealID, numide AS Cedula, nomnur AS Nombre,
                    'Enfermero' AS Tipo_Empleado, sexnur AS Sexo, 'Enfermero' AS Especialidad, state AS Estado,
                    nomnur AS Nombres, apenur AS Apellidos, nacinur AS FechaNac, NULL AS FechaIngreso,
                    NULL AS Telefono, NULL AS CorreoPersonal
             FROM nurse
             UNION ALL
             SELECT COALESCE(a.id_user, a.idadm) AS ID, a.idadm AS RealID, a.numide AS Cedula,
                    TRIM(CONCAT(a.nomadm, ' ', a.apeadm)) AS Nombre,
                    'Administrativo' AS Tipo_Empleado, a.sexadm AS Sexo,
                    COALESCE(NULLIF(a.cargo, ''), u.rol, 'Administrativo') AS Especialidad, a.state AS Estado,
                    a.nomadm AS Nombres, a.apeadm AS Apellidos, a.nacadm AS FechaNac, a.fecha_ingreso AS FechaIngreso,
                    NULL AS Telefono, NULL AS CorreoPersonal
             FROM staff_administrative a
             LEFT JOIN users u ON u.id = a.id_user
             UNION ALL
             SELECT COALESCE(s.id_user, s.idsg) AS ID, s.idsg AS RealID, s.numide AS Cedula,
                    TRIM(CONCAT(s.nomsg, ' ', s.apesg)) AS Nombre,
                    'Servicios Generales' AS Tipo_Empleado, s.sexsg AS Sexo,
                    COALESCE(NULLIF(s.area, ''), u2.rol, 'Servicios Generales') AS Especialidad, s.state AS Estado,
                    s.nomsg AS Nombres, s.apesg AS Apellidos, s.nacsg AS FechaNac, s.fecha_ingreso AS FechaIngreso,
                    NULL AS Telefono, NULL AS CorreoPersonal
             FROM staff_general_services s
             LEFT JOIN users u2 ON u2.id = s.id_user
             UNION ALL
             SELECT u.id AS ID, u.id AS RealID, COALESCE(NULLIF(u.cedula, ''), 'N/A') AS Cedula, u.name AS Nombre,
                    'Usuario' AS Tipo_Empleado, COALESCE(NULLIF(u.sexo, ''), 'N/A') AS Sexo, u.rol AS Especialidad, u.state AS Estado,
                    u.name AS Nombres, '' AS Apellidos, NULL AS FechaNac, NULL AS FechaIngreso,
                    NULL AS Telefono, u.email AS CorreoPersonal
             FROM users u
             WHERE u.id NOT IN ($linkedUsers)";

    $sql = "SELECT base.*,
                   ex.fecha_ingreso AS x_fecha_ingreso,
                   ex.fecha_nacimiento AS x_fecha_nacimiento,
                   ex.cuenta_bac AS x_cuenta_bac,
                   ex.depto AS x_depto,
                   ex.cargo AS x_cargo,
                   ex.horario AS x_horario,
                   ex.salario AS x_salario,
                   ex.nivel_salarial AS x_nivel_salarial,
                   ex.telefono AS x_telefono,
                   ex.correo_personal AS x_correo_personal,
                   ex.correo_institucional AS x_correo_institucional,
                   ex.locker AS x_locker,
                   ex.codigo_empleado AS x_codigo_empleado,
                   ex.contrato_nombre AS x_contrato_nombre,
                   CASE WHEN ex.contrato_pdf IS NOT NULL AND LENGTH(ex.contrato_pdf) > 0 THEN 1 ELSE 0 END AS x_has_contrato
            FROM ($base) base
            LEFT JOIN rrhh_colaborador_extra ex
                   ON ex.tipo = base.Tipo_Empleado AND ex.ref_id = base.RealID
            ORDER BY base.Nombre ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    error_log('fetch_collaborators_rrhh: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
