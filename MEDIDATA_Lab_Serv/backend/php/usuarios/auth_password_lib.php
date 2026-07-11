<?php
/**
 * Recuperación y avisos de contraseña — módulo Usuarios (perfil SMTP auth).
 */

require_once __DIR__ . '/../../bd/medidata_paths.php';
require_once __DIR__ . '/../medidata_mailer_lib.php';
require_once __DIR__ . '/../medidata_mailer_config.php';

if (!function_exists('medidata_auth_password_ensure_table')) {
    function medidata_auth_password_ensure_table(PDO $connect): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        try {
            $connect->exec("CREATE TABLE IF NOT EXISTS `users_password_reset` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `token_hash` char(64) NOT NULL,
                `expires_at` datetime NOT NULL,
                `used_at` datetime DEFAULT NULL,
                `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `request_ip` varchar(45) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_upr_token` (`token_hash`),
                KEY `idx_upr_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $connect->exec("CREATE TABLE IF NOT EXISTS `users_password_reset_attempts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `login_hash` char(64) NOT NULL,
                `request_ip` varchar(45) DEFAULT NULL,
                `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_upra_login_time` (`login_hash`, `attempted_at`),
                KEY `idx_upra_ip_time` (`request_ip`, `attempted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('medidata_auth_password_ensure_table: ' . $e->getMessage());
        }
        $done = true;
    }
}

if (!function_exists('medidata_auth_password_rate_limit_config')) {
    /** @return array{login_max:int,login_window:int,ip_max:int,ip_window:int} */
    function medidata_auth_password_rate_limit_config(): array
    {
        return [
            'login_max' => 3,
            'login_window' => 1800,
            'ip_max' => 12,
            'ip_window' => 3600,
        ];
    }
}

if (!function_exists('medidata_auth_password_login_hash')) {
    function medidata_auth_password_login_hash(string $login): string
    {
        $normalized = trim($login);
        if (function_exists('mb_strtolower')) {
            $normalized = mb_strtolower($normalized, 'UTF-8');
        } else {
            $normalized = strtolower($normalized);
        }
        return hash('sha256', $normalized);
    }
}

if (!function_exists('medidata_auth_password_attempts_cleanup')) {
    function medidata_auth_password_attempts_cleanup(PDO $connect): void
    {
        $cfg = medidata_auth_password_rate_limit_config();
        $keepSeconds = (int) max($cfg['login_window'], $cfg['ip_window']) + 3600;
        if ($keepSeconds < 1) {
            return;
        }
        try {
            $connect->exec(
                'DELETE FROM users_password_reset_attempts
                 WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ' . $keepSeconds . ' SECOND)'
            );
        } catch (Throwable $e) {
            error_log('medidata_auth_password_attempts_cleanup: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_auth_password_rate_limit_check')) {
    /**
     * Límite por usuario/correo ingresado y por IP (ventana deslizante).
     *
     * @return array{allowed:bool,reason:?string,retry_minutes:int}
     */
    function medidata_auth_password_rate_limit_check(PDO $connect, string $login, ?string $ip = null): array
    {
        try {
            medidata_auth_password_ensure_table($connect);
            medidata_auth_password_attempts_cleanup($connect);

            $cfg = medidata_auth_password_rate_limit_config();
            $loginHash = medidata_auth_password_login_hash($login);
            $ip = trim((string) $ip);

            $loginSince = date('Y-m-d H:i:s', time() - $cfg['login_window']);
            $stmtLogin = $connect->prepare(
                'SELECT COUNT(*) AS total, MIN(attempted_at) AS first_at
                 FROM users_password_reset_attempts
                 WHERE login_hash = ? AND attempted_at >= ?'
            );
            $stmtLogin->execute([$loginHash, $loginSince]);
            $loginRow = $stmtLogin->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'first_at' => null];

            if ((int) ($loginRow['total'] ?? 0) >= $cfg['login_max']) {
                $retryMinutes = medidata_auth_password_retry_minutes(
                    (string) ($loginRow['first_at'] ?? ''),
                    $cfg['login_window']
                );
                return ['allowed' => false, 'reason' => 'login', 'retry_minutes' => $retryMinutes];
            }

            if ($ip !== '') {
                $ipSince = date('Y-m-d H:i:s', time() - $cfg['ip_window']);
                $stmtIp = $connect->prepare(
                    'SELECT COUNT(*) AS total, MIN(attempted_at) AS first_at
                     FROM users_password_reset_attempts
                     WHERE request_ip = ? AND attempted_at >= ?'
                );
                $stmtIp->execute([$ip, $ipSince]);
                $ipRow = $stmtIp->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'first_at' => null];

                if ((int) ($ipRow['total'] ?? 0) >= $cfg['ip_max']) {
                    $retryMinutes = medidata_auth_password_retry_minutes(
                        (string) ($ipRow['first_at'] ?? ''),
                        $cfg['ip_window']
                    );
                    return ['allowed' => false, 'reason' => 'ip', 'retry_minutes' => $retryMinutes];
                }
            }

            return ['allowed' => true, 'reason' => null, 'retry_minutes' => 0];
        } catch (Throwable $e) {
            error_log('medidata_auth_password_rate_limit_check: ' . $e->getMessage());
            return ['allowed' => true, 'reason' => null, 'retry_minutes' => 0];
        }
    }
}

if (!function_exists('medidata_auth_password_retry_minutes')) {
    function medidata_auth_password_retry_minutes(string $firstAttemptAt, int $windowSeconds): int
    {
        if ($firstAttemptAt === '') {
            return max(1, (int) ceil($windowSeconds / 60));
        }
        $unlockAt = strtotime($firstAttemptAt) + $windowSeconds;
        $remaining = $unlockAt - time();
        if ($remaining <= 0) {
            return 1;
        }
        return max(1, (int) ceil($remaining / 60));
    }
}

if (!function_exists('medidata_auth_password_rate_limit_record')) {
    function medidata_auth_password_rate_limit_record(PDO $connect, string $login, ?string $ip = null): void
    {
        try {
            medidata_auth_password_ensure_table($connect);
            $connect->prepare(
                'INSERT INTO users_password_reset_attempts (login_hash, request_ip) VALUES (?, ?)'
            )->execute([
                medidata_auth_password_login_hash($login),
                trim((string) $ip) !== '' ? trim((string) $ip) : null,
            ]);
        } catch (Throwable $e) {
            error_log('medidata_auth_password_rate_limit_record: ' . $e->getMessage());
        }
    }
}

if (!function_exists('medidata_auth_password_rate_limit_message')) {
    function medidata_auth_password_rate_limit_message(int $retryMinutes): string
    {
        $mins = max(1, $retryMinutes);
        $label = $mins === 1 ? '1 minuto' : $mins . ' minutos';
        return 'Por seguridad, se pausaron temporalmente las solicitudes de recuperación. '
            . 'Intente nuevamente en ' . $label . '.';
    }
}

if (!function_exists('medidata_auth_alert_email')) {
    function medidata_auth_alert_email(): string
    {
        $cfg = medidata_mailer_config('auth');
        $alert = trim((string) ($cfg['alert_email'] ?? ''));
        if ($alert !== '' && filter_var($alert, FILTER_VALIDATE_EMAIL)) {
            return $alert;
        }
        return trim((string) ($cfg['from_email'] ?? 'soporteti@medicasa.hn'));
    }
}

if (!function_exists('medidata_auth_password_reset_public_path')) {
    function medidata_auth_password_reset_public_path(string $token): string
    {
        return '/frontend/usuarios/restablecer_password.php?token=' . rawurlencode($token);
    }
}

if (!function_exists('medidata_auth_password_reset_public_url')) {
    function medidata_auth_password_reset_public_url(string $token): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        return $scheme . '://' . $host . medidata_url(medidata_auth_password_reset_public_path($token));
    }
}

if (!function_exists('medidata_auth_password_find_user')) {
    function medidata_auth_password_find_user(PDO $connect, string $login): ?array
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }
        $stmt = $connect->prepare(
            'SELECT id, username, name, email, rol, state FROM users
             WHERE username = :q OR email = :q2 LIMIT 1'
        );
        $stmt->execute([':q' => $login, ':q2' => $login]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('medidata_auth_password_issue_token')) {
    function medidata_auth_password_issue_token(PDO $connect, int $userId, ?string $ip = null): string
    {
        medidata_auth_password_ensure_table($connect);
        $connect->prepare(
            'UPDATE users_password_reset SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()'
        )->execute([$userId]);

        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $stmt = $connect->prepare(
            'INSERT INTO users_password_reset (user_id, token_hash, expires_at, request_ip)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $hash, $expires, $ip]);

        return $token;
    }
}

if (!function_exists('medidata_auth_password_user_by_token')) {
    function medidata_auth_password_user_by_token(PDO $connect, string $token): ?array
    {
        $token = trim($token);
        if ($token === '' || strlen($token) < 32) {
            return null;
        }
        medidata_auth_password_ensure_table($connect);
        $hash = hash('sha256', $token);
        $stmt = $connect->prepare(
            'SELECT r.id AS reset_id, r.user_id, r.expires_at, u.username, u.name, u.email, u.rol, u.state
             FROM users_password_reset r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}

if (!function_exists('medidata_auth_password_mark_used')) {
    function medidata_auth_password_mark_used(PDO $connect, int $resetId): void
    {
        $connect->prepare('UPDATE users_password_reset SET used_at = NOW() WHERE id = ? LIMIT 1')
            ->execute([$resetId]);
    }
}

if (!function_exists('medidata_auth_password_notify_ti')) {
    function medidata_auth_password_notify_ti(string $subject, string $bodyHtml, string $bodyText): void
    {
        medidata_auth_send_email(medidata_auth_alert_email(), $subject, $bodyHtml, $bodyText, 'Soporte TI');
    }
}

if (!function_exists('medidata_auth_password_request')) {
    /** @return array{ok:bool, message:string} */
    function medidata_auth_password_request(PDO $connect, string $login, ?string $ip = null): array
    {
        $generic = 'Si el usuario existe y tiene correo registrado, recibirá un enlace para restablecer su contraseña. Revise también la carpeta de spam.';
        $login = trim($login);

        if ($login === '') {
            return ['ok' => false, 'message' => 'Ingrese su usuario o correo institucional.'];
        }

        $rateBefore = medidata_auth_password_rate_limit_check($connect, $login, $ip);
        if (!$rateBefore['allowed']) {
            medidata_auth_password_notify_ti(
                'MEDIDATA — Recuperación bloqueada por límite de intentos',
                '<p>Se bloqueó temporalmente una solicitud de recuperación de contraseña.</p>'
                . '<p>Identificador: <strong>' . htmlspecialchars($login) . '</strong></p>'
                . '<p>Motivo: ' . htmlspecialchars((string) $rateBefore['reason']) . '</p>'
                . '<p>IP: ' . htmlspecialchars((string) $ip) . '</p>'
                . '<p>Reintento estimado en: ' . (int) $rateBefore['retry_minutes'] . ' min.</p>'
                . '<p>Fecha: ' . date('Y-m-d H:i:s') . '</p>',
                "Recuperación bloqueada para {$login}. Motivo: {$rateBefore['reason']}. IP: {$ip}"
            );
            return [
                'ok' => false,
                'message' => medidata_auth_password_rate_limit_message((int) $rateBefore['retry_minutes']),
                'blocked' => true,
            ];
        }

        medidata_auth_password_rate_limit_record($connect, $login, $ip);

        $user = medidata_auth_password_find_user($connect, $login);
        if (!$user) {
            return ['ok' => true, 'message' => $generic];
        }
        if (isset($user['state']) && (string) $user['state'] === '0') {
            return ['ok' => true, 'message' => $generic];
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            medidata_auth_password_notify_ti(
                'MEDIDATA — Solicitud recuperación sin correo válido',
                '<p>Se solicitó recuperación de contraseña para el usuario <strong>' . htmlspecialchars($user['username']) . '</strong> '
                . '(ID ' . (int) $user['id'] . ') pero no tiene correo válido en el sistema.</p>'
                . '<p>IP: ' . htmlspecialchars((string) $ip) . '</p>',
                'Recuperación solicitada para ' . $user['username'] . ' sin correo válido. IP: ' . $ip
            );
            return ['ok' => true, 'message' => $generic];
        }

        $token = medidata_auth_password_issue_token($connect, (int) $user['id'], $ip);
        $resetUrl = medidata_auth_password_reset_public_url($token);
        $name = htmlspecialchars((string) $user['name']);
        $userEsc = htmlspecialchars((string) $user['username']);

        $subjectUser = 'MEDIDATA — Restablecer contraseña';
        $htmlUser = '<div style="font-family:Arial,sans-serif;color:#333;line-height:1.5;">'
            . '<p>Hola <strong>' . $name . '</strong>,</p>'
            . '<p>Recibimos una solicitud para restablecer la contraseña de su cuenta <strong>' . $userEsc . '</strong> en MEDIDATA.</p>'
            . '<p><a href="' . htmlspecialchars($resetUrl) . '" style="display:inline-block;background:#035c67;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;font-weight:bold;">Restablecer contraseña</a></p>'
            . '<p>El enlace expira en <strong>1 hora</strong>. Si no solicitó este cambio, ignore este correo.</p>'
            . '<p style="word-break:break-all;color:#666;font-size:13px;">' . htmlspecialchars($resetUrl) . '</p>'
            . '<p>Soporte TI — Hospital MEDICASA</p></div>';
        $textUser = "Hola {$user['name']},\n\nRestablezca su contraseña MEDIDATA:\n{$resetUrl}\n\nExpira en 1 hora.\n";

        medidata_auth_send_email($email, $subjectUser, $htmlUser, $textUser, (string) $user['name']);

        medidata_auth_password_notify_ti(
            'MEDIDATA — Solicitud de recuperación de contraseña',
            '<p>Usuario: <strong>' . $userEsc . '</strong> (ID ' . (int) $user['id'] . ', ' . htmlspecialchars((string) $user['rol']) . ')</p>'
            . '<p>Correo destino: ' . htmlspecialchars($email) . '</p>'
            . '<p>IP solicitud: ' . htmlspecialchars((string) $ip) . '</p>'
            . '<p>Fecha: ' . date('Y-m-d H:i:s') . '</p>',
            "Recuperación solicitada: {$user['username']} -> {$email}. IP: {$ip}"
        );

        return ['ok' => true, 'message' => $generic];
    }
}

if (!function_exists('medidata_auth_password_reset_apply')) {
    /** @return array{ok:bool, message:string} */
    function medidata_auth_password_reset_apply(PDO $connect, string $token, string $newPassword, ?string $ip = null): array
    {
        $newPassword = trim($newPassword);
        if (strlen($newPassword) < 6) {
            return ['ok' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        $row = medidata_auth_password_user_by_token($connect, $token);
        if (!$row) {
            return ['ok' => false, 'message' => 'El enlace no es válido o ya expiró. Solicite uno nuevo.'];
        }
        if (isset($row['state']) && (string) $row['state'] === '0') {
            return ['ok' => false, 'message' => 'Esta cuenta está desactivada. Contacte a Soporte TI.'];
        }

        $hash = md5($newPassword);
        $connect->prepare('UPDATE users SET password = ? WHERE id = ? LIMIT 1')
            ->execute([$hash, (int) $row['user_id']]);
        medidata_auth_password_mark_used($connect, (int) $row['reset_id']);

        $userEsc = htmlspecialchars((string) $row['username']);
        $email = trim((string) ($row['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            medidata_auth_send_email(
                $email,
                'MEDIDATA — Contraseña actualizada',
                '<p>Su contraseña de MEDIDATA fue restablecida correctamente.</p>'
                . '<p>Usuario: <strong>' . $userEsc . '</strong></p>'
                . '<p>Si no realizó este cambio, contacte de inmediato a Soporte TI.</p>',
                "Su contraseña MEDIDATA ({$row['username']}) fue restablecida. Si no fue usted, contacte a Soporte TI.",
                (string) $row['name']
            );
        }

        medidata_auth_password_notify_ti(
            'MEDIDATA — Contraseña restablecida por enlace',
            '<p>Usuario: <strong>' . $userEsc . '</strong> (ID ' . (int) $row['user_id'] . ')</p>'
            . '<p>Acción: restablecimiento vía enlace de recuperación.</p>'
            . '<p>IP: ' . htmlspecialchars((string) $ip) . '</p>'
            . '<p>Fecha: ' . date('Y-m-d H:i:s') . '</p>',
            "Contraseña restablecida: {$row['username']}. IP: {$ip}"
        );

        return ['ok' => true, 'message' => 'Contraseña actualizada. Ya puede iniciar sesión.'];
    }
}

if (!function_exists('medidata_auth_password_notify_admin_change')) {
    function medidata_auth_password_notify_admin_change(
        PDO $connect,
        int $userId,
        ?string $changedByUsername = null,
        ?string $changedByName = null
    ): void {
        $stmt = $connect->prepare('SELECT username, name, email, rol FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return;
        }

        $by = trim((string) ($changedByName ?? $changedByUsername ?? 'Administrador'));
        $userEsc = htmlspecialchars((string) $user['username']);
        $email = trim((string) ($user['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            medidata_auth_send_email(
                $email,
                'MEDIDATA — Su contraseña fue cambiada',
                '<p>Hola <strong>' . htmlspecialchars((string) $user['name']) . '</strong>,</p>'
                . '<p>Un administrador actualizó la contraseña de su cuenta <strong>' . $userEsc . '</strong>.</p>'
                . '<p>Cambiado por: <strong>' . htmlspecialchars($by) . '</strong></p>'
                . '<p>Si no reconoce esta acción, contacte a Soporte TI de inmediato.</p>',
                "Su contraseña MEDIDATA ({$user['username']}) fue cambiada por {$by}.",
                (string) $user['name']
            );
        }

        medidata_auth_password_notify_ti(
            'MEDIDATA — Contraseña cambiada por administrador',
            '<p>Usuario afectado: <strong>' . $userEsc . '</strong> (' . htmlspecialchars((string) $user['rol']) . ')</p>'
            . '<p>Cambiado por: <strong>' . htmlspecialchars($by) . '</strong></p>'
            . '<p>Fecha: ' . date('Y-m-d H:i:s') . '</p>',
            "Admin cambió contraseña de {$user['username']}. Por: {$by}"
        );
    }
}
