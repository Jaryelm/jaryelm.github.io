<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['error' => 'Falta el user_id']);
    exit;
}

try {
    if (!isset($connect)) throw new Exception("Sin conexión a BD de hojas.");
    
    // Fetch Employee Base Data from main DB
    // Assuming staff_administrative and users structure
    $stmt_emp = $connect->prepare("
        SELECT 
            s.codigo_colaborador, 
            s.nombre_completo, 
            s.fecha_ingreso,
            p.name AS puesto,
            d.name AS departamento
        FROM (
            SELECT numide AS codigo_colaborador, CONCAT(nomadm, ' ', apeadm) AS nombre_completo, fecha_ingreso, id_cargo, id_departamento, id_user FROM staff_administrative
            UNION ALL
            SELECT ceddoc AS codigo_colaborador, CONCAT(nodoc, ' ', apdoc) AS nombre_completo, fecha_ingreso, id_cargo, id_departamento, id_user FROM doctor
            UNION ALL
            SELECT numide AS codigo_colaborador, CONCAT(nomnur, ' ', apenur) AS nombre_completo, fecha_ingreso, id_cargo, id_departamento, id_user FROM nurse
            UNION ALL
            SELECT numide AS codigo_colaborador, CONCAT(nomsg, ' ', apesg) AS nombre_completo, fecha_ingreso, id_cargo, id_departamento, id_user FROM staff_general_services
            UNION ALL
            SELECT numide AS codigo_colaborador, CONCAT(nommf, ' ', apemf) AS nombre_completo, fecha_ingreso, id_cargo, id_departamento, id_user FROM staff_medifarma
        ) s
        LEFT JOIN positions p ON s.id_cargo = p.id
        LEFT JOIN medic9ue_medi_rrhh_interviews.departaments d ON s.id_departamento = d.id
        WHERE s.id_user = ? 
        LIMIT 1
    ");
    $stmt_emp->execute([$user_id]);
    $emp = $stmt_emp->fetch(PDO::FETCH_ASSOC);

    if (!$emp) {
        throw new Exception("Colaborador no encontrado en el sistema.");
    }
    
    // We calculate Antigüedad
    $fecha_ingreso = new DateTime($emp['fecha_ingreso']);
    $hoy = new DateTime();
    $antiguedad_diff = $fecha_ingreso->diff($hoy);
    $emp['antiguedad'] = $antiguedad_diff->y . ' años, ' . $antiguedad_diff->m . ' meses';
    
    // Fetch vacation profile
    $stmt_prof = $connect->prepare("SELECT new_vacations_date, calculation_start_date FROM hr_vacation_profile WHERE user_id = ?");
    $stmt_prof->execute([$user_id]);
    $prof = $stmt_prof->fetch(PDO::FETCH_ASSOC);
    
    $proxima_generacion = $prof ? $prof['new_vacations_date'] : 'No configurado';
    $calc_start = $prof ? $prof['calculation_start_date'] : $emp['fecha_ingreso'];
    
    $per_start_year = date('Y', strtotime($calc_start));
    $per_next_year = $prof ? date('Y', strtotime($prof['new_vacations_date'])) : date('Y');
    $periodo_vacacional = $per_start_year . ' - ' . $per_next_year;
    
    // Fetch aggregated data from Kardex
    $stmt_kardex = $connect->prepare("
        SELECT 
            SUM(CASE WHEN transaction_type IN ('Annual_Accrual', 'Manual_HR_Adjustment') THEN affected_days ELSE 0 END) as dias_otorgados,
            SUM(CASE WHEN transaction_type = 'Vacation_Consumption' THEN ABS(affected_days) ELSE 0 END) as dias_disfrutados,
            SUM(CASE WHEN transaction_type = 'Cash_Payout' THEN ABS(affected_days) ELSE 0 END) as dias_pagados,
            SUM(affected_days) as dias_pendientes,
            MAX(CASE WHEN transaction_type = 'Vacation_Consumption' THEN transaction_date ELSE NULL END) as fecha_ultimo_disfrute
        FROM hr_vacation_transactions
        WHERE user_id = ?
    ");
    $stmt_kardex->execute([$user_id]);
    $kardex = $stmt_kardex->fetch(PDO::FETCH_ASSOC);
    
    // Fetch Jefe Inmediato (this requires a lookup based on department or workflow, placeholder for now)
    $emp['jefe_inmediato'] = 'Asignado por Departamento/RRHH';
    
    $response = [
        'info_general' => [
            'nombre_completo' => $emp['nombre_completo'],
            'codigo_colaborador' => $emp['codigo_colaborador'],
            'puesto' => $emp['puesto'],
            'departamento' => $emp['departamento'],
            'jefe_inmediato' => $emp['jefe_inmediato'],
            'fecha_ingreso' => $emp['fecha_ingreso'],
            'antiguedad' => $emp['antiguedad']
        ],
        'info_vacaciones' => [
            'periodo_vacacional' => $periodo_vacacional,
            'dias_otorgados' => $kardex['dias_otorgados'] ?? 0,
            'dias_disfrutados' => $kardex['dias_disfrutados'] ?? 0,
            'dias_pendientes' => $kardex['dias_pendientes'] ?? 0,
            'dias_pagados' => $kardex['dias_pagados'] ?? 0,
            'fecha_ultimo_disfrute' => $kardex['fecha_ultimo_disfrute'] ? date('Y-m-d', strtotime($kardex['fecha_ultimo_disfrute'])) : 'N/A',
            'proxima_generacion' => $proxima_generacion
        ]
    ];
    
    echo json_encode($response);
    
} catch (Throwable $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
