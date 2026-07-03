<?php
declare(strict_types=1);

/**
 * Carga opcionalmente variables MEDIDATA_* desde
 * backend/php/biometric_ingest_secret.local.env (gitignored — ver .example).
 *
 * Variables permitidas en ese archivo:
 * - MEDIDATA_AGENT_INGEST_SECRET (solo si getenv no tiene uno ≥24 caracteres).
 * - MEDIDATA_RELJO_DB_SITE — sitio ej. Sucursal_1 para fallback en UI relojbio.php.
 *
 * Orden:
 * - No pisar getenv ya definido desde Apache/systemd (solo rellena vacíos donde aplica secret).
 */
function medidata_lab_opt_env_from_local_file(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $path = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'php' . DIRECTORY_SEPARATOR . 'biometric_ingest_secret.local.env';

    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!preg_match('/^\s*([A-Za-z0-9_]+)\s*=\s*(.*?)\s*$/', $line, $m)) {
            continue;
        }

        $key = (string) $m[1];
        $raw = trim((string) $m[2]);

        match ($key) {
            'MEDIDATA_AGENT_INGEST_SECRET' => (function () use ($raw): void {
                $cur = getenv('MEDIDATA_AGENT_INGEST_SECRET');
                if (is_string($cur) && strlen($cur) >= 24) {
                    return;
                }
                $val = trim($raw, "\"' \t");
                if ($val !== '' && strlen($val) >= 24) {
                    putenv('MEDIDATA_AGENT_INGEST_SECRET=' . $val);
                }
            })(),
            'MEDIDATA_RELJO_DB_SITE' => (function () use ($raw): void {
                if ($raw === '') {
                    return;
                }
                if (strlen((string) getenv('MEDIDATA_RELJO_DB_SITE')) > 0) {
                    return;
                }
                $val = trim($raw, "\"' \t");
                if ($val !== '') {
                    putenv('MEDIDATA_RELJO_DB_SITE=' . $val);
                }
            })(),
            default => null,
        };
    }
}

/**
 * Solo secreto ingest (retrocompat llamada desde agent_biometric_ingest.php).
 */
function medidata_biometric_bootstrap_agent_secret(): void
{
    medidata_lab_opt_env_from_local_file();
}
