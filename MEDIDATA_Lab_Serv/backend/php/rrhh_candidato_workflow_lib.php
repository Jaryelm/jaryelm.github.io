<?php
/**
 * Flujo de candidato RRHH: entrevista, psicométricas, expediente y correos.
 */

require_once __DIR__ . '/../registros/rrhh_guard.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';
require_once __DIR__ . '/rrhh_employee_form_lib.php';

if (!function_exists('medidata_rrhh_psychometric_tests_catalog')) {
    /** @return array<string, string> */
    function medidata_rrhh_psychometric_tests_catalog(): array
    {
        return [
            'Inteligencia_OTIS' => 'Test de Inteligencia OTIS',
            'Inteligencia_WONDERLIC_PHM' => 'Test de Inteligencia WONDERLIC PHM',
            'Inteligencia_BARSIT' => 'Test de Inteligencia BARSIT',
            'Instrucciones_Complejas' => 'Test de Instrucciones Complejas',
            'IPV_Ventas' => 'Test IPV Ventas',
            'Personalidad_Servicio_Cliente_IPER' => 'Test de Personalidad para Servicio al Cliente IPER',
            'VRP' => 'Test VRP',
            'Colores' => 'Test de Colores',
            'Estilos_Direccion_LEAD' => 'Test de Estilos de Direccion LEAD',
            'Intereses_Valores_IVAL' => 'Test de Intereses y Valores IVAL',
            'Habilidades_Juicio_MOSS' => 'Test Habilidades de Juicio social y Supervision MOSS',
            'Rasgos_Conductuales_DISC' => 'Test de Rasgos Conductuales DISC',
            'Estilo_Manejo_Conflictos' => 'Test Estilo de Manejo de conflictos',
            'Inteligencia_Emocional' => 'Test de Inteligencia Emocional',
        ];
    }
}

if (!function_exists('medidata_rrhh_expediente_documentos')) {
    /** @return array<string, array{label:string, column:string}> */
    function medidata_rrhh_expediente_documentos(): array
    {
        return [
            'curriculum_vitae' => ['label' => 'Curriculum vitae', 'column' => 'curriculum_vitae'],
            'birth_cert_children' => ['label' => 'Copia de partida de nacimiento de hijos', 'column' => 'birth_cert_children'],
            'photo_id_card' => ['label' => '(1) Foto a color (tamaño carnet)', 'column' => 'photo_id_card'],
            'id_document' => ['label' => '(2) Copia de tarjeta de identidad / licencia', 'column' => 'id_document'],
            'utility_bill' => ['label' => '(1) Copia del recibo (agua, luz, teléfono) más reciente', 'column' => 'utility_bill'],
            'criminal_record' => ['label' => 'Antecedentes penales', 'column' => 'criminal_record'],
            'police_record' => ['label' => 'Antecedentes policiales', 'column' => 'police_record'],
            'personal_references' => ['label' => '(2) Referencias personales', 'column' => 'personal_references'],
            'professional_references' => ['label' => '(2) Referencias laborales', 'column' => 'professional_references'],
            'diplomas' => ['label' => 'Diplomas o títulos recibidos', 'column' => 'diplomas'],
            'home_sketch' => ['label' => 'Croquis de vivienda', 'column' => 'home_sketch'],
        ];
    }
}

