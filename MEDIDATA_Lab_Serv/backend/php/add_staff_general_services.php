<?php
if (!isset($_POST['add_staff_general_services'])) {
    return;
}

require_once __DIR__ . '/staff_colaborador_bootstrap.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';

medidata_staff_ensure_tables($connect);

$numide = strtoupper(trim((string) ($_POST['sgiden'] ?? '')));
$nomadm = strtoupper(trim((string) ($_POST['sgnam'] ?? '')));
$apeadm = strtoupper(trim((string) ($_POST['sgape'] ?? '')));
$nacadm = trim((string) ($_POST['sgdat'] ?? ''));
$sexadm = trim((string) ($_POST['sgge'] ?? ''));
$idUser = medidata_staff_parse_id_user($_POST['sgid_user'] ?? null);

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

if ($numide === '' || $nomadm === '') {
    echo '<script>Swal.fire("Campos requeridos", "Complete identificación y nombre.", "warning");</script>';
    return;
}

try {
    if ($idUser !== null) {
        $linked = medidata_staff_id_user_linked($connect, $idUser, 'staff_general_services');
        if ($linked !== null) {
            echo '<script>Swal.fire("Usuario en uso", "Ese usuario ya está vinculado como colaborador de ' . $linked['label'] . '.", "warning");</script>';
            return;
        }
    }

    $check = $connect->prepare('SELECT COUNT(*) FROM staff_general_services WHERE numide = :numide');
    $check->execute([':numide' => $numide]);
    if ((int) $check->fetchColumn() > 0) {
        echo '<script>Swal.fire("Duplicado", "Ya existe un colaborador con esa identificación.", "warning");</script>';
        return;
    }

    // Autogenerar num_empleado si está vacío
    if ($num_empleado === '') {
        $stmtC = $connect->query("SELECT COUNT(*) FROM staff_general_services");
        $c = $stmtC->fetchColumn() + 1;
        $num_empleado = 'EMP-' . str_pad($c, 3, '0', STR_PAD_LEFT);
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
    $id_candidate_rrhh = null;

    if ($pdoRrhh) {
        // Buscar candidato existente
        $stmtC = $pdoRrhh->prepare("SELECT id FROM candidates WHERE dni = :dni LIMIT 1");
        $stmtC->execute([':dni' => $numide]);
        $id_candidate_rrhh = $stmtC->fetchColumn();

        if (!$id_candidate_rrhh) {
            // Obtener cualquier vacante como dummy para cumplir la llave foránea
            $stmtV = $pdoRrhh->query("SELECT id FROM vacant_positions LIMIT 1");
            $id_vacant = $stmtV->fetchColumn() ?: 1;

            $stmtInsC = $pdoRrhh->prepare("
                INSERT INTO candidates (id_vacant_position, fullname, dni, birthdate, phonenumber, email, direction, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 'N/D', 'Contratado', ?)
            ");
            $stmtInsC->execute([
                $id_vacant,
                $nomadm . ' ' . $apeadm,
                $numide,
                $nacadm !== '' ? $nacadm : null,
                $telefono,
                $correo_personal,
                $_SESSION['name'] ?? 'System'
            ]);
            $id_candidate_rrhh = $pdoRrhh->lastInsertId();
        }

        // Subida de requisitos de contratación
        // Usamos la tabla hiring_requirements para guardar la ruta del archivo
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
            // Asegurar que el registro de requisitos exista
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

    // Insertar en la BD principal
    // (Asegúrate de haber añadido doc_solicitud y doc_psicometricas si se requieren)
    $stmt = $connect->prepare('
        INSERT INTO staff_general_services (
            id_user, numide, nomsg, apesg, nacsg, sexsg, state,
            num_empleado, tipo_empleado, duracion_contrato, fecha_ingreso,
            id_departamento, id_cargo, id_horario, id_salary_level, salario,
            cuenta_bac, telefono, correo_personal, correo_institucional,
            num_locker, id_biometrico, url_contrato, url_solicitud, url_psicometricas, id_candidate_rrhh
        ) VALUES (
            :id_user, :numide, :nomadm, :apeadm, :nacadm, :sexadm, \'1\',
            :num_empleado, :tipo_empleado, :duracion_contrato, :fecha_ingreso,
            :id_departamento, :id_cargo, :id_horario, :id_salary_level, :salario,
            :cuenta_bac, :telefono, :correo_personal, :correo_institucional,
            :num_locker, :id_biometrico, :url_contrato, :url_solicitud, :url_psicometricas, :id_candidate_rrhh
        )
    ');
    
    $ok = $stmt->execute([
        ':id_user' => $idUser,
        ':numide' => $numide,
        ':nomadm' => $nomadm,
        ':apeadm' => $apeadm,
        ':nacadm' => $nacadm,
        ':sexadm' => $sexadm,
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
        ':url_contrato' => $url_contrato,
        ':url_solicitud' => $url_solicitud,
        ':url_psicometricas' => $url_psicometricas,
        ':id_candidate_rrhh' => $id_candidate_rrhh
    ]);

    if ($ok) {
        $returnPage = medidata_staff_return_page($_POST, 'servicios_generales.php');
        echo '<script>Swal.fire("Agregado", "Colaborador de servicios generales registrado correctamente", "success").then(function(){ window.location=' . json_encode($returnPage, JSON_UNESCAPED_UNICODE) . '; });</script>';
    } else {
        echo '<script>Swal.fire("Error", "No se pudo registrar el colaborador", "error");</script>';
    }
} catch (Throwable $e) {
    error_log('add_staff_general_services: ' . $e->getMessage());
    echo '<script>Swal.fire("Error", ' . json_encode($e->getMessage(), JSON_UNESCAPED_UNICODE) . ', "error");</script>';
}
