<?php
/**
 * Datos de demostración — flujo RRHH completo (web → contratado).
 * Ejecutar en servidor: php backend/scripts/seed_rrhh_demo_flujo_completo.php
 * Idempotente: puede ejecutarse varias veces.
 */

if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'medidata.medicasa.hn';
}

require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';

const DEMO_USER = 'demo_rrhh';
const DEMO_POSITION = 'Auxiliar de Enfermería II';
const DEMO_VACANT_NAME = '[DEMO] Vacante Auxiliar Enfermería 2026';

const DEMO_CANDIDATOS = [
    'maria' => [
        'dni' => '0801199009999',
        'dni_web' => '0801-1990-09999',
        'fullname' => '[DEMO] María Elena Rivera Soto',
        'email' => 'demo.maria.rivera@medicasa.hn',
        'phone' => '99990001',
        'status' => 'Contratado',
        'puesto_web' => 'Auxiliar de Enfermería II',
        'aplica_estado' => 'Incorporado',
        'birthdate' => '1990-03-15',
        'direction' => 'Col. Las Colinas, Tegucigalpa',
        'academic_level' => 'Técnico en Enfermería',
        'profession' => 'Auxiliar de Enfermería',
        'previous_experience' => '2 años en hospital privado — área de hospitalización.',
        'salary_expectation' => 18500.00,
        'overall_score' => 92.50,
        'psychometric_result' => 'Perfil compatible — estabilidad emocional alta.',
        'interview_result' => 'Aprobada — comunicación clara y experiencia verificada.',
        'rrhh_observations' => 'Candidata demo: proceso completo finalizado. Lista para PDF de capacitación.',
    ],
    'carlos' => [
        'dni' => '0801199009998',
        'dni_web' => '0801-1995-09998',
        'fullname' => '[DEMO] Carlos Alberto Mejía Cruz',
        'email' => 'demo.carlos.mejia@medicasa.hn',
        'phone' => '99990002',
        'status' => 'En Espera',
        'puesto_web' => 'Auxiliar de Enfermería II',
        'aplica_estado' => 'Incorporado',
        'birthdate' => '1995-07-22',
        'direction' => 'Comayagüela, F.M.',
        'academic_level' => 'Bachillerato',
        'profession' => 'Auxiliar de Enfermería',
        'previous_experience' => 'Prácticas en clínica comunitaria.',
        'salary_expectation' => 16000.00,
        'overall_score' => null,
        'psychometric_result' => null,
        'interview_result' => null,
        'rrhh_observations' => 'Recién incorporado desde web — pendiente revisión inicial.',
    ],
    'ana' => [
        'dni' => '0801199009997',
        'dni_web' => '0801-1998-09997',
        'fullname' => '[DEMO] Ana Lucía Paz Mendoza',
        'email' => 'demo.ana.paz@medicasa.hn',
        'phone' => '99990003',
        'status' => 'Entrevista',
        'puesto_web' => 'Auxiliar de Enfermería II',
        'aplica_estado' => 'Incorporado',
        'birthdate' => '1998-11-08',
        'direction' => 'Tegucigalpa, M.D.C.',
        'academic_level' => 'Técnico en Enfermería',
        'profession' => 'Auxiliar de Enfermería',
        'previous_experience' => '1 año en urgencias.',
        'salary_expectation' => 17000.00,
        'overall_score' => null,
        'psychometric_result' => null,
        'interview_result' => 'En evaluación',
        'rrhh_observations' => 'Entrevista programada — etapa activa del demo.',
        'interview_date' => '+3 days',
        'interview_time' => '10:30:00',
    ],
    'luis' => [
        'dni' => '0801199009996',
        'dni_web' => '0801-1993-09996',
        'fullname' => '[DEMO] Luis Fernando Ortiz Vega',
        'email' => 'demo.luis.ortiz@medicasa.hn',
        'phone' => '99990004',
        'status' => 'Pruebas Psicometricas',
        'puesto_web' => 'Auxiliar de Enfermería II',
        'aplica_estado' => 'Incorporado',
        'birthdate' => '1993-04-12',
        'direction' => 'Valle de Ángeles, F.M.',
        'academic_level' => 'Técnico en Enfermería',
        'profession' => 'Auxiliar de Enfermería',
        'previous_experience' => '3 años en área quirúrgica.',
        'salary_expectation' => 19000.00,
        'overall_score' => 78.00,
        'psychometric_result' => 'En proceso — 16PF aplicado.',
        'interview_result' => 'Aprobado en entrevista técnica.',
        'rrhh_observations' => 'Pendiente cierre de prueba psicométrica.',
    ],
    'rosa' => [
        'dni' => '0801199009995',
        'dni_web' => '0801-1991-09995',
        'fullname' => '[DEMO] Rosa Isabel Castillo Núñez',
        'email' => 'demo.rosa.castillo@medicasa.hn',
        'phone' => '99990005',
        'status' => 'Llenando Expediente',
        'puesto_web' => 'Auxiliar de Enfermería II',
        'aplica_estado' => 'Incorporado',
        'birthdate' => '1991-09-30',
        'direction' => 'Tegucigalpa, M.D.C.',
        'academic_level' => 'Licenciatura en Enfermería',
        'profession' => 'Enfermera',
        'previous_experience' => '4 años en medicina interna.',
        'salary_expectation' => 22000.00,
        'overall_score' => 88.00,
        'psychometric_result' => 'Apto — perfil analítico.',
        'interview_result' => 'Excelente desempeño en entrevista.',
        'rrhh_observations' => 'Recopilando documentos de contratación.',
    ],
];

