<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['upd_vacante'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$id = (int) ($_POST['id'] ?? 0);
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
$updated_by = trim((string) ($_POST['updated_by'] ?? ($name ?? 'sistema')));

if ($id <= 0 || $id_position <= 0 || $reason === ''
    || $benefits === '' || $init_date === '' || $end_date === '') {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios.']);
    exit;
}
try {
    $sql = "UPDATE vacant_positions SET 
                id_position = ?, id_schedule = ?,
                available_slots = ?, reason = ?, priority = ?, rrhh_responsible = ?,
                internal_observations = ?,
                benefits = ?, init_date = ?,
                end_date = ?, updated_by = ?
            WHERE id = ? AND deleted = 0";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $id_position, $id_schedule,
        $available_slots, $reason, $priority, $rrhh_responsible,
        $internal_observations,
        $benefits, $init_date,
        $end_date, $updated_by,
        $id
    ]);


    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Vacante actualizada con éxito' : 'No se pudo actualizar la vacante',
    ]);
} catch (Throwable $e) {
    error_log('Error upd_vacante_trabajo: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
