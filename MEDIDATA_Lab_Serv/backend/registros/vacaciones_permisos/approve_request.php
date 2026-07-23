<?php
require_once '../../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($connect_hr_leaves)) throw new Exception("Sin conexión a BD.");
        
        $request_id = $_POST['request_id'] ?? '';
        $approver_id = $_SESSION['id']; // El usuario actual que aprueba
        
        if (empty($request_id)) throw new Exception("ID de solicitud requerido.");
        
        // Iniciar transacción para asegurar consistencia
        $connect_hr_leaves->beginTransaction();
        
        // Obtener la información de la solicitud y del tipo de ausencia
        $stmt_req = $connect_hr_leaves->prepare("
            SELECT r.user_id, r.days_amount, t.category, t.deducts_vacation, r.request_status, r.start_time, r.end_time 
            FROM hr_absence_requests r
            JOIN hr_absence_types t ON r.type_id = t.type_id
            WHERE r.request_id = ? FOR UPDATE
        ");
        $stmt_req->execute([$request_id]);
        $req = $stmt_req->fetch(PDO::FETCH_ASSOC);
        
        if (!$req) throw new Exception("Solicitud no encontrada.");
        if ($req['request_status'] === 'Approved') throw new Exception("La solicitud ya fue aprobada previamente.");
        if ($req['request_status'] === 'Rejected') throw new Exception("La solicitud ya se encuentra rechazada.");
        
        // 1. Actualizar estado de la solicitud a Aprobado
        $stmt_upd = $connect_hr_leaves->prepare("UPDATE hr_absence_requests SET request_status = 'Approved', final_approver_id = ? WHERE request_id = ?");
        $stmt_upd->execute([$approver_id, $request_id]);
        
        // 2. Lógica para restar los días en el Kardex si corresponde
        // Si es categoría Vacación O el tipo de permiso está configurado para deducir vacaciones
        if ($req['category'] === 'Vacation' || $req['deducts_vacation'] == 1) {
            
            // Los días a afectar deben ser negativos (resta)
            $dias_a_restar = -abs($req['days_amount']);
            
            $descripcion = "Consumo de vacaciones por solicitud #" . $request_id;
            if (!empty($req['start_time']) && !empty($req['end_time'])) {
                $descripcion = "Consumo por solicitud #" . $request_id . " (Permiso de " . substr($req['start_time'], 0, 5) . " a " . substr($req['end_time'], 0, 5) . ")";
            }
            
            $stmt_kardex = $connect_hr_leaves->prepare("
                INSERT INTO hr_vacation_transactions 
                (user_id, transaction_type, affected_days, description, request_id_ref) 
                VALUES (?, 'Vacation_Consumption', ?, ?, ?)
            ");
            $stmt_kardex->execute([$req['user_id'], $dias_a_restar, $descripcion, $request_id]);
        }
        
        // Confirmar transacción
        $connect_hr_leaves->commit();
        
        echo json_encode(['status' => 'success', 'message' => 'Solicitud aprobada y días rebajados correctamente (si aplica).']);
        
    } catch (Throwable $e) {
        if (isset($connect_hr_leaves) && $connect_hr_leaves->inTransaction()) {
            $connect_hr_leaves->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Internal error: ' . $e->getMessage()]);
    }
}
