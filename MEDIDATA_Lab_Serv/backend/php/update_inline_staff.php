<?php
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/staff_colaborador_bootstrap.php';
require_once __DIR__ . '/users_rrhh_extra_lib.php';
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

    // Cuentas tipo "Usuario": identidad en `users`, datos RRHH en `users_rrhh_extra`.
    if ($table === 'users') {
        if ($id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            exit;
        }
        $finalValue = ($value === '' || $value === '—' || $value === '-') ? null : $value;
        // El biométrico de usuarios vive en users.uid_biometrico.
        if ($field === 'id_biometrico') {
            $field = 'uid_biometrico';
        }
        try {
            if (in_array($field, medidata_users_rrhh_extra_identity_fields(), true)) {
                $stmt = $connect->prepare("UPDATE users SET {$field} = :val WHERE id = :id");
                $stmt->bindValue(':val', $finalValue, $finalValue === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
                echo json_encode(['status' => 'success', 'message' => 'Actualizado correctamente']);
            } elseif (in_array($field, medidata_users_rrhh_extra_fields(), true)) {
                $ok = medidata_users_rrhh_extra_save_field($connect, $id, $field, $finalValue);
                echo json_encode($ok
                    ? ['status' => 'success', 'message' => 'Actualizado correctamente']
                    : ['status' => 'error', 'message' => 'No se pudo guardar']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Operación no permitida']);
            }
        } catch (PDOException $e) {
            error_log('update_inline_staff.php (users): ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Error de base de datos']);
        }
        exit;
    }

    // Allowlist de campos y tablas para seguridad
    $allowed_tables = ['staff_administrative', 'staff_general_services', 'nurse', 'doctor'];
    $allowed_fields = [
        'id_biometrico', 'num_locker', 
        'num_empleado', 'numide', 'nomadm', 'apeadm', 
        'salario', 'cuenta_bac', 'telefono', 'fecha_ingreso',
        'tipo_empleado', 'sexadm', 'id_departamento', 'id_salary_level',
        // Nurse fields
        'nomnur', 'apenur', 'sexnur',
        // Doctor fields
        'ceddoc', 'nodoc', 'apdoc', 'sexd',
        // SG fields
        'nomsg', 'apesg', 'sexsg'
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
