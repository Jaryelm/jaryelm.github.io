<?php
declare(strict_types=1);

/**
 * Carga variables KEY=value para el agente CLI (sin depender solo de `source` en shell).
 */
function medidata_biometric_agent_load_env_file(string $path, bool $onlyIfUnset = true): void
{
    if (!is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        if ($key === '') {
            continue;
        }
        if ($onlyIfUnset && getenv($key) !== false) {
            continue;
        }
        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
    }
}

/**
 * @param string|null $repoRoot Ruta MedicasaDATAUpdate2; si null, se infiere desde este archivo.
 */
function medidata_biometric_agent_bootstrap_env(?string $repoRoot = null): void
{
    if ($repoRoot === null || $repoRoot === '') {
        $repoRoot = realpath(dirname(__DIR__, 2)) ?: '';
    }
    $repoRoot = rtrim((string) $repoRoot, '/');

    medidata_biometric_agent_load_env_file('/etc/medicasa-biometric-agent.env', true);

    if ($repoRoot !== '') {
        medidata_biometric_agent_load_env_file(
            $repoRoot . '/scripts/biometric_agent_linux/medicasa-biometric-agent.env.sede',
            true
        );
    }
}
