<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['add_schedule'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$name_schedule = trim((string) ($_POST['name'] ?? ''));
$created_by = trim((string) ($_POST['created_by'] ?? ($name ?? 'sistema')));
$details = $_POST['details'] ?? []; // Expecting array of {day, entry_time, exit_time}

if ($name_schedule === '') {
    echo json_encode(['success' => false, 'message' => 'El nombre del horario es obligatorio.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert Master
    $sqlMaster = "INSERT INTO schedules (name, created_by) VALUES (?, ?)";
    $stmtM = $pdo->prepare($sqlMaster);
    $stmtM->execute([$name_schedule, $created_by]);
    $id_schedule = $pdo->lastInsertId();

    // Insert Details
    if (!empty($details) && is_array($details)) {
        $sqlDetail = "INSERT INTO schedule_details (id_schedule, day, entry_time, exit_time) VALUES (?, ?, ?, ?)";
        $stmtD = $pdo->prepare($sqlDetail);
        foreach ($details as $d) {
            if (!empty($d['day']) && !empty($d['entry_time']) && !empty($d['exit_time'])) {
                $stmtD->execute([$id_schedule, $d['day'], $d['entry_time'], $d['exit_time']]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Horario registrado con éxito']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error add_schedule: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>