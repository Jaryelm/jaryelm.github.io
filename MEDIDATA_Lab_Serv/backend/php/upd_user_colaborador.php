<?php
/**
 * Guardado del formulario completo de edición de colaboradores tipo "Usuario".
 * Identidad/login -> tabla `users`. Datos de RRHH y contrato -> `users_rrhh_extra`.
 * Se incluye al final de frontend/recursos_humanos/editar_colaborador_usr.php.
 */
if (!isset($_POST['upd_user_colab'])) {
    return;
}

require_once __DIR__ . '/users_rrhh_extra_lib.php';

$idUser = (int) ($_POST['midp'] ?? 0);

try {
    if ($idUser <= 0) {
        throw new RuntimeException('Identificador no válido.');
    }
    medidata_users_rrhh_extra_ensure($connect);

    // 1) Identidad en `users` (sin tocar usuario/contraseña).
    $name = trim((string) ($_POST['name'] ?? ''));
    if ($name === '') {
        throw new RuntimeException('El nombre completo es obligatorio.');
    }
    $stmtU = $connect->prepare(
        "UPDATE users SET name = :name, cedula = :cedula, sexo = :sexo,
                          email = :email, rol = :rol, uid_biometrico = :uid
         WHERE id = :id LIMIT 1"
    );
    $stmtU->execute([
        ':name' => $name,
        ':cedula' => ($v = trim((string) ($_POST['cedula'] ?? ''))) !== '' ? $v : null,
        ':sexo' => ($v = trim((string) ($_POST['sexo'] ?? ''))) !== '' ? $v : null,
        ':email' => trim((string) ($_POST['email'] ?? '')),
        ':rol' => ($v = trim((string) ($_POST['rol'] ?? ''))) !== '' ? $v : null,
        ':uid' => ($v = trim((string) ($_POST['uid_biometrico'] ?? ''))) !== '' ? $v : null,
        ':id' => $idUser,
    ]);

    // 2) Datos de RRHH en `users_rrhh_extra` (upsert).
    $extra = [
        'num_empleado'      => trim((string) ($_POST['num_empleado'] ?? '')),
        'tipo_empleado'     => trim((string) ($_POST['tipo_empleado'] ?? '')),
        'duracion_contrato' => trim((string) ($_POST['duracion_contrato'] ?? '')),
        'id_departamento'   => (int) ($_POST['id_departamento'] ?? 0) ?: null,
        'id_cargo'          => (int) ($_POST['id_cargo'] ?? 0) ?: null,
        'id_horario'        => (int) ($_POST['id_horario'] ?? 0) ?: null,
        'id_salary_level'   => (int) ($_POST['id_salary_level'] ?? 0) ?: null,
        'salario'           => trim((string) ($_POST['salario'] ?? '')),
        'cuenta_bac'        => trim((string) ($_POST['cuenta_bac'] ?? '')),
        'fecha_ingreso'     => trim((string) ($_POST['fecha_ingreso'] ?? '')) ?: null,
        'telefono'          => trim((string) ($_POST['telefono'] ?? '')),
        'correo_personal'   => trim((string) ($_POST['correo_personal'] ?? '')),
        'num_locker'        => trim((string) ($_POST['num_locker'] ?? '')),
    ];

    $cols = array_keys($extra);
    $placeholders = [];
    $updates = [];
    $params = [':id_user' => $idUser];
    foreach ($cols as $c) {
        $placeholders[] = ':' . $c;
        $updates[] = "{$c} = VALUES({$c})";
        $val = $extra[$c];
        $params[':' . $c] = ($val === '' ) ? null : $val;
    }
    $sqlExtra = "INSERT INTO users_rrhh_extra (id_user, " . implode(', ', $cols) . ")
                 VALUES (:id_user, " . implode(', ', $placeholders) . ")
                 ON DUPLICATE KEY UPDATE " . implode(', ', $updates);
    $connect->prepare($sqlExtra)->execute($params);

    // 3) Contrato (BLOB) si se subió uno nuevo.
    if (isset($_FILES['doc_contrato']) && $_FILES['doc_contrato']['error'] === UPLOAD_ERR_OK) {
        $bin = file_get_contents($_FILES['doc_contrato']['tmp_name']);
        if ($bin !== false) {
            medidata_users_rrhh_extra_save_contrato($connect, $idUser, $bin);
        }
    }

    $returnPage = 'lista_colaboradores_usr.php';
    if (isset($_POST['return_page']) && preg_match('/^[A-Za-z0-9_\-]+\.php$/', (string) $_POST['return_page'])) {
        $returnPage = (string) $_POST['return_page'];
    }
    echo '<script>Swal.fire("Actualizado", "Colaborador actualizado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    exit;
} catch (Throwable $e) {
    error_log('upd_user_colab: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