function demo_log(string $msg): void
{
    echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL;
}

function demo_ensure_position(PDO $main): int
{
    $stmt = $main->prepare('SELECT id FROM positions WHERE name = ? LIMIT 1');
    $stmt->execute([DEMO_POSITION]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return $id;
    }
    $ins = $main->prepare('INSERT INTO positions (name, state, created_by) VALUES (?, 1, ?)');
    $ins->execute([DEMO_POSITION, DEMO_USER]);
    return (int) $main->lastInsertId();
}

function demo_ensure_puesto(PDO $rrhh, int $positionId): int
{
    $stmt = $rrhh->prepare(
        'SELECT id FROM positions_details WHERE id_positions = ? AND department LIKE ? AND deleted = 0 LIMIT 1'
    );
    $stmt->execute([$positionId, '%DEMO%']);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return $id;
    }

    $sql = "INSERT INTO positions_details (
        id_positions, department, immediate_boss, objective, main_functions,
        academic_requirements, required_experience, technical_competencies,
        soft_competencies, schedule, shift_type, salary_range,
        suggested_psychometric_tests, created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $rrhh->prepare($sql)->execute([
        $positionId,
        'Enfermería [DEMO]',
        'Jefe de Enfermería',
        'Brindar cuidados básicos al paciente bajo supervisión.',
        "Signos vitales\nAseo del paciente\nApoyo en procedimientos básicos",
        'Técnico en Enfermería o estudiante avanzado',
        'Mínimo 6 meses en área clínica',
        'Toma de signos vitales, bioseguridad',
        'Empatía, trabajo en equipo, puntualidad',
        'Rotativo 24/7',
        'Mixto',
        'L 15,000 — L 20,000',
        '16PF, Cleaver',
        DEMO_USER,
    ]);
    return (int) $rrhh->lastInsertId();
}

function demo_ensure_vacante(PDO $rrhh, int $puestoId): int
{
    $stmt = $rrhh->prepare('SELECT id FROM vacant_positions WHERE vacant_name = ? AND deleted = 0 LIMIT 1');
    $stmt->execute([DEMO_VACANT_NAME]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return $id;
    }

    $init = date('Y-m-d', strtotime('-30 days'));
    $end = date('Y-m-d', strtotime('+90 days'));

    $sql = "INSERT INTO vacant_positions (
        id_position, vacant_name, requesting_department, available_slots, reason,
        priority, status, publication_channel, benefits, init_date, end_date, created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

    $rrhh->prepare($sql)->execute([
        $puestoId,
        DEMO_VACANT_NAME,
        'Enfermería',
        2,
        'Cobertura por incremento de demanda — demo capacitación RRHH.',
        'Alta',
        'Abierta',
        'Sitio Web',
        'Plan médico, aguinaldo, capacitación interna',
        $init,
        $end,
        DEMO_USER,
    ]);
    return (int) $rrhh->lastInsertId();
}

