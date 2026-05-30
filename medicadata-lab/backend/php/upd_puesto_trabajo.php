<?php
include_once '../registros/session_check.php';
$conexion_rrhh = $connect_rrhh; // Alias for compatibility

if (isset($_POST['upd_puesto'])) {
    $id = $_POST['id'];
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
    $updated_by = $name;

    try {
        $sql = "UPDATE positions_details SET 
                    id_positions = ?, department = ?, immediate_boss = ?, objective = ?, 
                    main_functions = ?, academic_requirements = ?, required_experience = ?, 
                    technical_competencies = ?, soft_competencies = ?, schedule = ?, 
                    shift_type = ?, salary_range = ?, special_conditions = ?, 
                    suggested_psychometric_tests = ?, updated_by = ? 
                WHERE id = ?";
        
        $stmt = $connect_rrhh->prepare($sql);
        $result = $stmt->execute([
            $id_positions, $department, $immediate_boss, $objective,
            $main_functions, $academic_requirements, $required_experience,
            $technical_competencies, $soft_competencies, $schedule,
            $shift_type, $salary_range, $special_conditions,
            $suggested_psychometric_tests, $updated_by, $id
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Puesto actualizado con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el puesto']);
        }
    } catch (Exception $e) {
        error_log("Error upd_puesto_trabajo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
</head>
<body>
</body>
</html>
