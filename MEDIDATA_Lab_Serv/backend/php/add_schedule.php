<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
require_once __DIR__ . '/schedule_lib.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['add_schedule'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();
medidata_schedule_ensure_schema($pdo);

$name_schedule = trim((string) ($_POST['name'] ?? ''));
$created_by = trim((string) ($_POST['created_by'] ?? ($name ?? 'sistema')));
$details = $_POST['details'] ?? [];
$breakMinutes = medidata_schedule_normalize_break_minutes($_POST['break_minutes'] ?? 0);
$calc = medidata_schedule_calc_weekly(is_array($details) ? $details : [], $breakMinutes);

if ($name_schedule === '') {
    echo json_encode(['success' => false, 'message' => 'El nombre del horario es obligatorio.']);
    exit;
}

if ($calc['active_days'] === 0) {
    echo json_encode(['success' => false, 'message' => 'Debe configurar al menos un día con hora de entrada y salida válidas.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmtM = $pdo->prepare(
        'INSERT INTO schedules (name, break_minutes, weekly_effective_hours, created_by) VALUES (?, ?, ?, ?)'
    );
    $stmtM->execute([
        $name_schedule,
        $breakMinutes,
        $calc['effective_hours'],
        $created_by,
    ]);
    $id_schedule = (int) $pdo->lastInsertId();

    if (!empty($details) && is_array($details)) {
        $stmtD = $pdo->prepare(
            'INSERT INTO schedule_details (id_schedule, day, entry_time, exit_time, apply_break) VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($details as $d) {
            if (!empty($d['day']) && !empty($d['entry_time']) && !empty($d['exit_time'])) {
                $applyBreak = !empty($d['apply_break']) ? 1 : 0;
                $stmtD->execute([$id_schedule, $d['day'], $d['entry_time'], $d['exit_time'], $applyBreak]);
            }
        }
    }

    $pdo->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Horario registrado con éxito. Tiempo efectivo semanal: ' . medidata_schedule_format_hours($calc['effective_hours']) . '.',
        'weekly_effective_hours' => $calc['effective_hours'],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error add_schedule: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
