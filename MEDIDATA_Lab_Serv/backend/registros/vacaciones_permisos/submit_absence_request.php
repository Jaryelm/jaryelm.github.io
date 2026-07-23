<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
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
        
        // --- VALIDACIONES AUTOMATICAS ---
        // 1. Fechas pasadas
        $hoy = date('Y-m-d');
        if ($start_date < $hoy) {
            throw new Exception("No se pueden solicitar fechas en el pasado.");
        }
        
        // 2. Traslapes (Vacaciones, permisos, incapacidades)
        $stmt_overlap = $connect_hr_leaves->prepare("
            SELECT request_id FROM hr_absence_requests 
            WHERE user_id = ? AND request_status != 'Rejected'
            AND start_date <= ? AND end_date >= ?
        ");
        $stmt_overlap->execute([$user_id, $end_date, $start_date]);
        if ($stmt_overlap->rowCount() > 0) {
            throw new Exception("Ya tienes una solicitud (vacación, permiso o incapacidad) que se traslapa con estas fechas.");
        }
        
        // 3. Obtener info del tipo de ausencia
        $stmt_t = $connect_hr_leaves->prepare("SELECT category, deducts_vacation FROM hr_absence_types WHERE type_id = ?");
        $stmt_t->execute([$type_id]);
        $type_info = $stmt_t->fetch(PDO::FETCH_ASSOC);
        // ---------------------------------
        
        $reference_workday_hours = null;

        if ($start_time && $end_time && $start_date === $end_date) {
            $req_start_min = strtotime($start_time);
            $req_end_min = strtotime($end_time);
            $requested_minutes = round(($req_end_min - $req_start_min) / 60);

            if ($requested_minutes <= 0) {
                throw new Exception("La hora de fin debe ser mayor a la hora de inicio.");
            }

            global $connect;
            $stmt_hor = $connect->prepare("
                SELECT id_horario FROM (
                    SELECT id_horario FROM staff_administrative WHERE id_user = ?
                    UNION
                    SELECT id_horario FROM doctor WHERE id_user = ?
                    UNION
                    SELECT id_horario FROM nurse WHERE id_user = ?
                    UNION
                    SELECT id_horario FROM staff_general_services WHERE id_user = ?
                    UNION
                    SELECT id_horario FROM staff_medifarma WHERE id_user = ?
                ) as h WHERE id_horario IS NOT NULL LIMIT 1
            ");
            $stmt_hor->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
            $hor_res = $stmt_hor->fetch(PDO::FETCH_ASSOC);

            if (!$hor_res || empty($hor_res['id_horario'])) {
                throw new Exception("El empleado no tiene un horario laboral asignado para calcular permisos por hora.");
            }
            $id_horario = $hor_res['id_horario'];

            $dias_semana = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'];
            $dia_idx = date('w', strtotime($start_date));
            $dia_str = $dias_semana[$dia_idx];

            $stmt_det = $connect->prepare("
                SELECT s.break_minutes, d.entry_time, d.exit_time, d.apply_break 
                FROM medic9ue_medi_rrhh_interviews.schedules s
                JOIN medic9ue_medi_rrhh_interviews.schedule_details d ON s.id = d.id_schedule 
                WHERE s.id = ? AND d.day = ?
            ");
            $stmt_det->execute([$id_horario, $dia_str]);
            $det = $stmt_det->fetch(PDO::FETCH_ASSOC);

            if (!$det || empty($det['entry_time']) || empty($det['exit_time'])) {
                throw new Exception("El empleado no labora en el día seleccionado según su horario.");
            }

            require_once __DIR__ . '/../../php/schedule_lib.php';
            $entry = medidata_schedule_time_to_minutes($det['entry_time']);
            $exit = medidata_schedule_time_to_minutes($det['exit_time']);
            $shift_minutes = medidata_schedule_shift_minutes($entry, $exit);

            if ($det['apply_break']) {
                $break = medidata_schedule_normalize_break_minutes($det['break_minutes']);
                $shift_minutes -= $break;
            }

            if ($shift_minutes <= 0) {
                throw new Exception("El turno no es válido.");
            }

            $reference_workday_hours = round($shift_minutes / 60, 2);
            $days_amount = round($requested_minutes / $shift_minutes, 4);
        }

        if (!$type_id || !$start_date || !$end_date || !$days_amount) {
            throw new Exception("Por favor complete todos los campos requeridos.");
        }
        
        // 4. Validar saldo si aplica
        if ($type_info && ($type_info['category'] === 'Vacation' || $type_info['deducts_vacation'] == 1)) {
            $stmt_bal = $connect_hr_leaves->prepare("SELECT SUM(affected_days) as balance FROM hr_vacation_transactions WHERE user_id = ?");
            $stmt_bal->execute([$user_id]);
            $bal = $stmt_bal->fetch(PDO::FETCH_ASSOC);
            $saldo_actual = $bal['balance'] ?? 0;
            
            if ($saldo_actual < $days_amount) {
                throw new Exception("No cuentas con suficientes días de vacaciones para esta solicitud. Saldo actual: " . round($saldo_actual, 2) . ", Solicitado: " . round($days_amount, 2));
            }
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
            $stmt_type = $connect_hr_leaves->prepare("SELECT workflow_id FROM hr_absence_types WHERE type_id = ?");
            $stmt_type->execute([$type_id]);
            $type_res = $stmt_type->fetch(PDO::FETCH_ASSOC);
            $workflow_id = $type_res['workflow_id'] ?? 1; // Default
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
            (user_id, type_id, start_date, end_date, start_time, end_time, days_amount, request_status, workflow_id, comments, reference_workday_hours) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id, $type_id, $start_date, $end_date, $start_time, $end_time, $days_amount, $workflow_id, $comments, $reference_workday_hours
        ]);
        
        $request_id = $connect_hr_leaves->lastInsertId();
        
        // 4. Insertar documento si existe
        if ($proof_doc_path) {
            $stmt_doc = $connect_hr_leaves->prepare("
                INSERT INTO hr_absence_attachments (request_id, original_filename, file_path) 
                VALUES (?, ?, ?)
            ");
            $stmt_doc->execute([$request_id, basename($proof_doc_path), $proof_doc_path]);
        }
        
        // Fin de inserción de solicitud
        
        echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada correctamente.']);
        
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}

