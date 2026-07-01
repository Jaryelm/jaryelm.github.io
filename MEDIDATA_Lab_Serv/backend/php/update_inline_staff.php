<?php
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/staff_colaborador_bootstrap.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
        exit;
    }

    $id = (int)($_POST['id'] ?? 0);
    $field = trim($_POST['field'] ?? '');
    $value = trim($_POST['value'] ?? '');
    $table = trim($_POST['table'] ?? '');
    $id_col = trim($_POST['id_col'] ?? '');

    // Allowlist de campos y tablas para seguridad
    $allowed_tables = ['staff_administrative', 'staff_general_services', 'nurse', 'doctor'];
    $allowed_fields = [
        'id_biometrico', 'num_locker', 
        'num_empleado', 'numide', 'nomadm', 'apeadm', 
        'salario', 'cuenta_bac', 'telefono', 'fecha_ingreso',
        'tipo_empleado', 'sexadm', 'id_departamento', 'id_salary_level', 'id_cargo',
        'correo_personal', 'correo_institucional', 'nacadm',
        // Nurse fields
        'nomnur', 'apenur', 'sexnur', 'nacinur',
        // Doctor fields
        'ceddoc', 'nodoc', 'apdoc', 'sexd', 'nacd',
        // SG fields
        'nomsg', 'apesg', 'sexsg', 'nacsg'
    ];

    if (!in_array($table, $allowed_tables) || !in_array($field, $allowed_fields)) {
        echo json_encode(['status' => 'error', 'message' => 'Operación no permitida']);
        exit;
    }

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
        exit;
    }

    try {
        $finalValue = ($value === '' || $value === '—' || $value === '-') ? null : $value;
        
        // Algunos campos numéricos pueden necesitar casteo, pero los string no.
        $int_fields = ['id_biometrico', 'id_departamento', 'id_cargo', 'id_horario', 'id_salary_level'];
        if ($finalValue !== null && in_array($field, $int_fields)) {
            $finalValue = (int)$finalValue;
        }

        $sql = "UPDATE {$table} SET {$field} = :val WHERE {$id_col} = :id";
        $stmt = $connect->prepare($sql);
        $stmt->execute([
            ':val' => $finalValue,
            ':id' => $id
        ]);
        
        echo json_encode(['status' => 'success', 'message' => 'Actualizado correctamente']);
    } catch (PDOException $e) {
        error_log('Error en update_inline_staff.php: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error de base de datos']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
}
