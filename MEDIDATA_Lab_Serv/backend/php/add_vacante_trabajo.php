<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['add_vacante'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$id_position = (int) ($_POST['id_position'] ?? 0);
$available_slots = (int) ($_POST['available_slots'] ?? 1);
$reason = trim((string) ($_POST['reason'] ?? ''));
$priority = trim((string) ($_POST['priority'] ?? 'Media'));
$id_schedule = !empty($_POST['id_schedule']) ? (int)$_POST['id_schedule'] : null;
$rrhh_responsible = trim((string) ($_POST['rrhh_responsible'] ?? '')) ?: null;
$internal_observations = trim((string) ($_POST['internal_observations'] ?? '')) ?: null;
$benefits = trim((string) ($_POST['benefits'] ?? ''));
$init_date = trim((string) ($_POST['init_date'] ?? ''));
$end_date = trim((string) ($_POST['end_date'] ?? ''));
$created_by = trim((string) ($_POST['created_by'] ?? ($name ?? 'sistema')));

if ($id_position <= 0 || $reason === ''
    || $benefits === '' || $init_date === '' || $end_date === '') {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios.']);
    exit;
}

if ($available_slots < 1) {
    $available_slots = 1;
}

if ($init_date > $end_date) {
    echo json_encode(['success' => false, 'message' => 'La fecha de cierre no puede ser anterior a la fecha de apertura.']);
    exit;
}

$allowedPriority = ['Baja', 'Media', 'Alta', 'Urgente'];
if (!in_array($priority, $allowedPriority, true)) {
    $priority = 'Media';
}

try {
    $sql = "INSERT INTO vacant_positions (
                id_position, id_schedule,
                available_slots, reason, priority, rrhh_responsible,
                internal_observations,
                benefits, init_date,
                end_date, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $id_position, $id_schedule,
        $available_slots, $reason, $priority, $rrhh_responsible,
        $internal_observations,
        $benefits, $init_date,
        $end_date, $created_by,
    ]);

    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Vacante registrada con éxito' : 'No se pudo registrar la vacante',
    ]);
} catch (Throwable $e) {
    error_log('Error add_vacante_trabajo: ' . $e->getMessage());
    $msg = $e->getMessage();
    if (stripos($msg, 'foreign key') !== false || stripos($msg, '1452') !== false) {
        $msg = 'El puesto detallado seleccionado no es válido. Registre primero un puesto de trabajo detallado.';
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $msg]);
}
?>