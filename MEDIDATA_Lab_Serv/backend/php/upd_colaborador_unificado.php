<?php
if (!isset($_POST['upd_colaborador_unificado'])) {
    return;
}

$table = $_POST['area_colaborador'] ?? '';
$id_primary = (int)($_POST['id_primary'] ?? 0);

require_once __DIR__ . '/staff_areas_lib.php';

if (!medidata_staff_area_is_valid($table) || $id_primary <= 0) {
    echo '<script>Swal.fire("Error", "Área o ID no válido.", "error");</script>';
    return;
}

$areaCols = medidata_staff_area_columns($table);
if (!$areaCols) {
    echo '<script>Swal.fire("Error", "Área de empleado no válida.", "error");</script>';
    return;
}

$col_id = $areaCols['pk'];
$col_numide = $areaCols['numide'];
$col_nombres = $areaCols['nombres'];
$col_apellidos = $areaCols['apellidos'];
$col_nac = $areaCols['nacimiento'];
$col_sexo = $areaCols['genero'];

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
require_once __DIR__ . '/staff_doctor_fields_lib.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';

medidata_staff_ensure_tables($connect);

$numide = strtoupper(trim((string) ($_POST['identificacion'] ?? '')));
$nombres = strtoupper(trim((string) ($_POST['nombres'] ?? '')));
$apellidos = strtoupper(trim((string) ($_POST['apellidos'] ?? '')));
$nacimiento = trim((string) ($_POST['fecha_nacimiento'] ?? ''));
$sexo = trim((string) ($_POST['genero'] ?? ''));
$idUser = medidata_staff_parse_id_user($_POST['id_user'] ?? null);

// Nuevos campos
$num_empleado = trim((string) ($_POST['num_empleado'] ?? ''));
$tipo_empleado = trim((string) ($_POST['tipo_empleado'] ?? 'Permanente'));
$duracion_contrato = trim((string) ($_POST['duracion_contrato'] ?? ''));
$fecha_ingreso = trim((string) ($_POST['fecha_ingreso'] ?? ''));
$id_departamento = (int) ($_POST['id_departamento'] ?? 0);
$id_cargo = (int) ($_POST['id_cargo'] ?? 0);
$id_horario = (int) ($_POST['id_horario'] ?? 0);
$id_salary_level = (int) ($_POST['id_salary_level'] ?? 0);
$salario = (float) ($_POST['salario'] ?? 0);
$cuenta_bac = trim((string) ($_POST['cuenta_bac'] ?? ''));
$telefono = trim((string) ($_POST['telefono'] ?? ''));
$correo_personal = trim((string) ($_POST['correo_personal'] ?? ''));
$correo_institucional = trim((string) ($_POST['correo_institucional'] ?? ''));
$num_locker = trim((string) ($_POST['num_locker'] ?? ''));
$id_biometrico = (int) ($_POST['id_biometrico'] ?? 0);

$doctorLegacy = null;
if ($table === 'doctor') {
    $doctorLegacy = medidata_staff_doctor_legacy_from_post($_POST, $telefono, $correo_personal, $correo_institucional);
    if ($doctorLegacy['nomesp'] === '') {
        echo '<script>Swal.fire("Especialidad requerida", "Indique la especialidad o categoría del médico.", "warning");</script>';
        return;
    }
}

