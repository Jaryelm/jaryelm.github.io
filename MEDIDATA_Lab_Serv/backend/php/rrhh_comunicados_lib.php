<?php
/**
 * Comunicados RRHH: envío masivo/manual vía SMTP (perfil talentohumano).
 */
declare(strict_types=1);

require_once __DIR__ . '/medidata_mailer_lib.php';

if (!function_exists('medidata_rrhh_comunicados_max_recipients')) {
    function medidata_rrhh_comunicados_max_recipients(): int
    {
        return 400;
    }
}

if (!function_exists('medidata_rrhh_comunicados_normalize_email')) {
    function medidata_rrhh_comunicados_normalize_email(string $email): string
    {
        return strtolower(trim($email));
    }
}

if (!function_exists('medidata_rrhh_comunicados_parse_manual_emails')) {
    /**
     * Acepta correos separados por coma, punto y coma, espacios o saltos de línea.
     * @return list<string>
     */
    function medidata_rrhh_comunicados_parse_manual_emails(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/[\s,;]+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $part) {
            $email = medidata_rrhh_comunicados_normalize_email((string) $part);
            if ($email === '') {
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $out[$email] = $email;
        }
        return array_values($out);
    }
}

if (!function_exists('medidata_rrhh_comunicados_fetch_users')) {
    /**
     * Usuarios MEDIDATA activos (con o sin correo).
     * @return list<array{id:int,name:string,email:string,username:string,rol:string,has_email:bool}>
     */
    function medidata_rrhh_comunicados_fetch_users(PDO $connect): array
    {
        $stmt = $connect->query(
            "SELECT id, name, username, email, rol
             FROM users
             WHERE state = '1'
             ORDER BY name ASC"
        );
        $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        $out = [];
        foreach ($rows as $row) {
            $email = medidata_rrhh_comunicados_normalize_email((string) ($row['email'] ?? ''));
            $valid = $email !== '' && (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
            $out[] = [
                'id' => (int) ($row['id'] ?? 0),
                'name' => trim((string) ($row['name'] ?? '')),
                'username' => trim((string) ($row['username'] ?? '')),
                'email' => $valid ? $email : '',
                'rol' => trim((string) ($row['rol'] ?? '')),
                'has_email' => $valid,
            ];
        }
        return $out;
    }
}

if (!function_exists('medidata_rrhh_comunicados_resolve_recipients')) {
    /**
     * Une usuarios seleccionados + correos manuales (deduplicados).
     *
     * @param list<int|string> $userIds
     * @param list<string>|string $manualEmails
     * @return array{
     *   recipients: list<array{email:string,name:string,source:string}>,
     *   invalid_manual: list<string>,
     *   skipped_no_email: list<array{id:int,name:string}>
     * }
     */
    function medidata_rrhh_comunicados_resolve_recipients(
        PDO $connect,
        array $userIds,
        $manualEmails
    ): array {
        $wanted = [];
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0) {
                $wanted[$uid] = $uid;
            }
        }

        $skipped = [];
        $byEmail = [];

        if ($wanted !== []) {
            $placeholders = implode(',', array_fill(0, count($wanted), '?'));
            $stmt = $connect->prepare(
                "SELECT id, name, email FROM users
                 WHERE state = '1' AND id IN ({$placeholders})"
            );
            $stmt->execute(array_values($wanted));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $id = (int) $row['id'];
                $name = trim((string) ($row['name'] ?? ''));
                $email = medidata_rrhh_comunicados_normalize_email((string) ($row['email'] ?? ''));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped[] = ['id' => $id, 'name' => $name !== '' ? $name : ('Usuario #' . $id)];
                    continue;
                }
                $byEmail[$email] = [
                    'email' => $email,
                    'name' => $name !== '' ? $name : $email,
                    'source' => 'usuario',
                ];
            }
        }

        $invalidManual = [];
        if (is_string($manualEmails)) {
            $rawParts = preg_split('/[\s,;]+/u', trim($manualEmails)) ?: [];
            $manualList = [];
            foreach ($rawParts as $part) {
                $part = trim((string) $part);
                if ($part === '') {
                    continue;
                }
                $norm = medidata_rrhh_comunicados_normalize_email($part);
                if (!filter_var($norm, FILTER_VALIDATE_EMAIL)) {
                    $invalidManual[] = $part;
                    continue;
                }
                $manualList[] = $norm;
            }
        } else {
            $manualList = [];
            foreach ((array) $manualEmails as $part) {
                $part = trim((string) $part);
                if ($part === '') {
                    continue;
                }
                $norm = medidata_rrhh_comunicados_normalize_email($part);
                if (!filter_var($norm, FILTER_VALIDATE_EMAIL)) {
                    $invalidManual[] = $part;
                    continue;
                }
                $manualList[] = $norm;
            }
        }

        foreach ($manualList as $email) {
            if (!isset($byEmail[$email])) {
                $byEmail[$email] = [
                    'email' => $email,
                    'name' => $email,
                    'source' => 'manual',
                ];
            }
        }

        return [
            'recipients' => array_values($byEmail),
            'invalid_manual' => array_values(array_unique($invalidManual)),
            'skipped_no_email' => $skipped,
        ];
    }
}