function demo_ensure_aplica(PDO $post, array $c): int
{
    medidata_rrhh_aplica_bridge_ensure_schema();

    $stmt = $post->prepare('SELECT id FROM aplica WHERE numero_id = ? LIMIT 1');
    $stmt->execute([$c['dni_web']]);
    $id = (int) $stmt->fetchColumn();

    $fecha = date('Y-m-d H:i:s', strtotime('-14 days'));

    if ($id > 0) {
        $post->prepare(
            "UPDATE aplica SET
                nombre_completo = ?, puesto_aspirado = ?, correo = ?, whatsapp = ?,
                estado_rrhh = ?, seleccionado = ?
             WHERE id = ?"
        )->execute([
            $c['fullname'],
            $c['puesto_web'],
            $c['email'],
            $c['phone'],
            $c['aplica_estado'],
            $c['aplica_estado'] === 'Incorporado' ? 1 : 0,
            $id,
        ]);
        return $id;
    }

    $post->prepare(
        "INSERT INTO aplica (
            nombre_completo, numero_id, puesto_aspirado, correo, whatsapp,
            fecha_registro, seleccionado, estado_rrhh, ip_address
        ) VALUES (?,?,?,?,?,?,?,?,?)"
    )->execute([
        $c['fullname'],
        $c['dni_web'],
        $c['puesto_web'],
        $c['email'],
        $c['phone'],
        $fecha,
        $c['aplica_estado'] === 'Incorporado' ? 1 : 0,
        $c['aplica_estado'],
        '127.0.0.1',
    ]);
    return (int) $post->lastInsertId();
}

function demo_ensure_candidate(PDO $rrhh, int $vacanteId, int $aplicaId, array $c): int
{
    medidata_rrhh_aplica_bridge_ensure_schema();

    $stmt = $rrhh->prepare('SELECT id FROM candidates WHERE dni = ? AND id_vacant_position = ? AND deleted = 0 LIMIT 1');
    $stmt->execute([$c['dni'], $vacanteId]);
    $id = (int) $stmt->fetchColumn();

    $createdAt = date('Y-m-d H:i:s', strtotime('-12 days'));

    if ($id > 0) {
        $sql = 'UPDATE candidates SET
            id_aplica = ?, fullname = ?, birthdate = ?, phonenumber = ?, email = ?, direction = ?,
            academic_level = ?, profession = ?, previous_experience = ?, salary_expectation = ?,
            status = ?, interview_result = ?, psychometric_result = ?, overall_score = ?,
            rrhh_observations = ?, assigned_interviewer = ?, referral_source = ?, updated_by = ?
            WHERE id = ?';
        $rrhh->prepare($sql)->execute([
            $aplicaId,
            $c['fullname'],
            $c['birthdate'],
            $c['phone'],
            $c['email'],
            $c['direction'],
            $c['academic_level'],
            $c['profession'],
            $c['previous_experience'],
            $c['salary_expectation'],
            $c['status'],
            $c['interview_result'],
            $c['psychometric_result'],
            $c['overall_score'],
            $c['rrhh_observations'],
            'Coordinación RRHH',
            'Sitio Web',
            DEMO_USER,
            $id,
        ]);
    } else {
        $sql = "INSERT INTO candidates (
            id_vacant_position, id_aplica, fullname, dni, birthdate, phonenumber, email, direction,
            academic_level, profession, previous_experience, salary_expectation,
            referral_source, status, interview_result, psychometric_result, overall_score,
            rrhh_observations, assigned_interviewer, created_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $rrhh->prepare($sql)->execute([
            $vacanteId,
            $aplicaId,
            $c['fullname'],
            $c['dni'],
            $c['birthdate'],
            $c['phone'],
            $c['email'],
            $c['direction'],
            $c['academic_level'],
            $c['profession'],
            $c['previous_experience'],
            $c['salary_expectation'],
            'Sitio Web',
            $c['status'],
            $c['interview_result'],
            $c['psychometric_result'],
            $c['overall_score'],
            $c['rrhh_observations'],
            'Coordinación RRHH',
            DEMO_USER,
            $createdAt,
        ]);
        $id = (int) $rrhh->lastInsertId();
    }

    return $id;
}