try {
    if ($idUser !== null) {
        $linked = medidata_staff_id_user_linked($connect, $idUser, $table, $id_primary);
        if ($linked !== null) {
            throw new RuntimeException('Ese usuario ya está vinculado como colaborador de ' . $linked['label'] . '.');
        }
    }

    // Comprobar que no exista otra persona en la MISMA tabla con el mismo DNI
    $stmtCheck = $connect->prepare("SELECT {$col_id} FROM {$table} WHERE {$col_numide} = :numide AND {$col_id} != :id");
    $stmtCheck->execute([':numide' => $numide, ':id' => $id_primary]);
    if ($stmtCheck->fetchColumn()) {
        throw new RuntimeException('Ya existe otro colaborador en esta área con el mismo documento.');
    }

    // Obtener información actual
    $stmtCurrent = $connect->prepare("SELECT id_candidate_rrhh FROM {$table} WHERE {$col_id} = ? LIMIT 1");
    $stmtCurrent->execute([$id_primary]);
    $currentRecord = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
    if (!$currentRecord) {
        throw new RuntimeException('El colaborador no existe.');
    }

    $url_contrato = null;
    $url_solicitud = null;
    $url_psicometricas = null;

    if (isset($_FILES['doc_contrato']) && $_FILES['doc_contrato']['error'] === UPLOAD_ERR_OK) {
        $url_contrato = file_get_contents($_FILES['doc_contrato']['tmp_name']);
    }
    if (isset($_FILES['doc_solicitud']) && $_FILES['doc_solicitud']['error'] === UPLOAD_ERR_OK) {
        $url_solicitud = file_get_contents($_FILES['doc_solicitud']['tmp_name']);
    }
    if (isset($_FILES['doc_psicometricas']) && $_FILES['doc_psicometricas']['error'] === UPLOAD_ERR_OK) {
        $url_psicometricas = file_get_contents($_FILES['doc_psicometricas']['tmp_name']);
    }

    // --- INTEGRACIÓN CON MÓDULO RRHH ---
    $pdoRrhh = medidata_rrhh_pdo();
    $id_candidate_rrhh = $currentRecord['id_candidate_rrhh'];

    if ($pdoRrhh) {
        if (!$id_candidate_rrhh) {
            $stmtC = $pdoRrhh->prepare("SELECT id FROM candidates WHERE dni = :dni LIMIT 1");
            $stmtC->execute([':dni' => $numide]);
            $id_candidate_rrhh = $stmtC->fetchColumn();

            if (!$id_candidate_rrhh) {
                $stmtV = $pdoRrhh->query("SELECT id FROM vacant_positions LIMIT 1");
                $id_vacant = $stmtV->fetchColumn() ?: 1;

                $stmtInsC = $pdoRrhh->prepare("
                    INSERT INTO candidates (id_vacant_position, fullname, dni, birthdate, phonenumber, email, direction, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, 'N/D', 'Contratado', ?)
                ");
                $stmtInsC->execute([
                    $id_vacant,
                    $nombres . ' ' . $apellidos,
                    $numide,
                    $nacimiento !== '' ? $nacimiento : null,
                    $telefono,
                    $correo_personal,
                    $_SESSION['name'] ?? 'System'
                ]);
                $id_candidate_rrhh = $pdoRrhh->lastInsertId();
            }
        } else {
            $stmtUpdC = $pdoRrhh->prepare("UPDATE candidates SET fullname = ?, dni = ?, birthdate = ?, phonenumber = ?, email = ? WHERE id = ?");
            $stmtUpdC->execute([
                $nombres . ' ' . $apellidos,
                $numide,
                $nacimiento !== '' ? $nacimiento : null,
                $telefono,
                $correo_personal,
                $id_candidate_rrhh
            ]);
        }

        $uploadDir = __DIR__ . '/../../uploads/staff/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        
        $hr_docs = [
            'doc_birth_cert_children' => 'birth_cert_children',
            'doc_photo_id_card' => 'photo_id_card',
            'doc_id_document' => 'id_document',
            'doc_utility_bill' => 'utility_bill',
            'doc_criminal_record' => 'criminal_record',
            'doc_police_record' => 'police_record',
            'doc_personal_references' => 'personal_references',
            'doc_professional_references' => 'professional_references',
            'doc_diplomas' => 'diplomas',
            'doc_home_sketch' => 'home_sketch'
        ];

        $hr_updates = [];
        $hr_params = [];
        foreach ($hr_docs as $fileInput => $dbCol) {
            if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION);
                $filename = $dbCol . '_' . $numide . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $uploadDir . $filename)) {
                    $hr_updates[] = "$dbCol = ?";
                    $hr_params[] = '/uploads/staff/' . $filename;
                }
            }
        }

        if (!empty($hr_updates)) {
            $stmtHR = $pdoRrhh->prepare("SELECT id FROM hiring_requirements WHERE id_candidate = ?");
            $stmtHR->execute([$id_candidate_rrhh]);
            if (!$stmtHR->fetchColumn()) {
                $pdoRrhh->prepare("INSERT INTO hiring_requirements (id_candidate, created_by) VALUES (?, ?)")
                        ->execute([$id_candidate_rrhh, $_SESSION['name'] ?? 'System']);
            }
            $hr_params[] = $id_candidate_rrhh;
            $pdoRrhh->prepare("UPDATE hiring_requirements SET " . implode(', ', $hr_updates) . " WHERE id_candidate = ?")
                    ->execute($hr_params);
        }
    }

    $updates = [
        'id_user = :id_user', "{$col_numide} = :numide", "{$col_nombres} = :nombres", "{$col_apellidos} = :apellidos",
        "{$col_nac} = :nacimiento", "{$col_sexo} = :sexo", 'num_empleado = :num_empleado',
        'tipo_empleado = :tipo_empleado', 'duracion_contrato = :duracion_contrato',
        'fecha_ingreso = :fecha_ingreso', 'id_departamento = :id_departamento',
        'id_cargo = :id_cargo', 'id_horario = :id_horario', 'id_salary_level = :id_salary_level',
        'salario = :salario', 'cuenta_bac = :cuenta_bac', 'telefono = :telefono',
        'correo_personal = :correo_personal', 'correo_institucional = :correo_institucional',
        'num_locker = :num_locker', 'id_biometrico = :id_biometrico'
    ];
    
    $params = [
        ':id_user' => $idUser,
        ':numide' => $numide,
        ':nombres' => $nombres,
        ':apellidos' => $apellidos,
        ':nacimiento' => $nacimiento,
        ':sexo' => $sexo,
        ':num_empleado' => $num_empleado,
        ':tipo_empleado' => $tipo_empleado,
        ':duracion_contrato' => $duracion_contrato,
        ':fecha_ingreso' => $fecha_ingreso ?: null,
        ':id_departamento' => $id_departamento > 0 ? $id_departamento : null,
        ':id_cargo' => $id_cargo > 0 ? $id_cargo : null,
        ':id_horario' => $id_horario > 0 ? $id_horario : null,
        ':id_salary_level' => $id_salary_level > 0 ? $id_salary_level : null,
        ':salario' => $salario > 0 ? $salario : null,
        ':cuenta_bac' => $cuenta_bac,
        ':telefono' => $telefono,
        ':correo_personal' => $correo_personal,
        ':correo_institucional' => $correo_institucional,
        ':num_locker' => $num_locker,
        ':id_biometrico' => $id_biometrico > 0 ? $id_biometrico : null,
        ':id' => $id_primary
    ];

    if ($table === 'doctor' && $doctorLegacy) {
        $updates[] = 'nomesp = :nomesp';
        $updates[] = 'direcd = :direcd';
        $updates[] = 'phd = :phd';
        $updates[] = 'corr = :corr';
        $updates[] = 'comisiona = :comisiona';
        $params[':nomesp'] = $doctorLegacy['nomesp'];
        $params[':direcd'] = $doctorLegacy['direcd'];
        $params[':phd'] = $doctorLegacy['phd'];
        $params[':corr'] = $doctorLegacy['corr'];
        $params[':comisiona'] = $doctorLegacy['comisiona'];
    }

    if ($id_candidate_rrhh) {
        $updates[] = 'id_candidate_rrhh = :id_candidate_rrhh';
        $params[':id_candidate_rrhh'] = $id_candidate_rrhh;
    }
    if ($url_contrato) {
        $updates[] = 'url_contrato = :url_contrato';
        $params[':url_contrato'] = $url_contrato;
    }
    if ($url_solicitud) {
        $updates[] = 'url_solicitud = :url_solicitud';
        $params[':url_solicitud'] = $url_solicitud;
    }
    if ($url_psicometricas) {
        $updates[] = 'url_psicometricas = :url_psicometricas';
        $params[':url_psicometricas'] = $url_psicometricas;
    }

    $sql = "UPDATE {$table} SET " . implode(', ', $updates) . " WHERE {$col_id} = :id LIMIT 1";
    
    $stmt = $connect->prepare($sql);
    $ok = $stmt->execute($params);

    if ($ok) {
        $returnPage = medidata_staff_return_page($_POST, 'lista_colaboradores.php');
        echo '<script>Swal.fire("Actualizado", "Colaborador actualizado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo actualizar", "error");</script>';
    }
    exit;
} catch (Throwable $e) {
    error_log('upd_colaborador_unificado: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
    exit;
}
