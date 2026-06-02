<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../php/staff_colaborador_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('Conexión a base de datos no disponible.');
    }

    medidata_staff_ensure_tables($connect);

    $linkedUsers = medidata_staff_linked_user_ids_subquery();

    $sql = "SELECT idodc AS ID, ceddoc AS Cedula, nodoc AS Nombre,
                   'Doctor' AS Tipo_Empleado, sexd AS Sexo, nomesp AS Especialidad
            FROM doctor
            UNION ALL
            SELECT idnur AS ID, numide AS Cedula, nomnur AS Nombre,
                   'Enfermero' AS Tipo_Empleado, sexnur AS Sexo, 'Enfermero' AS Especialidad
            FROM nurse
            UNION ALL
            SELECT COALESCE(a.id_user, a.idadm) AS ID, a.numide AS Cedula,
                   TRIM(CONCAT(a.nomadm, ' ', a.apeadm)) AS Nombre,
                   'Administrativo' AS Tipo_Empleado, a.sexadm AS Sexo,
                   COALESCE(NULLIF(a.cargo, ''), u.rol, 'Administrativo') AS Especialidad
            FROM staff_administrative a
            LEFT JOIN users u ON u.id = a.id_user
            UNION ALL
            SELECT COALESCE(s.id_user, s.idsg) AS ID, s.numide AS Cedula,
                   TRIM(CONCAT(s.nomsg, ' ', s.apesg)) AS Nombre,
                   'Servicios Generales' AS Tipo_Empleado, s.sexsg AS Sexo,
                   COALESCE(NULLIF(s.area, ''), u2.rol, 'Servicios Generales') AS Especialidad
            FROM staff_general_services s
            LEFT JOIN users u2 ON u2.id = s.id_user
            UNION ALL
            SELECT u.id AS ID, 'N/A' AS Cedula, u.name AS Nombre,
                   'Usuario' AS Tipo_Empleado, 'N/A' AS Sexo, u.rol AS Especialidad
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