function demo_link_aplica(PDO $post, int $aplicaId, int $candidateId): void
{
    $post->prepare(
        "UPDATE aplica SET estado_rrhh = 'Incorporado', seleccionado = 1,
         id_candidate_rrhh = ?, fecha_gestion_rrhh = NOW() WHERE id = ?"
    )->execute([$candidateId, $aplicaId]);
}

function demo_ensure_interview(PDO $rrhh, int $candidateId, array $c): void
{
    if (empty($c['interview_date'])) {
        return;
    }

    $date = date('Y-m-d', strtotime($c['interview_date']));
    $time = $c['interview_time'] ?? '09:00:00';
    $status = ($c['status'] === 'Contratado') ? 'Terminada' : 'Programada';

    $stmt = $rrhh->prepare('SELECT id FROM interviews WHERE id_candidate = ? AND deleted = 0 LIMIT 1');
    $stmt->execute([$candidateId]);
    $id = (int) $stmt->fetchColumn();

    if ($id > 0) {
        $rrhh->prepare('UPDATE interviews SET date_interview = ?, time_interview = ?, status = ?, updated_by = ? WHERE id = ?')
            ->execute([$date, $time, $status, DEMO_USER, $id]);
        return;
    }

    $rrhh->prepare(
        'INSERT INTO interviews (id_candidate, date_interview, time_interview, status, created_by) VALUES (?,?,?,?,?)'
    )->execute([$candidateId, $date, $time, $status, DEMO_USER]);
}

function demo_ensure_psychometric(PDO $rrhh, int $candidateId, array $c): void
{
    if ($c['status'] !== 'Contratado' && $c['status'] !== 'Pruebas Psicometricas') {
        return;
    }

    $stmt = $rrhh->prepare('SELECT id FROM psychometric_question_form WHERE id_candidate = ? AND deleted = 0 LIMIT 1');
    $stmt->execute([$candidateId]);
    $id = (int) $stmt->fetchColumn();

    $score = $c['overall_score'];
    $status = ($c['status'] === 'Pruebas Psicometricas') ? 'En Proceso' : 'Completado';
    $payload = json_encode(['demo' => true, 'nota' => 'Evaluación demo 16PF'], JSON_UNESCAPED_UNICODE);

    if ($id > 0) {
        $rrhh->prepare('UPDATE psychometric_question_form SET score = ?, status = ?, payload = ?, updated_by = ? WHERE id = ?')
            ->execute([$score, $status, $payload, DEMO_USER, $id]);
        return;
    }

    $rrhh->prepare(
        'INSERT INTO psychometric_question_form (id_candidate, score, payload, status, created_by) VALUES (?,?,?,?,?)'
    )->execute([$candidateId, $score, $payload, $status, DEMO_USER]);
}

function demo_ensure_hiring(PDO $rrhh, int $candidateId, array $c): void
{
    if (!in_array($c['status'], ['Llenando Expediente', 'Contratado'], true)) {
        return;
    }

    $stmt = $rrhh->prepare('SELECT id FROM hiring_requirements WHERE id_candidate = ? AND deleted = 0 LIMIT 1');
    $stmt->execute([$candidateId]);
    $id = (int) $stmt->fetchColumn();

    $hrStatus = ($c['status'] === 'Contratado') ? 'Completado' : 'En Revisión';

    if ($id > 0) {
        $rrhh->prepare('UPDATE hiring_requirements SET status = ?, updated_by = ? WHERE id = ?')
            ->execute([$hrStatus, DEMO_USER, $id]);
        return;
    }

    $rrhh->prepare(
        'INSERT INTO hiring_requirements (id_candidate, status, created_by) VALUES (?,?,?)'
    )->execute([$candidateId, $hrStatus, DEMO_USER]);
}

