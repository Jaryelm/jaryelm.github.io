<?php
/**
 * Rutas web del proyecto: producción (raíz del dominio) vs XAMPP (subcarpeta /MedicasaDATAUpdate2).
 *
 * Override en Apache: SetEnv MEDIDATA_WEB_BASE "/medi_data"
 */

if (!function_exists('medidata_web_base')) {
    function medidata_web_base(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }

        $env = getenv('MEDIDATA_WEB_BASE');
        if ($env !== false && $env !== '') {
            $base = rtrim(str_replace('\\', '/', $env), '/');
            return $base;
        }

        $appRoot = realpath(dirname(__DIR__, 2));
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

        if ($appRoot && $docRoot && strpos($appRoot, $docRoot) === 0) {
            $rel = substr($appRoot, strlen($docRoot));
            $base = rtrim(str_replace('\\', '/', $rel), '/');
            return $base;
        }

        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        if ($host === 'localhost' || strpos($host, '127.0.0.1') === 0) {
            $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
            if (preg_match('#^/([^/]+)/#', $script, $m)) {
                $base = '/' . $m[1];
                return $base;
            }
        }

        $base = '';
        return $base;
    }
}

if (!defined('MEDIDATA_WEB_BASE')) {
    define('MEDIDATA_WEB_BASE', medidata_web_base());
}

if (!function_exists('medidata_url')) {
    /**
     * @param string $path Ruta desde la raíz del sitio, p. ej. /backend/vendor/...
     */
    function medidata_url(string $path = ''): string
    {
        $path = '/' . ltrim(str_replace('\\', '/', $path), '/');
        $base = MEDIDATA_WEB_BASE;
        if ($base === '') {
            return $path;
        }
        return rtrim($base, '/') . $path;
    }
}

if (!function_exists('medidata_asset')) {
    function medidata_asset(string $path): string
    {
        return htmlspecialchars(medidata_url($path), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('medidata_rewrite_absolute_backend_paths')) {
    function medidata_rewrite_absolute_backend_paths(string $html): string
    {
        $base = MEDIDATA_WEB_BASE;
        if ($base === '' || strpos($html, $base . '/backend/') !== false) {
            return $html;
        }

        return str_replace(
            [
                '"/backend/',
                "'/backend/",
                '="/backend/',
                "='/backend/",
                '(/backend/',
            ],
            [
                '"' . $base . '/backend/',
                "'" . $base . "/backend/",
                '="' . $base . '/backend/',
                "='" . $base . "/backend/",
                '(' . $base . '/backend/',
            ],
            $html
        );
    }
}

if (!function_exists('medidata_begin_asset_path_buffer')) {
    function medidata_begin_asset_path_buffer(): void
    {
        static $started = false;
        if ($started || MEDIDATA_WEB_BASE === '' || PHP_SAPI === 'cli') {
            return;
        }
        $started = true;

        ob_start(static function (string $buffer): string {
            if (stripos($buffer, '<html') === false && stripos($buffer, '<!DOCTYPE') === false) {
                return $buffer;
            }
            return medidata_rewrite_absolute_backend_paths($buffer);
        });
    }
}

medidata_begin_asset_path_buffer();
