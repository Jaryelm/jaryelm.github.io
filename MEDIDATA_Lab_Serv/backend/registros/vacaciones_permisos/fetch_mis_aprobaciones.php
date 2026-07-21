<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    global $connect_hr_leaves, $connect;
    if (!$connect) throw new Exception("No db connection");
    
    // En un sistema real, aquí habría un JOIN con hr_approval_workflow_steps
    // para buscar si el usuario logueado ($_SESSION['id']) o su rol ($_SESSION['rol'])
    // es el encargado del step_order actual.
    // Por simplicidad en la base, mostraremos todas las solicitudes 'Pending'
    // si el usuario es Administrador/HR, o un subset.
    
    $user_id = $_SESSION['id'];
    $rol = $_SESSION['rol'];
    
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
    
    // Si no es admin/hr, quizás solo pueda aprobar las de su departamento
    // Filtro simplificado:
    if (!in_array($rol, ['Administrador', 'Recursos_Humanos'])) {
        // Pseudo-lógica: Jefe inmediato, etc.
        // Aquí requeriría cruzar con tabla de departamentos/empleados.
        // Por ahora, como es un stub funcional:
        $sql .= " AND 1=0 "; // Ocultar si no hay lógica de jefe inmediato implementada aún
    }
    
    $sql .= " ORDER BY r.created_at ASC";

    $stmt = $connect->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => 'Internal error: ' . $e->getMessage()]);
}
?>
