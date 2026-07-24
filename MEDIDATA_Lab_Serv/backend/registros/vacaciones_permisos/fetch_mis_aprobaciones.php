<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
require_once '../../php/jefe_lib.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    global $connect_hr_leaves, $connect;
    if (!$connect) throw new Exception("No db connection");

    $user_id = (int) $_SESSION['id'];
    $rol = $_SESSION['rol'] ?? '';
    $isAdminHr = in_array($rol, ['Administrador', 'Recursos_Humanos'], true);

    $sql = "
        SELECT
            r.request_id,
            r.user_id,
            u.name AS user_name,
            t.name AS type_name,
            r.start_date,
            r.end_date,
            r.days_amount,
            r.request_status,
            r.created_at
        FROM medic9ue_hr_leaves.hr_absence_requests r
        LEFT JOIN medic9ue_hr_leaves.hr_absence_types t ON r.type_id = t.type_id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.request_status = 'Pending'
    ";
    $params = [];

    if (!$isAdminHr) {
        // Jefe inmediato: solo las solicitudes de SU equipo (departamentos que encabeza),
        // excluyendo las propias (esas las resuelve RRHH/Admin).
        $equipo = array_values(array_filter(
            medidata_jefe_equipo_user_ids($connect, $user_id),
            fn($id) => $id !== $user_id
        ));
        if (!$equipo) {
            echo json_encode(['data' => []]);
            exit;
        }
        $in = implode(',', array_fill(0, count($equipo), '?'));
        $sql .= " AND r.user_id IN ($in)";
        $params = $equipo;
    }

    $sql .= " ORDER BY r.created_at ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
