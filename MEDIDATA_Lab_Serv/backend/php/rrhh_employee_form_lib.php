<?php
/**
 * Formulario de empleados — token de acceso, enlace público y envío por correo.
 */

require_once __DIR__ . '/../bd/medidata_paths.php';
require_once __DIR__ . '/../registros/rrhh_guard.php';
require_once __DIR__ . '/../registros/rrhh_aplica_bridge.php';
require_once __DIR__ . '/rrhh_employee_form_fields_lib.php';

if (!function_exists('medidata_rrhh_ensure_employee_form_schema')) {
    function medidata_rrhh_ensure_employee_form_schema(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $cols = $pdo->query('SHOW COLUMNS FROM candidates')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('form_access_token', $cols, true)) {
                $pdo->exec(
                    'ALTER TABLE candidates ADD COLUMN form_access_token VARCHAR(64) NULL DEFAULT NULL AFTER overall_score'
                );
            }
            $idx = $pdo->query(
                "SHOW INDEX FROM candidates WHERE Key_name = 'idx_candidates_form_token'"
            )->fetch(PDO::FETCH_ASSOC);
            if (!$idx) {
                $pdo->exec(
                    'ALTER TABLE candidates ADD UNIQUE KEY idx_candidates_form_token (form_access_token)'
                );
            }
        } catch (Throwable $e) {
            error_log('medidata_rrhh_ensure_employee_form_schema: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_absolute_url')) {
    function medidata_absolute_url(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return $scheme . '://' . $host . medidata_url($path);
    }
}

if (!function_exists('medidata_rrhh_employee_form_public_path')) {
    function medidata_rrhh_employee_form_public_path(string $token): string
    {
        return '/frontend/recursos_humanos/formulario_empleado.php?token=' . rawurlencode($token);
    }
}

if (!function_exists('medidata_rrhh_employee_form_public_url')) {
    function medidata_rrhh_employee_form_public_url(string $token): string
    {
        return medidata_absolute_url(medidata_rrhh_employee_form_public_path($token));
    }
}

