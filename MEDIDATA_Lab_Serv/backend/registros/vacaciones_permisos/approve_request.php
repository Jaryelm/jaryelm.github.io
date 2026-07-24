<?php
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['Administrador', 'Recursos_Humanos'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

try {
    if (!isset($connect_hr_leaves) || !$connect_hr_leaves) throw new Exception("Sin conexión a BD.");

    $request_id  = $_POST['request_id'] ?? '';
    $decision    = $_POST['decision'] ?? 'Approved'; // 'Approved' | 'Rejected'
    $comment     = trim((string) ($_POST['comment'] ?? ''));
    $approver_id = (int) $_SESSION['id'];

    if ($request_id === '') throw new Exception("ID de solicitud requerido.");
    if (!in_array($decision, ['Approved', 'Rejected'], true)) throw new Exception("Decisión inválida.");

    // Auditoría de la decisión (no debe abortar la operación si falla)
    $audit = function ($action, $old, $new) use ($connect_hr_leaves, $approver_id, $request_id) {
        try {
            $connect_hr_leaves->prepare("
                INSERT INTO hr_absence_audit_log (action_user_id, action_executed, affected_table, record_id, old_value, new_value)
                VALUES (?, ?, 'hr_absence_requests', ?, ?, ?)
            ")->execute([$approver_id, $action, $request_id, $old, $new]);
        } catch (Throwable $e) { /* la auditoría no debe abortar la decisión */ }
    };

    $connect_hr_leaves->beginTransaction();

    // Solicitud + tipo de ausencia (bloqueada para evitar decisiones concurrentes)
    $stmt_req = $connect_hr_leaves->prepare("
        SELECT r.user_id, r.days_amount, r.request_status, r.start_time, r.end_time,
               r.workflow_id, r.current_step_order, r.is_cash_payout, t.category, t.deducts_vacation
        FROM hr_absence_requests r
        JOIN hr_absence_types t ON r.type_id = t.type_id
        WHERE r.request_id = ? FOR UPDATE
    ");
    $stmt_req->execute([$request_id]);
    $req = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$req) throw new Exception("Solicitud no encontrada.");
    if (in_array($req['request_status'], ['Approved', 'Rejected', 'Cancelled'], true)) {
        throw new Exception("La solicitud ya fue resuelta (" . $req['request_status'] . ").");
    }

    $current_order = (int) ($req['current_step_order'] ?? 1);
    $workflow_id   = !empty($req['workflow_id']) ? (int) $req['workflow_id'] : null;

    // Paso actual del flujo (si el workflow tiene pasos configurados)
    $current_step = null;
    if ($workflow_id) {
        $st = $connect_hr_leaves->prepare("
            SELECT step_id, step_order
            FROM hr_approval_workflow_steps
            WHERE workflow_id = ? AND step_order = ?
            LIMIT 1
        ");
        $st->execute([$workflow_id, $current_order]);
        $current_step = $st->fetch(PDO::FETCH_ASSOC);
    }

    // Registrar la decisión de este paso en el historial (sólo si existe un paso real:
    // hr_absence_approval_logs.step_id es NOT NULL con FK a los pasos del flujo).
    if ($current_step) {
        $lg = $connect_hr_leaves->prepare("
            INSERT INTO hr_absence_approval_logs
            (request_id, step_id, step_order, approver_user_id, decision_status, comments)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $lg->execute([
            $request_id,
            $current_step['step_id'],
            $current_order,
            $approver_id,
            $decision,
            ($comment !== '' ? $comment : null),
        ]);
    }

    // --- RECHAZO: termina el flujo inmediatamente ---
    if ($decision === 'Rejected') {
        $connect_hr_leaves->prepare("
            UPDATE hr_absence_requests SET request_status = 'Rejected', final_approver_id = ? WHERE request_id = ?
        ")->execute([$approver_id, $request_id]);

        $audit('REJECT_REQUEST', $req['request_status'], 'Rejected');

        $connect_hr_leaves->commit();
        echo json_encode(['status' => 'success', 'message' => 'Solicitud rechazada correctamente.']);
        exit;
    }

    // --- APROBACIÓN: ¿hay un paso siguiente? ---
    $next_order = null;
    if ($workflow_id) {
        $ns = $connect_hr_leaves->prepare("
            SELECT MIN(step_order) AS next_order
            FROM hr_approval_workflow_steps
            WHERE workflow_id = ? AND step_order > ?
        ");
        $ns->execute([$workflow_id, $current_order]);
        $nr = $ns->fetch(PDO::FETCH_ASSOC);
        $next_order = ($nr && $nr['next_order'] !== null) ? (int) $nr['next_order'] : null;
    }

    if ($next_order !== null) {
        // Avanzar al siguiente aprobador; la solicitud queda "En Proceso"
        $connect_hr_leaves->prepare("
            UPDATE hr_absence_requests SET request_status = 'In_Progress', current_step_order = ? WHERE request_id = ?
        ")->execute([$next_order, $request_id]);

        $audit('APPROVE_STEP', $req['request_status'], 'In_Progress (paso ' . $next_order . ')');

        $connect_hr_leaves->commit();
        echo json_encode(['status' => 'success', 'message' => 'Paso aprobado. La solicitud avanzó al siguiente aprobador del flujo.', 'advanced' => true]);
        exit;
    }

    // --- Último paso (o flujo sin pasos): aprobación FINAL + Kardex ---
    $connect_hr_leaves->prepare("
        UPDATE hr_absence_requests SET request_status = 'Approved', final_approver_id = ? WHERE request_id = ?
    ")->execute([$approver_id, $request_id]);

    if ($req['category'] === 'Vacation' || $req['deducts_vacation'] == 1) {
        $dias_a_restar   = -abs($req['days_amount']);
        $es_pago_efectivo = !empty($req['is_cash_payout']);

        if ($es_pago_efectivo) {
            // Vacaciones pagadas: los días salen del saldo como pago en efectivo (no como disfrute)
            $transaction_type = 'Cash_Payout';
            $descripcion      = "Pago en efectivo de vacaciones por solicitud #" . $request_id;
        } else {
            $transaction_type = 'Vacation_Consumption';
            $descripcion      = "Consumo de vacaciones por solicitud #" . $request_id;
            if (!empty($req['start_time']) && !empty($req['end_time'])) {
                $descripcion = "Consumo por solicitud #" . $request_id . " (Permiso de " . substr($req['start_time'], 0, 5) . " a " . substr($req['end_time'], 0, 5) . ")";
            }
        }

        $connect_hr_leaves->prepare("
            INSERT INTO hr_vacation_transactions
            (user_id, transaction_type, affected_days, description, request_id_ref)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$req['user_id'], $transaction_type, $dias_a_restar, $descripcion, $request_id]);
    }

    $audit('APPROVE_FINAL', $req['request_status'], 'Approved');

    $connect_hr_leaves->commit();
    echo json_encode(['status' => 'success', 'message' => 'Solicitud aprobada por completo (días rebajados si aplica).', 'completed' => true]);

} catch (Throwable $e) {
    if (isset($connect_hr_leaves) && $connect_hr_leaves instanceof PDO && $connect_hr_leaves->inTransaction()) {
        $connect_hr_leaves->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
