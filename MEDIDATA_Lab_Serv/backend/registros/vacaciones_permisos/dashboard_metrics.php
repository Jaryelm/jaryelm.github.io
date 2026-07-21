<?php
require_once '../../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect_hr_leaves)) throw new Exception("Sin conexión.");
    $metrics = [];

    // a. Colaboradores actualmente de vacaciones
    $stmt = $connect_hr_leaves->query("SELECT COUNT(*) as count FROM hr_absence_requests r JOIN hr_absence_types t ON r.type_id = t.type_id WHERE t.category = 'Vacation' AND r.request_status = 'Approved' AND CURRENT_DATE BETWEEN r.start_date AND r.end_date");
    $metrics['vacaciones_activas'] = $stmt->fetchColumn();

    // a. Días de vacaciones pendientes por disfrutar (Sumatoria total de la empresa)
    $stmt = $connect_hr_leaves->query("SELECT SUM(affected_days) as total FROM hr_vacation_transactions");
    $metrics['dias_pendientes_empresa'] = $stmt->fetchColumn() ?? 0;

    // b. Solicitudes pendientes de aprobación
    $stmt = $connect_hr_leaves->query("SELECT COUNT(*) as count FROM hr_absence_requests WHERE request_status IN ('Pending', 'In_Progress')");
    $metrics['solicitudes_pendientes'] = $stmt->fetchColumn();

    // c. Solicitudes aprobadas (Histórico o del mes, asumiremos total o del mes actual)
    $stmt = $connect_hr_leaves->query("SELECT COUNT(*) as count FROM hr_absence_requests WHERE request_status = 'Approved' AND MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $metrics['solicitudes_aprobadas'] = $stmt->fetchColumn();

    // d. Solicitudes rechazadas
    $stmt = $connect_hr_leaves->query("SELECT COUNT(*) as count FROM hr_absence_requests WHERE request_status = 'Rejected' AND MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $metrics['solicitudes_rechazadas'] = $stmt->fetchColumn();

    // e. Permisos registrados del mes
    $stmt = $connect_hr_leaves->query("SELECT COUNT(*) as count FROM hr_absence_requests r JOIN hr_absence_types t ON r.type_id = t.type_id WHERE t.category = 'Permission' AND r.request_status = 'Approved' AND MONTH(r.start_date) = MONTH(CURRENT_DATE) AND YEAR(r.start_date) = YEAR(CURRENT_DATE)");
    $metrics['permisos_mes'] = $stmt->fetchColumn();

    // f. Incapacidades activas
    $stmt = $connect_hr_leaves->query("SELECT COUNT(*) as count FROM hr_absence_requests r JOIN hr_absence_types t ON r.type_id = t.type_id WHERE t.category = 'Medical_Leave' AND r.request_status = 'Approved' AND CURRENT_DATE BETWEEN r.start_date AND r.end_date");
    $metrics['incapacidades_activas'] = $stmt->fetchColumn();

    // g. Próximo colaborador con derecho a vacaciones (según código hondureño)
    // El más cercano a cumplir año (new_vacations_date)
    $stmt = $connect_hr_leaves->query("
        SELECT p.user_id, p.new_vacations_date 
        FROM hr_vacation_profile p
        WHERE p.new_vacations_date >= CURRENT_DATE
        ORDER BY p.new_vacations_date ASC 
        LIMIT 1
    ");
    $proximo = $stmt->fetch(PDO::FETCH_ASSOC);
    if($proximo) {
        // Find the user name in MEDIDATA main db users table or administrative
        $stmt_user = $pdo->prepare("SELECT CONCAT(nomadm, ' ', apeadm) as name FROM staff_administrative WHERE id_user = ? LIMIT 1");
        $stmt_user->execute([$proximo['user_id']]);
        $name = $stmt_user->fetchColumn();
        if(!$name) {
            $stmt_u = $pdo->prepare("SELECT CONCAT(nombre, ' ', apellido) as name FROM users WHERE id = ? LIMIT 1");
            $stmt_u->execute([$proximo['user_id']]);
            $name = $stmt_u->fetchColumn();
        }
        $metrics['proximo_vacaciones'] = [
            'nombre' => $name ? $name : 'Usuario ID '.$proximo['user_id'],
            'fecha' => $proximo['new_vacations_date']
        ];
    } else {
        $metrics['proximo_vacaciones'] = null;
    }

    echo json_encode($metrics);

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