if (!function_exists('medidata_rrhh_workflow_ensure_schema')) {
    function medidata_rrhh_workflow_ensure_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            medidata_rrhh_ensure_employee_form_schema($pdo);

            $cols = $pdo->query('SHOW COLUMNS FROM candidates')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('expediente_access_token', $cols, true)) {
                $pdo->exec(
                    'ALTER TABLE candidates ADD COLUMN expediente_access_token VARCHAR(64) NULL DEFAULT NULL'
                );
            }
            $idxExp = $pdo->query(
                "SHOW INDEX FROM candidates WHERE Key_name = 'idx_candidates_expediente_token'"
            )->fetch(PDO::FETCH_ASSOC);
            if (!$idxExp) {
                $pdo->exec(
                    'ALTER TABLE candidates ADD UNIQUE KEY idx_candidates_expediente_token (expediente_access_token)'
                );
            }

            $hrCols = $pdo->query('SHOW COLUMNS FROM hiring_requirements')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('curriculum_vitae', $hrCols, true)) {
                $pdo->exec('ALTER TABLE hiring_requirements ADD COLUMN curriculum_vitae MEDIUMBLOB DEFAULT NULL');
            }

            foreach (medidata_rrhh_expediente_documentos() as $doc) {
                $col = $doc['column'];
                if ($col === 'curriculum_vitae') {
                    continue;
                }
                $info = $pdo->query("SHOW COLUMNS FROM hiring_requirements LIKE " . $pdo->quote($col))->fetch(PDO::FETCH_ASSOC);
                if ($info && stripos((string) ($info['Type'] ?? ''), 'blob') !== false && stripos((string) $info['Type'], 'tiny') !== false) {
                    $pdo->exec("ALTER TABLE hiring_requirements MODIFY COLUMN `{$col}` MEDIUMBLOB DEFAULT NULL");
                }
            }
        } catch (Throwable $e) {
            error_log('medidata_rrhh_workflow_ensure_schema: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_rrhh_send_notification_email')) {
    /** @return array{success:bool, message:string} */
    function medidata_rrhh_send_notification_email(string $toEmail, string $subject, string $bodyHtml, string $bodyText, string $recipientName = ''): array
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Correo del destinatario no válido.'];
        }

        require_once __DIR__ . '/../vendor/phpmailer/autoload.php';
        require_once __DIR__ . '/medidata_mailer_config.php';

        $cfg = medidata_mailer_config();
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $cfg['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $cfg['username'];
            $mail->Password = $cfg['password'];
            $secure = strtolower((string) ($cfg['secure'] ?? 'tls'));
            $mail->SMTPSecure = ($secure === 'ssl')
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int) $cfg['port'];
            $mail->CharSet = 'UTF-8';
            $mail->SMTPDebug = (int) ($cfg['debug_level'] ?? 0);

            $mail->setFrom($cfg['from_email'], $cfg['from_name']);
            $mail->addReplyTo($cfg['reply_to_email'], $cfg['reply_to_name']);
            $mail->addAddress($toEmail, $recipientName !== '' ? $recipientName : $toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText;
            $mail->send();

            return ['success' => true, 'message' => 'Correo enviado a ' . $toEmail . '.'];
        } catch (Throwable $e) {
            error_log('medidata_rrhh_send_notification_email: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo enviar el correo. Copie el enlace manualmente.'];
        }
    }
}

if (!function_exists('medidata_rrhh_expediente_public_path')) {
    function medidata_rrhh_expediente_public_path(string $token): string
    {
        return '/frontend/recursos_humanos/expediente_candidato.php?token=' . rawurlencode($token);
    }
}

if (!function_exists('medidata_rrhh_expediente_public_url')) {
    function medidata_rrhh_expediente_public_url(string $token): string
    {
        return medidata_absolute_url(medidata_rrhh_expediente_public_path($token));
    }
}

if (!function_exists('medidata_rrhh_hiring_requirements_ensure_row')) {
    function medidata_rrhh_hiring_requirements_ensure_row(PDO $pdo, int $candidateId, string $createdBy): int
    {
        $stmt = $pdo->prepare(
            'SELECT id FROM hiring_requirements WHERE id_candidate = ? AND deleted = 0 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$candidateId]);
        $id = (int) $stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }

        $pdo->prepare(
            "INSERT INTO hiring_requirements (id_candidate, status, created_by) VALUES (?, 'Incompleto', ?)"
        )->execute([$candidateId, $createdBy]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('medidata_rrhh_expediente_issue_link')) {
    /** @return array{success:bool, message?:string, url?:string, email?:string, candidate_name?:string} */
    function medidata_rrhh_expediente_issue_link(int $candidateId, string $usuario, bool $updateStatus = true): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return ['success' => false, 'message' => 'RRHH no disponible o candidato inválido.'];
        }

        medidata_rrhh_workflow_ensure_schema($pdo);

        $stmt = $pdo->prepare(
            'SELECT id, fullname, email, expediente_access_token, status FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1'
        );
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['success' => false, 'message' => 'Candidato no encontrado.'];
        }

        $token = trim((string) ($row['expediente_access_token'] ?? ''));
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $pdo->prepare(
                'UPDATE candidates SET expediente_access_token = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$token, $usuario, $candidateId]);
        }

        medidata_rrhh_hiring_requirements_ensure_row($pdo, $candidateId, $usuario);

        if ($updateStatus && ($row['status'] ?? '') !== 'Llenando Expediente') {
            medidata_rrhh_cambiar_estado_candidato(
                $candidateId,
                'Llenando Expediente',
                $usuario,
                'Enlace de expediente de contratación generado.'
            );
        }

        return [
            'success' => true,
            'url' => medidata_rrhh_expediente_public_url($token),
            'email' => trim((string) ($row['email'] ?? '')),
            'candidate_name' => (string) ($row['fullname'] ?? ''),
        ];
    }
}

