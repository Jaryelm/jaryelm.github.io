<?php
/**
 * Configuración SMTP de MEDICASA (perfiles).
 *
 * Perfiles:
 *   rrhh — talentohumano@medicasa.hn (notificaciones RRHH, formularios candidatos)
 *   auth — soporteti@medicasa.hn (recuperación / avisos de contraseña intranet)
 *
 * Override opcional por variables de entorno (Apache SetEnv).
 */

if (!function_exists('medidata_mailer_profile_keys')) {
    function medidata_mailer_profile_keys(): array
    {
        return ['rrhh', 'auth'];
    }
}

if (!function_exists('medidata_mailer_profile_defaults')) {
    function medidata_mailer_profile_defaults(): array
    {
        return [
            'rrhh' => [
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
            ],
            'auth' => [
                'host' => 'us2.smtp.mailhostbox.com',
                'username' => 'soporteti@medicasa.hn',
                'password' => 'bXUJySF4',
                'port' => 587,
                'secure' => 'tls',
                'from_email' => 'soporteti@medicasa.hn',
                'from_name' => 'MEDICASA — Soporte TI',
                'reply_to_email' => 'soporteti@medicasa.hn',
                'reply_to_name' => 'Soporte TI MEDICASA',
                'debug_level' => 0,
                'alert_email' => 'soporteti@medicasa.hn',
            ],
        ];
    }
}

if (!function_exists('medidata_mailer_env_map_for_profile')) {
    function medidata_mailer_env_map_for_profile(string $profile): array
    {
        if ($profile === 'auth') {
            return [
                'host' => 'MEDIDATA_SMTP_AUTH_HOST',
                'username' => 'MEDIDATA_SMTP_AUTH_USER',
                'password' => 'MEDIDATA_SMTP_AUTH_PASS',
                'port' => 'MEDIDATA_SMTP_AUTH_PORT',
                'secure' => 'MEDIDATA_SMTP_AUTH_SECURE',
                'from_email' => 'MEDIDATA_SMTP_AUTH_FROM',
                'from_name' => 'MEDIDATA_SMTP_AUTH_FROM_NAME',
                'reply_to_email' => 'MEDIDATA_SMTP_AUTH_REPLY',
                'reply_to_name' => 'MEDIDATA_SMTP_AUTH_REPLY_NAME',
                'debug_level' => 'MEDIDATA_SMTP_AUTH_DEBUG',
            ];
        }

        return [
            'host' => 'MEDIDATA_SMTP_HOST',
            'username' => 'MEDIDATA_SMTP_USER',
            'password' => 'MEDIDATA_SMTP_PASS',
            'port' => 'MEDIDATA_SMTP_PORT',
            'secure' => 'MEDIDATA_SMTP_SECURE',
            'from_email' => 'MEDIDATA_SMTP_FROM',
            'from_name' => 'MEDIDATA_SMTP_FROM_NAME',
            'reply_to_email' => 'MEDIDATA_SMTP_REPLY',
            'reply_to_name' => 'MEDIDATA_SMTP_REPLY_NAME',
            'debug_level' => 'MEDIDATA_SMTP_DEBUG',
        ];
    }
}

if (!function_exists('medidata_mailer_apply_env')) {
    function medidata_mailer_apply_env(array $cfg, string $profile): array
    {
        foreach (medidata_mailer_env_map_for_profile($profile) as $key => $env) {
            $val = getenv($env);
            if ($val !== false && $val !== '') {
                $cfg[$key] = ($key === 'port' || $key === 'debug_level') ? (int) $val : $val;
            }
        }
        return $cfg;
    }
}

if (!function_exists('medidata_mailer_load_secrets')) {
    function medidata_mailer_load_secrets(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $cache = [];
        $secretsFile = __DIR__ . '/medidata_mailer_secrets.php';
        if (is_readable($secretsFile)) {
            $override = require $secretsFile;
            if (is_array($override)) {
                $cache = $override;
            }
        }
        return $cache;
    }
}

if (!function_exists('medidata_mailer_config')) {
    /**
     * @param string $profile rrhh|auth
     */
    function medidata_mailer_config(string $profile = 'rrhh'): array
    {
        $profiles = medidata_mailer_profile_defaults();
        if (!isset($profiles[$profile])) {
            $profile = 'rrhh';
        }

        $cfg = $profiles[$profile];
        $cfg = medidata_mailer_apply_env($cfg, $profile);

        $secrets = medidata_mailer_load_secrets();

        // Formato nuevo: ['rrhh' => [...], 'auth' => [...]]
        if (isset($secrets[$profile]) && is_array($secrets[$profile])) {
            $cfg = array_merge($cfg, $secrets[$profile]);
        }

        // Compatibilidad: secrets plano (solo RRHH)
        if ($profile === 'rrhh') {
            $legacyKeys = ['host', 'username', 'password', 'port', 'secure', 'from_email', 'from_name', 'reply_to_email', 'reply_to_name', 'debug_level'];
            foreach ($legacyKeys as $key) {
                if (isset($secrets[$key]) && !isset($secrets['rrhh'])) {
                    $cfg[$key] = $secrets[$key];
                }
            }
        }

        return $cfg;
    }
}

if (!function_exists('medidata_mailer_auth_configured')) {
    function medidata_mailer_auth_configured(): bool
    {
        $cfg = medidata_mailer_config('auth');
        return trim((string) ($cfg['username'] ?? '')) !== ''
            && trim((string) ($cfg['password'] ?? '')) !== '';
    }
}
