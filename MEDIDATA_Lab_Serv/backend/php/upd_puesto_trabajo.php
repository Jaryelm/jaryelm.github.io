<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['upd_puesto'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$id = (int) ($_POST['id'] ?? 0);
$id_positions = (int) ($_POST['id_position'] ?? 0);
$id_departament = (int) ($_POST['id_departament'] ?? 0);
$immediate_boss = trim((string) ($_POST['immediate_boss'] ?? ''));

$objective = trim((string) ($_POST['objective'] ?? ''));
$main_functions = trim((string) ($_POST['main_functions'] ?? ''));
$academic_requirements = trim((string) ($_POST['academic_requirements'] ?? ''));
$required_experience = trim((string) ($_POST['required_experience'] ?? ''));
$technical_competencies = trim((string) ($_POST['technical_competencies'] ?? ''));
$soft_competencies = trim((string) ($_POST['soft_competencies'] ?? ''));
$schedule = trim((string) ($_POST['schedule'] ?? ''));
$shift_type = trim((string) ($_POST['shift_type'] ?? ''));
$salary_range = trim((string) ($_POST['salary_range'] ?? '')) ?: null;
$special_conditions = trim((string) ($_POST['special_conditions'] ?? '')) ?: null;
$suggested_psychometric_tests = trim((string) ($_POST['suggested_psychometric_tests'] ?? '')) ?: null;

$updated_by = trim((string) ($_POST['updated_by'] ?? ($name ?? 'sistema')));

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de puesto no válido.']);
    exit;
}

try {
    // Note: department column was removed from schema provided by user
    $sql = "UPDATE positions_details SET
                id_positions = ?, id_departament = ?, immediate_boss = ?, objective = ?,
                main_functions = ?, academic_requirements = ?, required_experience = ?,
                technical_competencies = ?, soft_competencies = ?, schedule = ?,
                shift_type = ?, salary_range = ?, special_conditions = ?,
                suggested_psychometric_tests = ?, updated_by = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $id_positions,
        $id_departament,
        $immediate_boss,
        $objective,
        $main_functions,
        $academic_requirements,
        $required_experience,
        $technical_competencies,
        $soft_competencies,
        $schedule,
        $shift_type,
        $salary_range,
        $special_conditions,
        $suggested_psychometric_tests,
        $updated_by,
        $id,
    ]);

    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Puesto actualizado con éxito' : 'No se pudo actualizar el puesto',
    ]);
} catch (Throwable $e) {
    error_log('Error upd_puesto_trabajo: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>