if (!function_exists('medidata_rrhh_expediente_by_token')) {
    /** @return array<string, mixed>|null */
    function medidata_rrhh_expediente_by_token(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 16) {
            return null;
        }

        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return null;
        }

        medidata_rrhh_workflow_ensure_schema($pdo);

        $stmt = $pdo->prepare(
            "SELECT c.id AS candidate_id, c.fullname, c.email, c.status AS candidate_status,
                    hr.id AS hr_id, hr.status AS hr_status
             FROM candidates c
             LEFT JOIN hiring_requirements hr ON hr.id_candidate = c.id AND hr.deleted = 0
             WHERE c.expediente_access_token = ? AND c.deleted = 0
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}

if (!function_exists('medidata_rrhh_expediente_estado')) {
    /**
     * @return array{completed:array<int, array{key:string,label:string}>, missing:array<int, array{key:string,label:string}>, total:int, done:int}
     */
    function medidata_rrhh_expediente_estado(int $candidateId): array
    {
        $pdo = medidata_rrhh_pdo();
        $docs = medidata_rrhh_expediente_documentos();
        $completed = [];
        $missing = [];

        if (!$pdo || $candidateId <= 0) {
            foreach ($docs as $key => $doc) {
                $missing[] = ['key' => $key, 'label' => $doc['label']];
            }
            return ['completed' => [], 'missing' => $missing, 'total' => count($docs), 'done' => 0];
        }

        medidata_rrhh_workflow_ensure_schema($pdo);
        medidata_rrhh_hiring_requirements_ensure_row($pdo, $candidateId, 'sistema');

        $cols = array_map(static fn($d) => $d['column'], $docs);
        $sql = 'SELECT ' . implode(', ', array_map(static fn($c) => "`{$c}`", $cols))
            . ' FROM hiring_requirements WHERE id_candidate = ? AND deleted = 0 ORDER BY id DESC LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        foreach ($docs as $key => $doc) {
            $val = $row[$doc['column']] ?? null;
            $item = ['key' => $key, 'label' => $doc['label']];
            if ($val !== null && $val !== '') {
                $completed[] = $item;
            } else {
                $missing[] = $item;
            }
        }

        return [
            'completed' => $completed,
            'missing' => $missing,
            'total' => count($docs),
            'done' => count($completed),
        ];
    }
}

