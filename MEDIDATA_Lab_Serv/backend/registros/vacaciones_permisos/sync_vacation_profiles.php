<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos Humanos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

try {
    global $connect, $connect_hr_leaves;
    if (!$connect || !$connect_hr_leaves) {
        throw new Exception("Sin conexión a base de datos.");
    }
    
    // Obtener politicas
    $stmt_pol = $connect_hr_leaves->prepare("SELECT * FROM hr_vacation_policies WHERE status = 1 ORDER BY min_seniority_years ASC");
    $stmt_pol->execute();
    $policies = $stmt_pol->fetchAll(PDO::FETCH_ASSOC);
    
    // 1. Obtener todos los usuarios con fecha de ingreso de todas las tablas de staff
    $sql_users = "
        SELECT id_usuario, fecha_ingreso 
        FROM (
        SELECT id_user as id_usuario, fecha_ingreso FROM staff_administrative WHERE id_user IS NOT NULL
        UNION
        SELECT id_user as id_usuario, fecha_ingreso FROM doctor WHERE id_user IS NOT NULL
        UNION
        SELECT id_user as id_usuario, fecha_ingreso FROM nurse WHERE id_user IS NOT NULL
        UNION
        SELECT id_user as id_usuario, fecha_ingreso FROM staff_general_services WHERE id_user IS NOT NULL
        UNION
        SELECT id_user as id_usuario, fecha_ingreso FROM staff_medifarma WHERE id_user IS NOT NULL
        ) AS s
        WHERE fecha_ingreso IS NOT NULL AND fecha_ingreso > '1970-01-01'
    ";
    
    $stmt_users = $connect->prepare($sql_users);
    $stmt_users->execute();
    $empleados = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
    
    $procesados = 0;
    $nuevos = 0;
    $dias_otorgados_totales = 0;
    
    $hoy = new DateTime();
    $hoy->setTime(0,0,0);
    
    $stmt_insert_prof = $connect_hr_leaves->prepare("
        INSERT IGNORE INTO hr_vacation_profile (user_id, calculation_start_date, new_vacations_date)
        VALUES (?, ?, ?)
    ");
    
    $stmt_insert_txn = $connect_hr_leaves->prepare("
        INSERT INTO hr_vacation_transactions (user_id, transaction_type, affected_days, description, transaction_date)
        VALUES (?, 'Manual_HR_Adjustment', ?, 'Saldo Inicial Retroactivo (Sincronización)', NOW())
    ");
    
    // Verificar si ya tiene transacciones (para no duplicar retroactivos)
    $stmt_check_txn = $connect_hr_leaves->prepare("SELECT COUNT(*) FROM hr_vacation_transactions WHERE user_id = ?");

    foreach ($empleados as $emp) {
        $user_id = $emp['id_usuario'];
        $fecha_ingreso_str = $emp['fecha_ingreso'];
        
        try {
            $fecha_ingreso = new DateTime($fecha_ingreso_str);
        } catch (Exception $e) {
            continue; 
        }
        
        // Next anniversary
        $aniversario = clone $fecha_ingreso;
        $aniversario->setDate((int)$hoy->format('Y'), (int)$fecha_ingreso->format('m'), (int)$fecha_ingreso->format('d'));
        
        if ($aniversario <= $hoy) {
            $aniversario->modify('+1 year');
        }
        
        $next_date = $aniversario->format('Y-m-d');
        $calc_start_date = $fecha_ingreso->format('Y-m-d');
        
        // Crear perfil
        $stmt_insert_prof->execute([$user_id, $calc_start_date, $next_date]);
        if ($stmt_insert_prof->rowCount() > 0) {
            $nuevos++;
        }
        
        // Si no tiene historial de transacciones, le damos su saldo inicial retroactivo
        $stmt_check_txn->execute([$user_id]);
        if ($stmt_check_txn->fetchColumn() == 0) {
            // Calcular días retroactivos
            $dias_retroactivos = 0;
            $anios_trabajados = $fecha_ingreso->diff($hoy)->y;
            
            for ($y = 1; $y <= $anios_trabajados; $y++) {
                $dias_year = 0;
                foreach ($policies as $pol) {
                    if ($y >= $pol['min_seniority_years'] && $y <= $pol['max_seniority_years']) {
                        $dias_year = $pol['granted_days'];
                        break;
                    }
                }
                $dias_retroactivos += $dias_year;
            }
            
            if ($dias_retroactivos > 0) {
                $stmt_insert_txn->execute([$user_id, $dias_retroactivos]);
                $dias_otorgados_totales += $dias_retroactivos;
            }
        }
        $procesados++;
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => "Sincronización completada. Empleados evaluados: $procesados. Perfiles nuevos: $nuevos. Días retroactivos otorgados: $dias_otorgados_totales."
    ]);
    
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
