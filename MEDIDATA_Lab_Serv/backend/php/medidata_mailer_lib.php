<?php
/**
 * Envío de correo vía PHPMailer (perfiles SMTP).
 */

if (!function_exists('medidata_send_email')) {
    /**
     * @param string $profile rrhh|auth
     * @return array{success:bool, message:string}
     */
    function medidata_send_email(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        string $bodyText,
        string $recipientName = '',
        string $profile = 'rrhh'
    ): array {
        $toEmail = trim($toEmail);
        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Correo del destinatario no válido.'];
        }

        require_once __DIR__ . '/../vendor/phpmailer/autoload.php';
        require_once __DIR__ . '/medidata_mailer_config.php';

        if (!in_array($profile, medidata_mailer_profile_keys(), true)) {
            $profile = 'rrhh';
        }

        $cfg = medidata_mailer_config($profile);
        if (trim((string) ($cfg['password'] ?? '')) === '') {
            return ['success' => false, 'message' => 'SMTP no configurado para el perfil "' . $profile . '".'];
        }

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
            $replyEmail = $cfg['reply_to_email'] ?? $cfg['from_email'];
            $replyName = $cfg['reply_to_name'] ?? $cfg['from_name'];
            $mail->addReplyTo($replyEmail, $replyName);
            $mail->addAddress($toEmail, $recipientName !== '' ? $recipientName : $toEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $bodyHtml;
            $mail->AltBody = $bodyText;
            $mail->send();

            return ['success' => true, 'message' => 'Correo enviado a ' . $toEmail . '.'];
        } catch (Throwable $e) {
            error_log('medidata_send_email[' . $profile . ']: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo enviar el correo. Intente más tarde o contacte a Soporte TI.'];
        }
    }
}

if (!function_exists('medidata_rrhh_send_email')) {
    function medidata_rrhh_send_email(string $toEmail, string $subject, string $bodyHtml, string $bodyText, string $recipientName = ''): array
    {
        return medidata_send_email($toEmail, $subject, $bodyHtml, $bodyText, $recipientName, 'rrhh');
    }
}

if (!function_exists('medidata_auth_send_email')) {
    /** Correos de intranet: recuperación / avisos de contraseña (soporteti@medicasa.hn). */
    function medidata_auth_send_email(string $toEmail, string $subject, string $bodyHtml, string $bodyText, string $recipientName = ''): array
    {
        return medidata_send_email($toEmail, $subject, $bodyHtml, $bodyText, $recipientName, 'auth');
    }
}