if (!function_exists('medidata_rrhh_expediente_upload_pdf')) {
    /** @return array{success:bool, message:string} */
    function medidata_rrhh_expediente_upload_pdf(string $token, string $docKey, array $file): array
    {
        $ctx = medidata_rrhh_expediente_by_token($token);
        if (!$ctx) {
            return ['success' => false, 'message' => 'Enlace no válido o expirado.'];
        }

        $docs = medidata_rrhh_expediente_documentos();
        if (!isset($docs[$docKey])) {
            return ['success' => false, 'message' => 'Documento no reconocido.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Seleccione un archivo PDF válido.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = '';
        if ($tmp !== '' && is_uploaded_file($tmp)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
            if ($finfo) {
                finfo_close($finfo);
            }
        }

        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($mime !== 'application/pdf' && $ext !== 'pdf') {
            return ['success' => false, 'message' => 'Solo se permiten archivos PDF.'];
        }

        $content = file_get_contents($tmp);
        if ($content === false || strlen($content) < 10) {
            return ['success' => false, 'message' => 'No se pudo leer el archivo.'];
        }

        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Servicio no disponible.'];
        }

        $candidateId = (int) $ctx['candidate_id'];
        medidata_rrhh_hiring_requirements_ensure_row($pdo, $candidateId, 'expediente_publico');
        $col = $docs[$docKey]['column'];

        $pdo->prepare(
            "UPDATE hiring_requirements SET `{$col}` = ?, updated_by = 'expediente_publico', updated_at = NOW() WHERE id_candidate = ? AND deleted = 0"
        )->execute([$content, $candidateId]);

        $estado = medidata_rrhh_expediente_estado($candidateId);
        $hrStatus = ($estado['done'] >= $estado['total']) ? 'Completado' : 'En Revisión';
        $pdo->prepare(
            'UPDATE hiring_requirements SET status = ? WHERE id_candidate = ? AND deleted = 0'
        )->execute([$hrStatus, $candidateId]);

        return ['success' => true, 'message' => 'Documento cargado correctamente.'];
    }
}

if (!function_exists('medidata_rrhh_interview_questions_default')) {
    /** @return array<string, string> */
    function medidata_rrhh_interview_questions_default(): array
    {
        return [
            'motivacion' => 'Motivación para el puesto',
            'experiencia' => 'Experiencia relevante',
            'fortalezas' => 'Fortalezas',
            'debilidades' => 'Áreas de mejora',
            'expectativa_salarial' => 'Expectativa salarial',
            'disponibilidad' => 'Disponibilidad de ingreso',
            'observaciones' => 'Observaciones del entrevistador',
            'resultado' => 'Resultado (Apto / No apto / Pendiente)',
        ];
    }
}

if (!function_exists('medidata_rrhh_fetch_interview_form')) {
    /** @return array<string, mixed>|null */
    function medidata_rrhh_fetch_interview_form(int $candidateId): ?array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare(
            "SELECT rf.id, rf.payload, rf.status, rf.id_interview,
                    i.date_interview, i.time_interview, i.status AS interview_status
             FROM rrhh_questions_form rf
             LEFT JOIN interviews i ON i.id = rf.id_interview AND i.deleted = 0
             WHERE rf.id_candidate = ? AND rf.deleted = 0
             ORDER BY rf.id DESC LIMIT 1"
        );
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}

if (!function_exists('medidata_rrhh_save_interview_form')) {
    /**
     * @param array<string, mixed> $answers
     * @return array{success:bool, message:string, interview_id?:int}
     */
    function medidata_rrhh_save_interview_form(int $candidateId, array $answers, string $usuario, ?string $dateInterview = null, ?string $timeInterview = null): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return ['success' => false, 'message' => 'RRHH no disponible.'];
        }

        $dateInterview = trim((string) $dateInterview);
        $timeInterview = trim((string) $timeInterview);
        if ($dateInterview === '' || $timeInterview === '') {
            return ['success' => false, 'message' => 'Indique fecha y hora de la entrevista.'];
        }

        $interviewId = 0;
        $stmtIv = $pdo->prepare(
            'SELECT id FROM interviews WHERE id_candidate = ? AND deleted = 0 ORDER BY id DESC LIMIT 1'
        );
        $stmtIv->execute([$candidateId]);
        $interviewId = (int) $stmtIv->fetchColumn();

        $status = 'Programada';
        if (!empty($answers['resultado'])) {
            $res = strtolower((string) $answers['resultado']);
            if (strpos($res, 'termin') !== false || strpos($res, 'apto') !== false) {
                $status = 'Terminada';
            } elseif (strpos($res, 'proceso') !== false) {
                $status = 'En Proceso';
            }
        }

        $userId = (int) ($_SESSION['id'] ?? 0);

        if ($interviewId > 0) {
            $pdo->prepare(
                'UPDATE interviews SET date_interview = ?, time_interview = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$dateInterview, $timeInterview, $status, $usuario, $interviewId]);
        } else {
            $ivCols = $pdo->query('SHOW COLUMNS FROM interviews')->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('id_interviewer', $ivCols, true)) {
                $pdo->prepare(
                    'INSERT INTO interviews (id_candidate, date_interview, time_interview, status, id_interviewer, created_by) VALUES (?,?,?,?,?,?)'
                )->execute([$candidateId, $dateInterview, $timeInterview, $status, $userId ?: null, $usuario]);
            } else {
                $pdo->prepare(
                    'INSERT INTO interviews (id_candidate, date_interview, time_interview, status, created_by) VALUES (?,?,?,?,?)'
                )->execute([$candidateId, $dateInterview, $timeInterview, $status, $usuario]);
            }
            $interviewId = (int) $pdo->lastInsertId();
        }

        $payload = json_encode($answers, JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return ['success' => false, 'message' => 'Datos de entrevista no válidos.'];
        }

        $existing = medidata_rrhh_fetch_interview_form($candidateId);
        if ($existing && !empty($existing['id'])) {
            $pdo->prepare(
                "UPDATE rrhh_questions_form SET payload = ?, status = 'Completado', id_interview = ?, updated_by = ?, updated_at = NOW() WHERE id = ?"
            )->execute([$payload, $interviewId, $usuario, (int) $existing['id']]);
        } else {
            $pdo->prepare(
                "INSERT INTO rrhh_questions_form (id_candidate, id_interview, payload, status, created_by) VALUES (?,?,?,'Completado',?)"
            )->execute([$candidateId, $interviewId, $payload, $usuario]);
        }

        medidata_rrhh_cambiar_estado_candidato(
            $candidateId,
            'Entrevista',
            $usuario,
            'Formulario de entrevista registrado.'
        );

        return ['success' => true, 'message' => 'Entrevista guardada. Aparecerá en el calendario de entrevistas.', 'interview_id' => $interviewId];
    }
}

