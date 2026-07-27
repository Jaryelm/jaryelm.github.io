<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($connect)) throw new Exception("Sin conexión a BD.");
        
        $user_id = $_SESSION['id'];
        $type_id = $_POST['type_id'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
        $end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
        $days_amount = $_POST['days_amount'] ?? null;
        $comments = $_POST['comments'] ?? '';
        $is_paid_vacation = isset($_POST['is_paid_vacation']) && $_POST['is_paid_vacation'] == '1' ? 1 : 0;
        // Campos propios de incapacidad (solo se guardan si el tipo es Medical_Leave)
        $issuing_institution  = trim((string) ($_POST['issuing_institution'] ?? ''));
        $medical_leave_number = trim((string) ($_POST['medical_leave_number'] ?? ''));

        // Info del tipo de ausencia. Se obtiene ANTES del chequeo de traslape porque
        // necesitamos saber si esta solicitud es de vacaciones (para poder recortar los
        // días que caen dentro de una incapacidad) o si es una incapacidad.
        $stmt_t = $connect->prepare("SELECT category, deducts_vacation, requires_document FROM hr_absence_types WHERE type_id = ?");
        $stmt_t->execute([$type_id]);
        $type_info = $stmt_t->fetch(PDO::FETCH_ASSOC);
        $new_category       = $type_info['category'] ?? '';
        $new_es_vacacion    = ($new_category === 'Vacation' || (isset($type_info['deducts_vacation']) && $type_info['deducts_vacation'] == 1));
        $new_es_incapacidad = ($new_category === 'Medical_Leave');

        // --- VALIDACIONES AUTOMATICAS ---
        // 1. Fechas pasadas
        $hoy = date('Y-m-d');
        if ($start_date < $hoy) {
            throw new Exception("No se pueden solicitar fechas en el pasado.");
        }

        // 2. Traslapes (vacaciones, permisos, incapacidades). Para permisos por HORA en el
        //    mismo día, dos franjas horarias distintas y no solapadas NO son traslape.
        //    Excepción: una VACACIÓN que se traslapa con una INCAPACIDAD ya registrada NO se
        //    rechaza; más abajo se recalculan los días omitiendo el período de incapacidad.
        $new_is_partial = ($start_time && $end_time && $start_date === $end_date);

        $stmt_overlap = $connect->prepare("
            SELECT r.start_date, r.end_date, r.start_time, r.end_time, t.category
            FROM hr_absence_requests r
            JOIN hr_absence_types t ON r.type_id = t.type_id
            WHERE r.user_id = ? AND r.request_status NOT IN ('Rejected', 'Cancelled')
            AND r.start_date <= ? AND r.end_date >= ?
        ");
        $stmt_overlap->execute([$user_id, $end_date, $start_date]);

        foreach ($stmt_overlap->fetchAll(PDO::FETCH_ASSOC) as $ov) {
            $ov_is_partial = ($ov['start_time'] && $ov['end_time'] && $ov['start_date'] === $ov['end_date']);

            if ($new_is_partial && $ov_is_partial && $ov['start_date'] === $start_date) {
                // Ambos por hora el mismo día: conflicto sólo si los rangos horarios se solapan
                $ns = strtotime($start_time); $ne = strtotime($end_time);
                $os = strtotime($ov['start_time']); $oe = strtotime($ov['end_time']);
                if ($ns < $oe && $os < $ne) {
                    throw new Exception("Ya tienes un permiso por horas que se traslapa con este horario.");
                }
                continue; // franjas distintas: no es traslape
            }

            // Vacación de día completo que se traslapa con una incapacidad: permitido.
            // Los días de la incapacidad se descontarán del cómputo (ver más abajo), en
            // lugar de rechazar toda la solicitud.
            if ($new_es_vacacion && !$new_is_partial && $ov['category'] === 'Medical_Leave') {
                continue;
            }

            // Cualquier otro caso (día completo de por medio) => traslape real
            throw new Exception("Ya tienes una solicitud (vacación, permiso o incapacidad) que se traslapa con estas fechas.");
        }
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
        } else {
            // Día(s) completo(s): el total de días NO se toma del cliente. Se recalcula de
            // forma autoritativa contando sólo los días que el colaborador labora según su
            // horario activo y excluyendo feriados (p. ej. no cuenta domingos ni feriados).
            require_once __DIR__ . '/../../php/absence_workdays_lib.php';
            global $connect;
            $calc = medidata_absence_count_working_days(
                $connect,
                $user_id,
                $start_date,
                $end_date
            );
            $days_amount = $calc['days'];

            if ($days_amount <= 0) {
                throw new Exception("El rango seleccionado no contiene días laborables (feriados, días de descanso según tu horario o días cubiertos por una incapacidad).");
            }
        }

        if (!$type_id || !$start_date || !$end_date || !$days_amount) {
            throw new Exception("Por favor complete todos los campos requeridos.");
        }
        
        // 4. Validar saldo si aplica
        if ($type_info && ($type_info['category'] === 'Vacation' || $type_info['deducts_vacation'] == 1)) {
            $stmt_bal = $connect->prepare("SELECT SUM(affected_days) as balance FROM hr_vacation_transactions WHERE user_id = ?");
            $stmt_bal->execute([$user_id]);
            $bal = $stmt_bal->fetch(PDO::FETCH_ASSOC);
            $saldo_actual = $bal['balance'] ?? 0;
            
            if ($saldo_actual < $days_amount) {
                throw new Exception("No cuentas con suficientes días de vacaciones para esta solicitud. Saldo actual: " . round($saldo_actual, 2) . ", Solicitado: " . round($days_amount, 2));
            }
        }
        
        // 1. El flujo de aprobación se determina por el DEPARTAMENTO del solicitante
        //    (hr_workflow_departments); si su departamento no tiene flujo asignado se usa
        //    el flujo del tipo de ausencia y, en última instancia, el flujo por defecto.
        require_once __DIR__ . '/../../php/workflow_lib.php';
        global $connect;
        $workflow_id = medidata_workflow_para_usuario($connect, (int) $user_id, (int) $type_id);
        
        // 2. Documento de respaldo: validar tipo/tamaño y exigirlo si el tipo lo requiere.
        //    (El archivo se mueve DESPUÉS del INSERT, cuando ya tenemos el request_id.)
        $has_proof  = isset($_FILES['proof_doc']) && $_FILES['proof_doc']['error'] === UPLOAD_ERR_OK && $_FILES['proof_doc']['size'] > 0;
        $proof_ext  = null;
        $proof_mime = null;
        if ($has_proof) {
            $allowed_ext  = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
            $allowed_mime = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $max_bytes    = 5 * 1024 * 1024; // 5 MB

            $proof_ext = strtolower(pathinfo($_FILES['proof_doc']['name'], PATHINFO_EXTENSION));
            $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
            $proof_mime = $finfo ? finfo_file($finfo, $_FILES['proof_doc']['tmp_name']) : ($_FILES['proof_doc']['type'] ?? '');
            if ($finfo) finfo_close($finfo);

            if (!in_array($proof_ext, $allowed_ext, true)) {
                throw new Exception('Formato de documento no permitido. Use PDF, imagen (JPG/PNG/WEBP) o Word.');
            }
            if ($proof_mime && !in_array($proof_mime, $allowed_mime, true)) {
                throw new Exception('El contenido del documento no corresponde a un PDF/imagen/Word válido.');
            }
            if ($_FILES['proof_doc']['size'] > $max_bytes) {
                throw new Exception('El documento supera el tamaño máximo permitido (5 MB).');
            }
        }

        if (!empty($type_info['requires_document']) && !$has_proof) {
            throw new Exception('Este tipo de ausencia requiere adjuntar un documento de respaldo.');
        }
        
        // Datos de incapacidad: solo se almacenan cuando el tipo es Medical_Leave.
        $db_issuing_institution  = ($new_es_incapacidad && $issuing_institution !== '')  ? $issuing_institution  : null;
        $db_medical_leave_number = ($new_es_incapacidad && $medical_leave_number !== '') ? $medical_leave_number : null;

        // 3. Insertar solicitud (incluye la marca de pago en efectivo — vacaciones pagadas)
        $stmt = $connect->prepare("
            INSERT INTO hr_absence_requests
            (user_id, type_id, start_date, end_date, start_time, end_time, days_amount, is_cash_payout, request_status, workflow_id, comments, reference_workday_hours, issuing_institution, medical_leave_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $user_id, $type_id, $start_date, $end_date, $start_time, $end_time, $days_amount, $is_paid_vacation, $workflow_id, $comments, $reference_workday_hours, $db_issuing_institution, $db_medical_leave_number
        ]);
        
        $request_id = $connect->lastInsertId();

        // Auditoría: registrar la creación de la solicitud (no bloquea si falla)
        try {
            $connect->prepare("
                INSERT INTO hr_absence_audit_log (action_user_id, action_executed, affected_table, record_id, old_value, new_value)
                VALUES (?, 'CREATE_REQUEST', 'hr_absence_requests', ?, NULL, ?)
            ")->execute([
                $user_id,
                $request_id,
                json_encode([
                    'type_id'        => $type_id,
                    'start_date'     => $start_date,
                    'end_date'       => $end_date,
                    'days_amount'    => $days_amount,
                    'is_cash_payout' => $is_paid_vacation,
                ], JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable $e) { /* la auditoría no debe impedir el registro */ }

        // 4. Guardar el documento en un directorio protegido (fuera del alcance web directo)
        //    con nombre seguro, y registrar el adjunto.
        if ($has_proof) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Bloquear acceso HTTP directo a los archivos subidos
            $htaccess = $upload_dir . '.htaccess';
            if (!file_exists($htaccess)) {
                @file_put_contents($htaccess, "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
            }

            $safe_name = 'req' . (int) $request_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $proof_ext;
            if (move_uploaded_file($_FILES['proof_doc']['tmp_name'], $upload_dir . $safe_name)) {
                $stmt_doc = $connect->prepare("
                    INSERT INTO hr_absence_attachments (request_id, attachment_type, original_filename, file_path, format)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_doc->execute([
                    $request_id,
                    'Comprobante',
                    substr(basename($_FILES['proof_doc']['name']), 0, 250),
                    $safe_name,
                    $proof_mime ?: $proof_ext,
                ]);
            }
        }
        
        // Fin de inserción de solicitud
        
        echo json_encode(['status' => 'success', 'message' => 'Solicitud enviada correctamente.']);
        
    } catch (Throwable $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
}