if (!function_exists('medidata_rrhh_employee_form_ensure_row')) {
    function medidata_rrhh_employee_form_ensure_row(PDO $pdo, int $candidateId, string $createdBy): int
    {
        $stmt = $pdo->prepare(
            'SELECT id FROM employees_form WHERE id_candidate = ? AND deleted = 0 ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$candidateId]);
        $id = (int) $stmt->fetchColumn();
        if ($id > 0) {
            return $id;
        }

        $pdo->prepare(
            "INSERT INTO employees_form (id_candidate, payload, status, created_by)
             VALUES (?, '{}', 'Borrador', ?)"
        )->execute([$candidateId, $createdBy]);

        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('medidata_rrhh_employee_form_issue_link')) {
    /**
     * @return array{success:bool,message?:string,token?:string,url?:string,email?:string,already_sent?:bool}
     */
    function medidata_rrhh_employee_form_issue_link(int $candidateId, string $usuario, bool $updateStatus = true): array
    {
        $pdo = medidata_rrhh_pdo();
        if (!$pdo || $candidateId <= 0) {
            return ['success' => false, 'message' => 'RRHH no disponible o candidato inválido.'];
        }

        medidata_rrhh_ensure_employee_form_schema($pdo);

        $stmt = $pdo->prepare(
            'SELECT id, fullname, email, form_access_token, status
             FROM candidates WHERE id = ? AND deleted = 0 LIMIT 1'
        );
        $stmt->execute([$candidateId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['success' => false, 'message' => 'Candidato no encontrado.'];
        }

        $email = trim((string) ($row['email'] ?? ''));

        $token = trim((string) ($row['form_access_token'] ?? ''));
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $upd = $pdo->prepare(
                'UPDATE candidates SET form_access_token = ?, updated_by = ?, updated_at = NOW() WHERE id = ?'
            );
            $upd->execute([$token, $usuario, $candidateId]);
        }

        medidata_rrhh_employee_form_ensure_row($pdo, $candidateId, $usuario);

        if ($updateStatus && ($row['status'] ?? '') !== 'Formulario Empleados') {
            medidata_rrhh_cambiar_estado_candidato(
                $candidateId,
                'Formulario Empleados',
                $usuario,
                'Enlace de formulario de empleado generado.'
            );
        }

        return [
            'success' => true,
            'token' => $token,
            'url' => medidata_rrhh_employee_form_public_url($token),
            'email' => $email,
            'candidate_name' => (string) ($row['fullname'] ?? ''),
        ];
    }
}

if (!function_exists('medidata_rrhh_employee_form_send_email')) {
    /**
     * @return array{success:bool,message:string}
     */
    function medidata_rrhh_employee_form_send_email(string $toEmail, string $candidateName, string $formUrl): array
    {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Correo del candidato no válido.'];
        }

        require_once __DIR__ . '/medidata_mailer_lib.php';

        $nombre = trim($candidateName) !== '' ? trim($candidateName) : 'Candidato/a';
        $urlEscaped = htmlspecialchars($formUrl, ENT_QUOTES, 'UTF-8');
        $nombreEscaped = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $subject = 'Hospital MEDICASA — Formulario de empleado';

        $bodyHtml = "<div style=\"font-family:Arial,sans-serif;color:#333;line-height:1.5;\">"
            . "<p>Estimado/a <strong>{$nombreEscaped}</strong>,</p>"
            . "<p>Le invitamos a completar el <strong>Formulario de empleado</strong> de Hospital MEDICASA como parte de su proceso de contratación.</p>"
            . "<p style=\"text-align:left;margin:24px 0;\">"
            . "<a href=\"{$urlEscaped}\" style=\"display:inline-block;background:#06adbf;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:bold;\">Completar formulario</a>"
            . "</p>"
            . "<p>Si el botón no funciona, copie y pegue este enlace en su navegador:<br>"
            . "<span style=\"color:#035c67;word-break:break-all;\">{$urlEscaped}</span></p>"
            . "<p>Cualquier duda, comuníquese con Recursos Humanos.</p>"
            . "<p style=\"margin-top:24px;color:#555;font-size:13px;\">Atentamente,<br>"
            . "<strong>Talento Humano</strong><br>Hospital MEDICASA</p>"
            . "</div>";

        $bodyText = "Estimado/a {$nombre},\n\n"
            . "Le invitamos a completar el formulario de empleado de Hospital MEDICASA:\n\n"
            . "{$formUrl}\n\n"
            . "Cualquier duda, comuníquese con Recursos Humanos.\n\n"
            . "Atentamente,\nTalento Humano — Hospital MEDICASA";

        return medidata_rrhh_send_email($toEmail, $subject, $bodyHtml, $bodyText, $nombre);
    }
}

if (!function_exists('medidata_rrhh_employee_form_by_token')) {
    /**
     * @return array<string, mixed>|null
     */
    function medidata_rrhh_employee_form_by_token(string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 16) {
            return null;
        }

        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return null;
        }

        medidata_rrhh_ensure_employee_form_schema($pdo);

        $stmt = $pdo->prepare(
            "SELECT c.id AS candidate_id, c.fullname, c.dni, c.email, c.phonenumber, c.direction,
                    c.birthdate, c.marital_status, c.status AS candidate_status,
                    ef.id AS form_id, ef.payload, ef.status AS form_status
             FROM candidates c
             LEFT JOIN employees_form ef ON ef.id_candidate = c.id AND ef.deleted = 0
             WHERE c.form_access_token = ? AND c.deleted = 0
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }
}

if (!function_exists('medidata_rrhh_employee_form_save_public')) {
    /**
     * @param array<string, mixed> $fields
     * @return array{success:bool,message:string}
     */
    function medidata_rrhh_employee_form_save_public(string $token, array $fields): array
    {
        $ctx = medidata_rrhh_employee_form_by_token($token);
        if (!$ctx) {
            return ['success' => false, 'message' => 'Enlace no válido o expirado.'];
        }

        if (($ctx['form_status'] ?? '') === 'Enviado') {
            return ['success' => false, 'message' => 'Este formulario ya fue enviado.'];
        }

        $pdo = medidata_rrhh_pdo();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Servicio no disponible.'];
        }

        $candidateId = (int) $ctx['candidate_id'];
        $payload = json_encode($fields, JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            return ['success' => false, 'message' => 'Datos del formulario no válidos.'];
        }

        $formId = (int) ($ctx['form_id'] ?? 0);
        if ($formId <= 0) {
            $formId = medidata_rrhh_employee_form_ensure_row($pdo, $candidateId, 'formulario_publico');
        }

        $pdo->prepare(
            "UPDATE employees_form SET payload = ?, status = 'Enviado', updated_by = 'formulario_publico', updated_at = NOW()
             WHERE id = ?"
        )->execute([$payload, $formId]);

        medidata_rrhh_employee_form_sync_candidate($pdo, $candidateId, $fields);

        return ['success' => true, 'message' => 'Solicitud de empleo enviada correctamente. Gracias.'];
    }
}
