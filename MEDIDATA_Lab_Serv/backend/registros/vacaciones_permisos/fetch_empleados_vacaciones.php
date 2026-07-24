<?php
require_once '../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode([]);
    exit;
}

try {
    if (!isset($connect) || !$connect) throw new Exception('Sin conexión a BD.');

    // Todos los colaboradores (todas las tablas de staff), devolviendo id_user + nombre.
    // id_user es la clave que consume fetch_vacation_profile.php (WHERE s.id_user = ?).
    $sql = "
        SELECT id_user, text FROM (
            SELECT id_user, CONCAT(nomadm, ' ', apeadm) AS text FROM staff_administrative WHERE id_user IS NOT NULL
            UNION ALL
            SELECT id_user, CONCAT(nodoc, ' ', apdoc) AS text FROM doctor WHERE id_user IS NOT NULL
            UNION ALL
            SELECT id_user, CONCAT(nomnur, ' ', apenur) AS text FROM nurse WHERE id_user IS NOT NULL
            UNION ALL
            SELECT id_user, CONCAT(nomsg, ' ', apesg) AS text FROM staff_general_services WHERE id_user IS NOT NULL
            UNION ALL
            SELECT id_user, CONCAT(nommf, ' ', apemf) AS text FROM staff_medifarma WHERE id_user IS NOT NULL
        ) s
        WHERE s.text IS NOT NULL AND TRIM(s.text) <> ''
        ORDER BY s.text ASC
    ";
    $stmt = $connect->query($sql);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