if (!function_exists('medidata_rrhh_comunicados_apply_placeholders')) {
    function medidata_rrhh_comunicados_apply_placeholders(string $text, string $recipientName): string
    {
        $name = trim($recipientName) !== '' ? trim($recipientName) : 'colaborador/a';
        return str_replace(
            ['{{nombre}}', '{{NOMBRE}}', '{nombre}'],
            [$name, $name, $name],
            $text
        );
    }
}

if (!function_exists('medidata_rrhh_comunicados_build_bodies')) {
    /**
     * @return array{html:string,text:string}
     */
    function medidata_rrhh_comunicados_build_bodies(string $message, string $recipientName): array
    {
        $personalized = medidata_rrhh_comunicados_apply_placeholders($message, $recipientName);
        $personalized = trim($personalized);
        $nombreEsc = htmlspecialchars(
            trim($recipientName) !== '' ? trim($recipientName) : 'colaborador/a',
            ENT_QUOTES,
            'UTF-8'
        );
        $msgEsc = nl2br(htmlspecialchars($personalized, ENT_QUOTES, 'UTF-8'));

        $html = '<div style="font-family:Arial,Helvetica,sans-serif;color:#333;line-height:1.55;font-size:15px;">'
            . '<p>Estimado/a <strong>' . $nombreEsc . '</strong>,</p>'
            . '<div style="margin:16px 0;">' . $msgEsc . '</div>'
            . '<p style="margin-top:28px;color:#555;font-size:13px;">Atentamente,<br>'
            . '<strong>Talento Humano</strong><br>Hospital MEDICASA</p>'
            . '<p style="margin-top:18px;color:#888;font-size:11px;">Este mensaje fue enviado desde MEDIDATA — Recursos Humanos.</p>'
            . '</div>';

        $text = "Estimado/a " . (trim($recipientName) !== '' ? trim($recipientName) : 'colaborador/a') . ",\n\n"
            . $personalized . "\n\n"
            . "Atentamente,\nTalento Humano\nHospital MEDICASA\n";

        return ['html' => $html, 'text' => $text];
    }
}

if (!function_exists('medidata_rrhh_comunicados_send_bulk')) {
    /**
     * @param list<array{email:string,name:string,source?:string}> $recipients
     * @return array{
     *   success:bool,
     *   message:string,
     *   sent:int,
     *   failed:int,
     *   total:int,
     *   details:list<array{email:string,name:string,ok:bool,message:string}>
     * }
     */
    function medidata_rrhh_comunicados_send_bulk(
        array $recipients,
        string $subject,
        string $message
    ): array {
        $subject = trim($subject);
        $message = trim($message);
        if ($subject === '') {
            return [
                'success' => false,
                'message' => 'El asunto del correo es obligatorio.',
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'details' => [],
            ];
        }
        if ($message === '') {
            return [
                'success' => false,
                'message' => 'El mensaje del correo es obligatorio.',
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'details' => [],
            ];
        }

        $max = medidata_rrhh_comunicados_max_recipients();
        if (count($recipients) === 0) {
            return [
                'success' => false,
                'message' => 'No hay destinatarios válidos para enviar.',
                'sent' => 0,
                'failed' => 0,
                'total' => 0,
                'details' => [],
            ];
        }
        if (count($recipients) > $max) {
            return [
                'success' => false,
                'message' => 'Máximo ' . $max . ' destinatarios por envío. Reduzca la lista e intente de nuevo.',
                'sent' => 0,
                'failed' => 0,
                'total' => count($recipients),
                'details' => [],
            ];
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $sent = 0;
        $failed = 0;
        $details = [];

        foreach ($recipients as $rec) {
            $email = medidata_rrhh_comunicados_normalize_email((string) ($rec['email'] ?? ''));
            $name = trim((string) ($rec['name'] ?? ''));
            if ($name === '') {
                $name = $email;
            }

            $subj = medidata_rrhh_comunicados_apply_placeholders($subject, $name);
            $bodies = medidata_rrhh_comunicados_build_bodies($message, $name);
            $result = medidata_rrhh_send_email($email, $subj, $bodies['html'], $bodies['text'], $name);

            $ok = !empty($result['success']);
            if ($ok) {
                $sent++;
            } else {
                $failed++;
            }
            $details[] = [
                'email' => $email,
                'name' => $name,
                'ok' => $ok,
                'message' => (string) ($result['message'] ?? ''),
            ];

            // Pequeña pausa para no saturar el SMTP.
            usleep(150000);
        }

        $total = count($recipients);
        $allOk = $failed === 0;
        $msg = $allOk
            ? ('Se enviaron correctamente ' . $sent . ' correo(s).')
            : ('Envío finalizado: ' . $sent . ' exitoso(s), ' . $failed . ' fallido(s) de ' . $total . '.');

        return [
            'success' => $allOk,
            'message' => $msg,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $total,
            'details' => $details,
        ];
    }
}
