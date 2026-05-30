<?php
include_once '../registros/session_check.php';
header('Content-Type: application/json');

if (isset($_POST['add_puesto'])) {
    $id_positions = $_POST['id_position']; 
    $department = $_POST['department'];
    $immediate_boss = $_POST['immediate_boss'];
    $objective = $_POST['objective'];
    $main_functions = $_POST['main_functions'];
    $academic_requirements = $_POST['academic_requirements'];
    $required_experience = $_POST['required_experience'];
    $technical_competencies = $_POST['technical_competencies'];
    $soft_competencies = $_POST['soft_competencies'];
    $schedule = $_POST['schedule'] ?? null;
    $shift_type = $_POST['shift_type'];

    $salary_range = $_POST['salary_range'] ?? null;
    $special_conditions = $_POST['special_conditions'] ?? null;
    $suggested_psychometric_tests = $_POST['suggested_psychometric_tests'] ?? null;
    $created_by = $_POST['created_by'];

    try {
        $sql = "INSERT INTO positions_details (
                    id_positions, department, immediate_boss, objective, 
                    main_functions, academic_requirements, required_experience, 
                    technical_competencies, soft_competencies, schedule, 
                    shift_type, salary_range, special_conditions, 
                    suggested_psychometric_tests, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([
            $id_positions, $department, $immediate_boss, $objective,
            $main_functions, $academic_requirements, $required_experience,
            $technical_competencies, $soft_competencies, $schedule,
            $shift_type, $salary_range, $special_conditions,
            $suggested_psychometric_tests, $created_by
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Puesto registrado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo registrar el puesto']);
        }
    } catch (Exception $e) {
        error_log("Error add_puesto_trabajo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Petición no válida']);
}
?>
