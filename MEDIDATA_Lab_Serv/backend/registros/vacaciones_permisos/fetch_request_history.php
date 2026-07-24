<?php
// Devuelve el historial/estado del flujo de aprobación de UNA solicitud:
// sus pasos con estado (aprobado / rechazado / actual / en espera), quién resolvió
// cada paso y quién falta. El empleado sólo ve sus propias solicitudes; RRHH/Admin ve todas.
require_once '../session_check.php';
require_once '../../bd/Conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

function vp_hist_approver_label(array $s): string
{
    switch ($s['approver_type']) {
        case 'Direct_Manager':     return 'Jefe Inmediato';
        case 'Department_Manager': return 'Jefe de Departamento';
        case 'Specific_Role':      return str_replace('_', ' ', (string) ($s['approver_role_name'] ?? 'Rol'));
        case 'Specific_User':      return !empty($s['approver_user_name']) ? $s['approver_user_name'] : ('Usuario #' . (int) $s['approver_user_id']);
        default:                   return (string) $s['approver_type'];
    }
}

try {
    global $connect;
    if (!$connect) throw new Exception('Sin conexión a la base de datos.');

    $request_id = isset($_GET['request_id']) ? (int) $_GET['request_id'] : 0;
    if (!$request_id) throw new Exception('ID de solicitud requerido.');

    $rol    = $_SESSION['rol'] ?? '';
    $uid    = (int) $_SESSION['id'];
    $isPriv = in_array($rol, ['Administrador', 'Recursos_Humanos'], true);

    // Solicitud + workflow
    $stmt = $connect->prepare("
        SELECT r.request_id, r.user_id, r.workflow_id, r.current_step_order, r.request_status,
               r.start_date, r.end_date, r.days_amount,
               u.name AS user_name, t.name AS type_name, w.name AS workflow_name
        FROM medic9ue_hr_leaves.hr_absence_requests r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN medic9ue_hr_leaves.hr_absence_types t ON r.type_id = t.type_id
        LEFT JOIN medic9ue_hr_leaves.hr_approval_workflows w ON r.workflow_id = w.workflow_id
        WHERE r.request_id = ?
    ");
    $stmt->execute([$request_id]);
    $req = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$req) throw new Exception('Solicitud no encontrada.');
    if (!$isPriv && (int) $req['user_id'] !== $uid) throw new Exception('No autorizado.');

    $status  = $req['request_status'];
    $current = (int) $req['current_step_order'];

    // Pasos del workflow
    $stmt = $connect->prepare("
        SELECT s.step_id, s.step_order, s.approver_type, s.approver_role_name, s.approver_user_id, u.name AS approver_user_name
        FROM medic9ue_hr_leaves.hr_approval_workflow_steps s
        LEFT JOIN users u ON s.approver_user_id = u.id
        WHERE s.workflow_id = ?
        ORDER BY s.step_order ASC
    ");
    $stmt->execute([(int) $req['workflow_id']]);
    $stepRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decisiones registradas (última por step_order)
    $stmt = $connect->prepare("
        SELECT l.step_order, l.decision_status, l.comments, l.created_at, l.approver_user_id, u.name AS approver_name
        FROM medic9ue_hr_leaves.hr_absence_approval_logs l
        LEFT JOIN users u ON l.approver_user_id = u.id
        WHERE l.request_id = ?
        ORDER BY l.step_order ASC, l.created_at ASC
    ");
    $stmt->execute([$request_id]);
    $logsByStep = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $l) {
        $logsByStep[(int) $l['step_order']] = $l; // la más reciente gana
    }

    $steps = [];
    foreach ($stepRows as $s) {
        $ord   = (int) $s['step_order'];
        $state = 'waiting';
        $by = null; $at = null; $comment = null;

        if (isset($logsByStep[$ord])) {
            $lg      = $logsByStep[$ord];
            $state   = ($lg['decision_status'] === 'Approved') ? 'approved' : 'rejected';
            $by      = $lg['approver_name'];
            $at      = $lg['created_at'];
            $comment = $lg['comments'];
        } elseif ($status === 'Rejected') {
            $state = 'cancelled';
        } elseif ($status === 'Cancelled') {
            $state = 'cancelled';
        } elseif ($status === 'Approved') {
            // Aprobada completa aunque falte log explícito (flujo sin pasos)
            $state = 'approved';
        } elseif (($status === 'Pending' || $status === 'In_Progress') && $ord === $current) {
            $state = 'current';
        } else {
            $state = 'waiting';
        }

        $steps[] = [
            'step_order' => $ord,
            'label'      => vp_hist_approver_label($s),
            'state'      => $state,
            'by'         => $by,
            'at'         => $at,
            'comment'    => $comment,
        ];
    }

    // Documentos adjuntos de la solicitud
    $stmt = $connect->prepare("
        SELECT attachment_id, original_filename, format
        FROM medic9ue_hr_leaves.hr_absence_attachments
        WHERE request_id = ?
        ORDER BY attachment_id ASC
    ");
    $stmt->execute([$request_id]);
    $attachments = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $attachments[] = [
            'id'     => (int) $a['attachment_id'],
            'name'   => $a['original_filename'],
            'format' => $a['format'],
        ];
    }

    // Alerta (solo RRHH/Admin): otros colaboradores del MISMO departamento con vacaciones
    // que se traslapan con las fechas de esta solicitud.
    $dept_overlaps = [];
    if ($isPriv) {
        $staffUnion = "
            SELECT id_user, id_departamento, CONCAT(nomadm, ' ', apeadm) AS nombre FROM staff_administrative WHERE id_user IS NOT NULL
            UNION ALL SELECT id_user, id_departamento, CONCAT(nodoc, ' ', apdoc) FROM doctor WHERE id_user IS NOT NULL
            UNION ALL SELECT id_user, id_departamento, CONCAT(nomnur, ' ', apenur) FROM nurse WHERE id_user IS NOT NULL
            UNION ALL SELECT id_user, id_departamento, CONCAT(nomsg, ' ', apesg) FROM staff_general_services WHERE id_user IS NOT NULL
            UNION ALL SELECT id_user, id_departamento, CONCAT(nommf, ' ', apemf) FROM staff_medifarma WHERE id_user IS NOT NULL
        ";
        $stmtDep = $connect->prepare("SELECT id_departamento FROM ($staffUnion) s WHERE s.id_user = ? LIMIT 1");
        $stmtDep->execute([(int) $req['user_id']]);
        $dep = $stmtDep->fetchColumn();

        if ($dep) {
            $stmtOv = $connect->prepare("
                SELECT s.nombre, r2.start_date, r2.end_date
                FROM medic9ue_hr_leaves.hr_absence_requests r2
                JOIN medic9ue_hr_leaves.hr_absence_types t2 ON r2.type_id = t2.type_id
                JOIN ($staffUnion) s ON r2.user_id = s.id_user
                WHERE t2.category = 'Vacation'
                  AND r2.request_status IN ('Approved', 'Pending', 'In_Progress')
                  AND r2.request_id <> ?
                  AND s.id_user <> ?
                  AND s.id_departamento = ?
                  AND r2.start_date <= ? AND r2.end_date >= ?
                ORDER BY r2.start_date ASC
            ");
            $stmtOv->execute([$request_id, (int) $req['user_id'], $dep, $req['end_date'], $req['start_date']]);
            foreach ($stmtOv->fetchAll(PDO::FETCH_ASSOC) as $o) {
                $dept_overlaps[] = [
                    'name'       => $o['nombre'],
                    'start_date' => $o['start_date'],
                    'end_date'   => $o['end_date'],
                ];
            }
        }
    }

    echo json_encode([
        'attachments'   => $attachments,
        'dept_overlaps' => $dept_overlaps,
        'request' => [
            'request_id'         => (int) $req['request_id'],
            'user_name'          => $req['user_name'],
            'type_name'          => $req['type_name'],
            'workflow_name'      => $req['workflow_name'],
            'status'             => $status,
            'current_step_order' => $current,
            'start_date'         => $req['start_date'],
            'end_date'           => $req['end_date'],
            'days_amount'        => round((float) $req['days_amount'], 2),
        ],
        'steps'             => $steps,
        'has_workflow_steps' => count($stepRows) > 0,
    ]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Internal error: ' . $e->getMessage()]);
}
