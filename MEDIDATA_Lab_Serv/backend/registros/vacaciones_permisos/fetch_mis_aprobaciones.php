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
    global $connect;
    if (!$connect) throw new Exception("No db connection");

    $user_id = (int) $_SESSION['id'];
    $rol = $_SESSION['rol'] ?? '';
    $isAdminHr = in_array($rol, ['Administrador', 'Recursos_Humanos'], true);

    // Se incluyen también las solicitudes 'In_Progress' (flujos multi-paso a medio
    // camino) y se resuelve el paso ACTUAL del flujo para saber a quién le toca.
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
            r.created_at,
            s.approver_type AS current_step_type,
            s.approver_role_name AS current_step_role
        FROM hr_absence_requests r
        LEFT JOIN hr_absence_types t ON r.type_id = t.type_id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN hr_approval_workflow_steps s
               ON s.workflow_id = r.workflow_id AND s.step_order = r.current_step_order
        WHERE r.request_status IN ('Pending', 'In_Progress')
    ";
    $params = [];

    if (!$isAdminHr) {
        // Jefe de departamento: solo las solicitudes de SU equipo (departamentos que
        // encabeza), excluyendo las propias, y únicamente cuando el paso actual del
        // flujo le corresponde (Jefe de Departamento o flujo sin pasos configurados).
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
        $sql .= " AND (s.step_id IS NULL OR s.approver_type = 'Department_Manager')";
        $params = $equipo;
    }

    $sql .= " ORDER BY r.created_at ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        if ($row['current_step_type'] === 'Department_Manager') {
            $row['current_step_label'] = 'Jefe de Departamento';
        } elseif ($row['current_step_type'] === 'Specific_Role') {
            $row['current_step_label'] = str_replace('_', ' ', (string) $row['current_step_role']);
        } else {
            $row['current_step_label'] = '';
        }
    }
    unset($row);

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
