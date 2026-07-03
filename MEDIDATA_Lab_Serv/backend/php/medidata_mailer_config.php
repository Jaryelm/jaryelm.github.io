<?php
/**
 * Configuración SMTP de MEDICASA.
 *
 * En producción, prefiere override por variables de entorno:
 *   SetEnv MEDIDATA_SMTP_HOST "us2.smtp.mailhostbox.com"
 *   SetEnv MEDIDATA_SMTP_USER "talentohumano@medicasa.hn"
 *   SetEnv MEDIDATA_SMTP_PASS "..."
 *   SetEnv MEDIDATA_SMTP_PORT "587"
 *
 * Alternativa: colocar en el mismo directorio un archivo
 * medidata_mailer_secrets.php que retorne un array override.
 */

if (!function_exists('medidata_mailer_config')) {
    function medidata_mailer_config(): array
    {
        $defaults = [
            'host' => 'us2.smtp.mailhostbox.com',
            'username' => 'talentohumano@medicasa.hn',
            'password' => 'RQYyizd6',
            'port' => 587,
            'secure' => 'tls',
            'from_email' => 'talentohumano@medicasa.hn',
            'from_name' => 'MEDICASA — Talento Humano',
            'reply_to_email' => 'talentohumano@medicasa.hn',
            'reply_to_name' => 'Talento Humano MEDICASA',
            'debug_level' => 0,
        ];

        $envMap = [
            'host' => 'MEDIDATA_SMTP_HOST',
            'username' => 'MEDIDATA_SMTP_USER',
            'password' => 'MEDIDATA_SMTP_PASS',
            'port' => 'MEDIDATA_SMTP_PORT',
            'secure' => 'MEDIDATA_SMTP_SECURE',
            'from_email' => 'MEDIDATA_SMTP_FROM',
            'from_name' => 'MEDIDATA_SMTP_FROM_NAME',
            'reply_to_email' => 'MEDIDATA_SMTP_REPLY',
        ];
        foreach ($envMap as $key => $env) {
            $val = getenv($env);
            if ($val !== false && $val !== '') {
                $defaults[$key] = $key === 'port' ? (int) $val : $val;
            }
        }

        $secretsFile = __DIR__ . '/medidata_mailer_secrets.php';
        if (is_readable($secretsFile)) {
            $override = require $secretsFile;
            if (is_array($override)) {
                $defaults = array_merge($defaults, $override);
            }
        }

        return $defaults;
    }
}
