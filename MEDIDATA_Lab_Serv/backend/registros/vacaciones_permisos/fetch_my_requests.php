<?php
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../../bd/Conexion.php';
header('Content-Type: application/json');

if (!isset($_SESSION['id'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    global $connect_hr_leaves;
    if (!$connect_hr_leaves) throw new Exception("Sin conexión.");

    $user_id = $_SESSION['id'];

    $sql = "
        SELECT 
            r.request_id,
            t.name as type_name,
            r.start_date,
            r.end_date,
            r.start_time,
            r.end_time,
            r.days_amount,
            r.request_status,
            r.comments,
            (
                SELECT comments 
                FROM hr_absence_approval_logs h 
                WHERE h.request_id = r.request_id 
                ORDER BY step_order DESC LIMIT 1
            ) as last_comment,
            (
                SELECT decision_status 
                FROM hr_absence_approval_logs h 
                WHERE h.request_id = r.request_id 
                ORDER BY step_order DESC LIMIT 1
            ) as last_action
        FROM hr_absence_requests r
        JOIN hr_absence_types t ON r.type_id = t.type_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ";

    $stmt = $connect_hr_leaves->prepare($sql);
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estado -> clase .vp-badge (definida en cards.css) + etiqueta en español
    $estado_map = [
        'Pending'     => ['pending', 'Pendiente'],
        'In_Progress' => ['in-progress', 'En Proceso'],
        'Approved'    => ['approved', 'Aprobada'],
        'Rejected'    => ['rejected', 'Rechazada'],
        'Cancelled'   => ['cancelled', 'Cancelada'],
    ];

    $data = [];
    foreach ($requests as $req) {
        $r_status = $req['request_status'];
        [$badge_mod, $status_es] = $estado_map[$r_status] ?? ['neutral', $r_status];
        $status_badge = '<span class="vp-badge vp-badge--' . $badge_mod . '">' . htmlspecialchars((string) $status_es) . '</span>';

        // Rango de fechas (+ horas si es permiso por horas)
        $fechas = $req['start_date'];
        if ($req['end_date'] != $req['start_date']) {
            $fechas .= " al " . $req['end_date'];
        }
        if ($req['start_time'] && $req['end_time']) {
            $fechas .= " (" . date('H:i', strtotime($req['start_time'])) . " - " . date('H:i', strtotime($req['end_time'])) . ")";
        }

        // Separar la observación del colaborador de la resolución de RRHH.
        // approve_request.php anexa la resolución al campo comments con el marcador
        // "[RRHH - Aprobada|Rechazada]: <texto>". La observación es todo lo previo.
        $raw_comments  = (string) ($req['comments'] ?? '');
        $observaciones = $raw_comments;
        $resolucion    = '';
        if (preg_match('/\[RRHH - (?:Aprobada|Rechazada)\]:\s*(.*)$/s', $raw_comments, $m)) {
            $observaciones = trim(preg_replace('/\s*\[RRHH - (?:Aprobada|Rechazada)\]:.*$/s', '', $raw_comments));
            $resolucion    = trim($m[1]);
        }

        // Si existe un log de aprobación formal, tiene prioridad como resolución.
        if (!empty($req['last_comment'])) {
            $resolucion = (string) $req['last_comment'];
        }

        // Fallback: dejar claro el propósito de la columna aunque no haya comentario.
        $resolucion_muted = false;
        if ($resolucion === '') {
            $resolucion_muted = true;
            switch ($r_status) {
                case 'Approved':  $resolucion = 'Aprobada sin comentario'; break;
                case 'Rejected':  $resolucion = 'Rechazada sin comentario'; break;
                case 'Cancelled': $resolucion = 'Cancelada'; break;
                default:          $resolucion = 'En espera de aprobación'; break;
            }
        }

        $obs_html = ($observaciones !== '')
            ? htmlspecialchars($observaciones)
            : '<span class="vp-muted">—</span>';
        $res_html = $resolucion_muted
            ? '<span class="vp-muted">' . htmlspecialchars($resolucion) . '</span>'
            : htmlspecialchars($resolucion);

        $data[] = [
            'request_id'   => (int) $req['request_id'],
            'type_name'    => htmlspecialchars((string) $req['type_name']),
            'colaborador'  => htmlspecialchars($_SESSION['name'] ?? 'Usuario'),
            'fechas'       => htmlspecialchars($fechas),
            'days_amount'  => round((float) $req['days_amount'], 2),
            'status'       => $status_badge,
            'comments'     => $obs_html,
            'last_comment' => $res_html,
        ];
    }

    echo json_encode(['data' => $data]);

} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
?>
