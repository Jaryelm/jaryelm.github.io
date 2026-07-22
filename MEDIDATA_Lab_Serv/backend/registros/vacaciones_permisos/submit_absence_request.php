<?php
require_once '../../session_check.php';
require_once '../../../backend/bd/Conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($connect_hr_leaves)) throw new Exception("Sin conexión a BD.");
        
        $user_id = $_SESSION['id'];
        $type_id = $_POST['type_id'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $days_amount = $_POST['days_amount'] ?? null;
        $comments = $_POST['comments'] ?? '';
        $is_paid_vacation = isset($_POST['is_paid_vacation']) && $_POST['is_paid_vacation'] == '1' ? 1 : 0;
        
        if (!$type_id || !$start_date || !$end_date || !$days_amount) {
            throw new Exception("Por favor complete todos los campos requeridos.");
        }
        
        // 1. Obtener la jerarquía del rol del usuario para definir su workflow
        // Check if there is an explicit role assigned in hr_workflow_roles
        // Para simplificar, buscamos si su rol específico tiene un workflow, sino tomamos el default
        $rol_usuario = $_SESSION['rol'];
        
        $stmt_wf = $connect_hr_leaves->prepare("
            SELECT workflow_id FROM hr_workflow_roles 
            WHERE role_name = ? LIMIT 1
        ");
        $stmt_wf->execute([$rol_usuario]);
        $wf_res = $stmt_wf->fetch(PDO::FETCH_ASSOC);
        
        if ($wf_res) {
            $workflow_id = $wf_res['workflow_id'];
        } else {
            // Fallback al workflow del tipo de ausencia
            $stmt_type = $connect_hr_leaves->prepare("SELECT default_workflow_id FROM hr_absence_types WHERE type_id = ?");
            $stmt_type->execute([$type_id]);
            $type_res = $stmt_type->fetch(PDO::FETCH_ASSOC);
            $workflow_id = $type_res['default_workflow_id'] ?? 1; // Default
        }
        
        // 2. Determinar si hay documentacion (Opcional en este flujo)
        $proof_doc_path = null;
        if (isset($_FILES['proof_doc']) && $_FILES['proof_doc']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../../frontend/vacaciones_permisos/uploads/';
            if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . basename($_FILES['proof_doc']['name']);
            if(move_uploaded_file($_FILES['proof_doc']['tmp_name'], $upload_dir . $filename)) {
                $proof_doc_path = 'uploads/' . $filename;
            }
        }
        
        // 3. Insertar solicitud
        $stmt = $connect_hr_leaves->prepare("
            INSERT INTO hr_absence_requests 
            (user_id, type_id, start_date, end_date, start_time, end_time, days_amount, request_status, workflow_id, comments, proof_doc_path, is_paid_vacation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id, $type_id, $start_date, $end_date, $start_time, $end_time, $days_amount, $workflow_id, $comments, $proof_doc_path, $is_paid_vacation
        ]);
        
        $request_id = $connect_hr_leaves->lastInsertId();
        
        // 4. Iniciar log de historial de aprobación
        $stmt_hist = $connect_hr_leaves->prepare("
            INSERT INTO hr_approval_history (request_id, step_order, action_taken, actor_id, comments)
            VALUES (?, 0, 'Submitted', ?, 'Solicitud creada por el usuario')
        ");
        $stmt_hist->execute([$request_id, $user_id]);
        
        echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada correctamente.']);
        
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}

