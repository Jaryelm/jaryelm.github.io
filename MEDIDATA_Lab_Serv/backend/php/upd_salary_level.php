<?php
include_once '../registros/session_check.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['upd_salary_level'])) {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
    exit;
}

$pdo = medidata_rrhh_json_require();

$id = (int) ($_POST['id'] ?? 0);
$level_name = trim((string) ($_POST['level_name'] ?? ''));
$position_category = trim((string) ($_POST['position_category'] ?? ''));
$min_salary = (float) ($_POST['min_salary'] ?? 0);
$max_salary = (float) ($_POST['max_salary'] ?? 0);
$updated_by = trim((string) ($_POST['updated_by'] ?? ($name ?? 'sistema')));

if ($id <= 0 || $level_name === '' || $position_category === '' || $min_salary <= 0 || $max_salary < $min_salary) {
    echo json_encode(['success' => false, 'message' => 'Complete todos los campos con valores válidos.']);
    exit;
}

try {
    $sql = "UPDATE salary_levels SET 
                level_name = ?, position_category = ?, min_salary = ?, max_salary = ?, updated_by = ? 
            WHERE id = ? AND deleted = 0";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$level_name, $position_category, $min_salary, $max_salary, $updated_by, $id]);

    echo json_encode([
        'success' => (bool) $result,
        'message' => $result ? 'Nivel salarial actualizado con éxito' : 'No se pudo actualizar el nivel salarial',
    ]);
} catch (Throwable $e) {
    error_log('Error upd_salary_level: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>