if (!function_exists('medidata_rrhh_fetch_psychometric_form')) {
    /** @return array<string, mixed>|null */
    function medidata_rrhh_fetch_psychometric_form(int $candidateId): ?array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT id, score, payload, status FROM psychometric_question_form WHERE id_candidate = ? AND deleted = 0 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}

if (!function_exists('medidata_rrhh_psychometric_upload_dir')) {
    function medidata_rrhh_psychometric_upload_dir(): string
    {
        $dir = dirname(__DIR__) . '/uploads/rrhh/psicometricas';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $dir;
    }
}

if (!function_exists('medidata_rrhh_save_psychometric_form')) {
    /**
     * @param array<int, string> $tests
     * @return array{success:bool, message:string}
     */
    function medidata_rrhh_save_psychometric_form(int $candidateId, array $tests, ?array $file, string $usuario, ?string $score = null, ?string $notes = null): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return ['success' => false, 'message' => 'RRHH no disponible.'];
        }

        $tests = array_values(array_unique(array_filter(array_map('trim', $tests))));
        if ($tests === []) {
            return ['success' => false, 'message' => 'Seleccione al menos una prueba psicométrica.'];
        }

        $payload = [
            'tests' => $tests,
            'notes' => trim((string) $notes),
            'document' => null,
        ];

        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $tmp = (string) ($file['tmp_name'] ?? '');
            $mime = '';
            if ($tmp !== '' && is_uploaded_file($tmp)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
                if ($finfo) {
                    finfo_close($finfo);
                }
            }
            $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
            if ($mime !== 'application/pdf' && $ext !== 'pdf') {
                return ['success' => false, 'message' => 'El documento debe ser PDF.'];
            }

            $destName = 'psico_' . $candidateId . '_' . date('YmdHis') . '.pdf';
            $destPath = medidata_rrhh_psychometric_upload_dir() . DIRECTORY_SEPARATOR . $destName;
            if (!move_uploaded_file($tmp, $destPath)) {
                return ['success' => false, 'message' => 'No se pudo guardar el documento.'];
            }
            $payload['document'] = $destName;
            $payload['document_original'] = (string) ($file['name'] ?? '');
        }

        $existing = medidata_rrhh_fetch_psychometric_form($candidateId);
        if ($existing && !empty($existing['payload'])) {
            $prev = json_decode((string) $existing['payload'], true);
            if (is_array($prev) && empty($payload['document']) && !empty($prev['document'])) {
                $payload['document'] = $prev['document'];
                $payload['document_original'] = $prev['document_original'] ?? '';
            }
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return ['success' => false, 'message' => 'Datos no válidos.'];
        }

        $scoreVal = ($score !== null && $score !== '') ? (float) $score : null;
        $status = ($payload['document'] || $scoreVal !== null) ? 'Completado' : 'En Proceso';

        if ($existing && !empty($existing['id'])) {
            $pdo->prepare(
                'UPDATE psychometric_question_form SET score = ?, payload = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            )->execute([$scoreVal, $json, $status, $usuario, (int) $existing['id']]);
        } else {
            $pdo->prepare(
                'INSERT INTO psychometric_question_form (id_candidate, score, payload, status, created_by) VALUES (?,?,?,?,?)'
            )->execute([$candidateId, $scoreVal, $json, $status, $usuario]);
        }

        $testsLabel = implode(', ', $tests);
        $pdo->prepare(
            'UPDATE candidates SET psychometric_result = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
        )->execute(['Pruebas: ' . $testsLabel, $usuario, $candidateId]);

        medidata_rrhh_cambiar_estado_candidato(
            $candidateId,
            'Pruebas Psicometricas',
            $usuario,
            'Registro de pruebas psicométricas: ' . $testsLabel
        );

        return ['success' => true, 'message' => 'Pruebas psicométricas guardadas.'];
    }
}

if (!function_exists('medidata_rrhh_candidate_name_parts')) {
    /** @return array{nombres:string, apellidos:string} */
    function medidata_rrhh_candidate_name_parts(string $fullname): array
    {
        $fullname = trim(preg_replace('/\s+/', ' ', $fullname));
        if ($fullname === '') {
            return ['nombres' => '', 'apellidos' => ''];
        }
        $parts = explode(' ', $fullname, 2);
        return [
            'nombres' => $parts[0],
            'apellidos' => $parts[1] ?? '',
        ];
    }
}
