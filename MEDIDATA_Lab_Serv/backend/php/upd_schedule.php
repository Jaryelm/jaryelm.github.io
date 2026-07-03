<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['upd_schedule'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$id = (int) ($_POST['id'] ?? 0);
$name_schedule = trim((string) ($_POST['name'] ?? ''));
$updated_by = trim((string) ($_POST['updated_by'] ?? ($name ?? 'sistema')));
$details = $_POST['details'] ?? [];

if ($id <= 0 || $name_schedule === '') {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update Master
    $sqlMaster = "UPDATE schedules SET name = ?, updated_by = ? WHERE id = ?";
    $stmtM = $pdo->prepare($sqlMaster);
    $stmtM->execute([$name_schedule, $updated_by, $id]);

    // Update Details: Simplest way is DELETE and INSERT again
    $stmtDel = $pdo->prepare("DELETE FROM schedule_details WHERE id_schedule = ?");
    $stmtDel->execute([$id]);

    if (!empty($details) && is_array($details)) {
        $sqlDetail = "INSERT INTO schedule_details (id_schedule, day, entry_time, exit_time) VALUES (?, ?, ?, ?)";
        $stmtD = $pdo->prepare($sqlDetail);
        foreach ($details as $d) {
            if (!empty($d['day']) && !empty($d['entry_time']) && !empty($d['exit_time'])) {
                $stmtD->execute([$id, $d['day'], $d['entry_time'], $d['exit_time']]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Horario actualizado con éxito']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error upd_schedule: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>