<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../php/staff_colaborador_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('Conexión a base de datos no disponible.');
    }

    medidata_staff_ensure_tables($connect);

    // Asegurar que la tabla users tenga la columna state
    try {
        $stmtUsers = $connect->query("SHOW COLUMNS FROM users LIKE 'state'");
        if (!$stmtUsers || !$stmtUsers->fetch()) {
            $connect->exec("ALTER TABLE users ADD COLUMN state CHAR(1) NOT NULL DEFAULT '1'");
        }
    } catch (Throwable $e) {
        error_log('Error asegurando columna state en users: ' . $e->getMessage());
    }

    $linkedUsers = medidata_staff_linked_user_ids_subquery();

    $sql = "SELECT idodc AS ID, idodc AS RealID, ceddoc AS Cedula, nodoc AS Nombre,
                   'Doctor' AS Tipo_Empleado, sexd AS Sexo, nomesp AS Especialidad, state AS Estado
            FROM doctor
            UNION ALL
            SELECT idnur AS ID, idnur AS RealID, numide AS Cedula, nomnur AS Nombre,
                   'Enfermero' AS Tipo_Empleado, sexnur AS Sexo, 'Enfermero' AS Especialidad, state AS Estado
            FROM nurse
            UNION ALL
            SELECT COALESCE(a.id_user, a.idadm) AS ID, a.idadm AS RealID, a.numide AS Cedula,
                   TRIM(CONCAT(a.nomadm, ' ', a.apeadm)) AS Nombre,
                   'Administrativo' AS Tipo_Empleado, a.sexadm AS Sexo,
                   COALESCE(NULLIF(a.cargo, ''), u.rol, 'Administrativo') AS Especialidad, a.state AS Estado
            FROM staff_administrative a
            LEFT JOIN users u ON u.id = a.id_user
            UNION ALL
            SELECT COALESCE(s.id_user, s.idsg) AS ID, s.idsg AS RealID, s.numide AS Cedula,
                   TRIM(CONCAT(s.nomsg, ' ', s.apesg)) AS Nombre,
                   'Servicios Generales' AS Tipo_Empleado, s.sexsg AS Sexo,
                   COALESCE(NULLIF(s.area, ''), u2.rol, 'Servicios Generales') AS Especialidad, s.state AS Estado
            FROM staff_general_services s
            LEFT JOIN users u2 ON u2.id = s.id_user
            UNION ALL
            SELECT u.id AS ID, u.id AS RealID, 'N/A' AS Cedula, u.name AS Nombre,
                   'Usuario' AS Tipo_Empleado, 'N/A' AS Sexo, u.rol AS Especialidad, u.state AS Estado
            FROM users u
            WHERE u.id NOT IN ($linkedUsers)
            ORDER BY Nombre ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    error_log('fetch_collaborators: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
