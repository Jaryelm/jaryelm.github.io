<?php
require_once '../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'])) {
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

try {
    if (!isset($connect)) throw new Exception("Sin conexión.");
    $metrics = [];

    // a. Colaboradores actualmente de vacaciones
    $stmt = $connect->query("SELECT COUNT(*) as count FROM hr_absence_requests r JOIN hr_absence_types t ON r.type_id = t.type_id WHERE t.category = 'Vacation' AND r.request_status = 'Approved' AND CURRENT_DATE BETWEEN r.start_date AND r.end_date");
    $metrics['vacaciones_activas'] = $stmt->fetchColumn();

    // a. Días de vacaciones pendientes por disfrutar (Sumatoria total de la empresa)
    $stmt = $connect->query("SELECT SUM(affected_days) as total FROM hr_vacation_transactions");
    $metrics['dias_pendientes_empresa'] = $stmt->fetchColumn() ?? 0;

    // b. Solicitudes pendientes de aprobación
    $stmt = $connect->query("SELECT COUNT(*) as count FROM hr_absence_requests WHERE request_status IN ('Pending', 'In_Progress')");
    $metrics['solicitudes_pendientes'] = $stmt->fetchColumn();

    // c. Solicitudes aprobadas (Histórico o del mes, asumiremos total o del mes actual)
    $stmt = $connect->query("SELECT COUNT(*) as count FROM hr_absence_requests WHERE request_status = 'Approved' AND MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $metrics['solicitudes_aprobadas'] = $stmt->fetchColumn();

    // d. Solicitudes rechazadas
    $stmt = $connect->query("SELECT COUNT(*) as count FROM hr_absence_requests WHERE request_status = 'Rejected' AND MONTH(created_at) = MONTH(CURRENT_DATE) AND YEAR(created_at) = YEAR(CURRENT_DATE)");
    $metrics['solicitudes_rechazadas'] = $stmt->fetchColumn();

    // e. Permisos registrados del mes (todos los registrados, cualquier estado excepto cancelados)
    $stmt = $connect->query("SELECT COUNT(*) as count FROM hr_absence_requests r JOIN hr_absence_types t ON r.type_id = t.type_id WHERE t.category = 'Permission' AND r.request_status <> 'Cancelled' AND MONTH(r.start_date) = MONTH(CURRENT_DATE) AND YEAR(r.start_date) = YEAR(CURRENT_DATE)");
    $metrics['permisos_mes'] = $stmt->fetchColumn();

    // f. Incapacidades activas
    $stmt = $connect->query("SELECT COUNT(*) as count FROM hr_absence_requests r JOIN hr_absence_types t ON r.type_id = t.type_id WHERE t.category = 'Medical_Leave' AND r.request_status = 'Approved' AND CURRENT_DATE BETWEEN r.start_date AND r.end_date");
    $metrics['incapacidades_activas'] = $stmt->fetchColumn();

    // g. Próximo colaborador con derecho a vacaciones (código de trabajo hondureño: la vacación
    //    puede gozarse en una ventana de 2 meses antes o 2 meses después del aniversario).
    //    Por eso incluimos a quienes ya cumplieron hasta 2 meses atrás (siguen dentro de la ventana)
    //    y al próximo por venir; tomamos el más cercano.
    $stmt = $connect->query("
        SELECT p.user_id, p.new_vacations_date
        FROM hr_vacation_profile p
        WHERE p.new_vacations_date >= (CURRENT_DATE - INTERVAL 2 MONTH)
        ORDER BY p.new_vacations_date ASC
        LIMIT 1
    ");
    $proximo = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($proximo) {
        // Resolver el nombre desde las tablas de staff (BD principal) con fallback a users.name
        $name = null;
        if (isset($connect) && $connect) {
            $stmt_user = $connect->prepare("
                SELECT text FROM (
                    SELECT id_user, CONCAT(nomadm, ' ', apeadm) AS text FROM staff_administrative WHERE id_user IS NOT NULL
                    UNION ALL SELECT id_user, CONCAT(nodoc, ' ', apdoc) FROM doctor WHERE id_user IS NOT NULL
                    UNION ALL SELECT id_user, CONCAT(nomnur, ' ', apenur) FROM nurse WHERE id_user IS NOT NULL
                    UNION ALL SELECT id_user, CONCAT(nomsg, ' ', apesg) FROM staff_general_services WHERE id_user IS NOT NULL
                    UNION ALL SELECT id_user, CONCAT(nommf, ' ', apemf) FROM staff_medifarma WHERE id_user IS NOT NULL
                ) s WHERE s.id_user = ? LIMIT 1
            ");
            $stmt_user->execute([$proximo['user_id']]);
            $name = $stmt_user->fetchColumn();
            if (!$name) {
                $stmt_u = $connect->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
                $stmt_u->execute([$proximo['user_id']]);
                $name = $stmt_u->fetchColumn();
            }
        }
        // ¿Está actualmente dentro de la ventana de derecho (±2 meses de hoy)?
        $dentro_ventana = ($proximo['new_vacations_date'] >= date('Y-m-d', strtotime('-2 months'))
                        && $proximo['new_vacations_date'] <= date('Y-m-d', strtotime('+2 months')));
        $metrics['proximo_vacaciones'] = [
            'nombre'         => $name ? $name : 'Usuario ID ' . $proximo['user_id'],
            'fecha'          => $proximo['new_vacations_date'],
            'dentro_ventana' => $dentro_ventana ? 1 : 0,
        ];
    } else {
        $metrics['proximo_vacaciones'] = null;
    }

    echo json_encode($metrics);

} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

