<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['add_puesto'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

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

// New fields from schema
$required_docs_hiring = json_encode([]); // Default empty JSON array
$status = 'Activo'; 

$created_by = trim((string) ($_POST['created_by'] ?? ($name ?? 'sistema')));

if ($id_positions <= 0 || $id_departament <= 0 || $immediate_boss === '' || $objective === ''
    || $main_functions === '' || $academic_requirements === '' || $required_experience === ''
    || $technical_competencies === '' || $soft_competencies === ''
    || $id_salary_level <= 0) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos obligatorios.']);
    exit;
}

try {
    $sql = "INSERT INTO positions_details (
                id_positions, id_departament, id_salary_level, immediate_boss, objective,
                main_functions, academic_requirements, required_experience,
                technical_competencies, soft_competencies, special_conditions,
                suggested_psychometric_tests, required_docs_hiring, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $id_positions,
        $id_departament,
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
        $required_docs_hiring,
        $status,
        $created_by,
    ]);

    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Puesto registrado con éxito' : 'No se pudo registrar el puesto',
    ]);
} catch (Throwable $e) {
    error_log('Error add_puesto_trabajo: ' . $e->getMessage());
    $msg = $e->getMessage();
    if (stripos($msg, 'foreign key') !== false || stripos($msg, '1452') !== false) {
        $msg = 'El puesto base seleccionado no es válido. Verifique que exista en Posiciones de Trabajo.';
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $msg]);
}
?>