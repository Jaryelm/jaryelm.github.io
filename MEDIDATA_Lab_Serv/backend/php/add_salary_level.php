<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['add_salary_level'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$level_name = trim((string) ($_POST['level_name'] ?? ''));
$position_category = trim((string) ($_POST['position_category'] ?? ''));
$min_salary = (float) ($_POST['min_salary'] ?? 0);
$max_salary = (float) ($_POST['max_salary'] ?? 0);
$created_by = trim((string) ($_POST['created_by'] ?? ($name ?? 'sistema')));

if ($level_name === '' || $position_category === '' || $min_salary <= 0 || $max_salary < $min_salary) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos con valores válidos. El salario máximo no puede ser menor al mínimo.']);
    exit;
}

try {
    $sql = "INSERT INTO salary_levels (level_name, position_category, min_salary, max_salary, created_by) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$level_name, $position_category, $min_salary, $max_salary, $created_by]);

    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Nivel salarial registrado con éxito' : 'No se pudo registrar el nivel salarial',
    ]);
} catch (Throwable $e) {
    error_log('Error add_salary_level: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>