function demo_ensure_employee_form(PDO $rrhh, int $candidateId): void
{
    $stmt = $rrhh->prepare('SELECT id FROM employees_form WHERE id_candidate = ? AND deleted = 0 LIMIT 1');
    $stmt->execute([$candidateId]);
    $id = (int) $stmt->fetchColumn();

    $payload = json_encode([
        'demo' => true,
        'dependientes' => 1,
        'contacto_emergencia' => 'Juan Rivera — 9999-0000',
        'cuenta_banco' => 'Demo — no usar en producción',
    ], JSON_UNESCAPED_UNICODE);

    if ($id > 0) {
        $rrhh->prepare("UPDATE employees_form SET payload = ?, status = 'Revisado', updated_by = ? WHERE id = ?")
            ->execute([$payload, DEMO_USER, $id]);
        return;
    }

    $rrhh->prepare(
        "INSERT INTO employees_form (id_candidate, payload, status, created_by) VALUES (?,?,'Revisado',?)"
    )->execute([$candidateId, $payload, DEMO_USER]);
}

// --- Main ---
demo_log('Iniciando seed demo flujo RRHH...');

if (!$connect instanceof PDO) {
    demo_log('ERROR: sin conexión medic9ue_medi_data');
    exit(1);
}
if (!$connect_rrhh instanceof PDO) {
    demo_log('ERROR: sin conexión medic9ue_medi_rrhh_interviews');
    exit(1);
}
if (!$connect_postulaciones instanceof PDO) {
    demo_log('ERROR: sin conexión medic9ue_postulaciones');
    exit(1);
}

try {
    $positionId = demo_ensure_position($connect);
    demo_log("Posición catálogo ID {$positionId}: " . DEMO_POSITION);

    $puestoId = demo_ensure_puesto($connect_rrhh, $positionId);
    demo_log("Puesto detalle ID {$puestoId}");

    $vacanteId = demo_ensure_vacante($connect_rrhh, $puestoId);
    demo_log("Vacante ID {$vacanteId}: " . DEMO_VACANT_NAME);

    $candidateIds = [];

    foreach (DEMO_CANDIDATOS as $key => $c) {
        try {
            $aplicaId = demo_ensure_aplica($connect_postulaciones, $c);
            $candidateId = demo_ensure_candidate($connect_rrhh, $vacanteId, $aplicaId, $c);
            demo_link_aplica($connect_postulaciones, $aplicaId, $candidateId);
            demo_ensure_interview($connect_rrhh, $candidateId, $c);
            demo_ensure_psychometric($connect_rrhh, $candidateId, $c);
            demo_ensure_hiring($connect_rrhh, $candidateId, $c);
            if ($c['status'] === 'Contratado') {
                demo_ensure_employee_form($connect_rrhh, $candidateId);
            }
            $candidateIds[$key] = $candidateId;
            demo_log("Candidato [{$key}] ID {$candidateId} — {$c['status']} — {$c['fullname']}");
        } catch (Throwable $e) {
            throw new RuntimeException("[{$key}] " . $e->getMessage(), 0, $e);
        }
    }

    demo_log('');
    demo_log('=== DEMO LISTO ===');
    demo_log('Vacante filtro ID: ' . $vacanteId);
    demo_log('María (proceso COMPLETO / Contratado): candidato #' . $candidateIds['maria']);
    demo_log('Carlos (En Espera): candidato #' . $candidateIds['carlos']);
    demo_log('Ana (Entrevista): candidato #' . $candidateIds['ana']);
    demo_log('');
    demo_log('Abra: frontend/recursos_humanos/demo_flujo_proceso_usr.php');
    demo_log('O ejecute desde navegador con sesión RRHH activa.');

    file_put_contents(
        __DIR__ . '/.demo_flujo_ids.json',
        json_encode([
            'vacante_id' => $vacanteId,
            'candidates' => $candidateIds,
            'seeded_at' => date('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
} catch (Throwable $e) {
    demo_log('ERROR: ' . $e->getMessage());
    exit(1);
}
