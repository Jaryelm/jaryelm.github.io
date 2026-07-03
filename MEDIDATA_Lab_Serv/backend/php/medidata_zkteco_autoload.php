<?php
/**
 * Autoload PSR-4 para el SDK locales en backend/sdk/zkteco/src (jmrashed/zkteco).
 */
declare(strict_types=1);

if (!defined('MEDIDATA_ZKTECO_AUTOLOAD_REGISTERED')) {
    define('MEDIDATA_ZKTECO_AUTOLOAD_REGISTERED', true);

    spl_autoload_register(static function (string $class): void {
        $prefix = 'Jmrashed\\Zkteco\\';
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) {
            return;
        }
        $relative = substr($class, $len);
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sdk'
            . DIRECTORY_SEPARATOR . 'zkteco' . DIRECTORY_SEPARATOR . 'src'
            . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
        if (is_readable($file)) {
            require_once $file;
        }
    });
}
