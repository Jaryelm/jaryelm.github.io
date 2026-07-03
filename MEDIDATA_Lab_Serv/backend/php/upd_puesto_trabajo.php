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
$id_salary_level = (int) ($_POST['id_salary_level'] ?? 0);
$special_conditions = trim((string) ($_POST['special_conditions'] ?? '')) ?: null;
$suggested_psychometric_tests = trim((string) ($_POST['suggested_psychometric_tests'] ?? '')) ?: null;

$updated_by = trim((string) ($_POST['updated_by'] ?? ($name ?? 'sistema')));

if ($id <= 0 || $id_salary_level <= 0 || $id_departament <= 0) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios.']);
    exit;
}

$department_name = medidata_rrhh_departament_name($pdo, $id_departament);
if ($department_name === '') {
    echo json_encode(['success' => false, 'message' => 'El departamento seleccionado no es válido.']);
    exit;
}

try {
    $params = [
        $id_positions,
        $id_departament,
        $department_name,
        $id_salary_level,
        $immediate_boss,
        $objective,
        $main_functions,
        $academic_requirements,
        $required_experience,
        $technical_competencies,
        $soft_competencies,
        $special_conditions,
        $suggested_psychometric_tests,
        $updated_by
    ];

    $fileSql = '';
    if (isset($_FILES['job_profile_file'])) {
        $profileUpload = medidata_rrhh_job_profile_upload($_FILES['job_profile_file']);
        if ($profileUpload['error'] !== null) {
            echo json_encode(['success' => false, 'message' => $profileUpload['error']]);
            exit;
        }
        if ($profileUpload['content'] !== null) {
            $fileSql = ', job_profile_file = ?, job_profile_mime_type = ?';
            $params[] = $profileUpload['content'];
            $params[] = $profileUpload['mime'];
        }
    }

    $params[] = $id;

    $sql = "UPDATE positions_details SET
                id_positions = ?, id_departament = ?, department = ?, id_salary_level = ?, immediate_boss = ?, objective = ?,
                main_functions = ?, academic_requirements = ?, required_experience = ?,
                technical_competencies = ?, soft_competencies = ?,
                special_conditions = ?, suggested_psychometric_tests = ?, updated_by = ?
                $fileSql
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Puesto actualizado con éxito' : 'No se pudo actualizar el puesto',
    ]);
} catch (Throwable $e) {
    error_log('Error upd_puesto_trabajo: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>