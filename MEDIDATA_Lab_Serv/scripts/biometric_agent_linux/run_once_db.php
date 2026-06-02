#!/usr/bin/env php
<?php
/**
 * Agente biométrico vía MySQL remoto (evita WAF HTTP 409 en hosting compartido).
 *
 * Requiere en cPanel → Remote MySQL®: permitir host 201.190.11.6 (IP pública sede).
 *
 * Variables en /etc/medicasa-biometric-agent.env:
 *   MEDIDATA_AGENT_REPO_ROOT, MEDIDATA_AGENT_SITE_CODE
 *   MEDIDATA_AGENT_DB_HOST=127.0.0.1
 *   MEDIDATA_AGENT_DB_NAME=medic9ue_medi_data
 *   MEDIDATA_AGENT_DB_USER=medic9ue_moisesc
 *   MEDIDATA_AGENT_DB_PASS=...
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$repoGuess = realpath(dirname(__DIR__, 2)) ?: dirname(__DIR__, 2);
require_once $repoGuess . '/backend/php/biometric_agent_env_bootstrap.php';
medidata_biometric_agent_bootstrap_env($repoGuess);

$repo = rtrim((string) (getenv('MEDIDATA_AGENT_REPO_ROOT') ?: $repoGuess), '/');
if ($repo === '' || !is_dir($repo . '/backend/php')) {
    fwrite(STDERR, "Defina MEDIDATA_AGENT_REPO_ROOT.\n");
    exit(1);
}

$siteCode = trim((string) (getenv('MEDIDATA_AGENT_SITE_CODE') ?: ''));
if ($siteCode === '') {
    fwrite(STDERR, "Falta MEDIDATA_AGENT_SITE_CODE.\n");
    exit(1);
}

require_once $repo . '/backend/php/reloj_biometrico_config.php';
require_once $repo . '/backend/php/reloj_biometrico_mb360.php';
require_once $repo . '/backend/php/biometric_agent_push.php';

$cfg = medidata_reloj_biometrico_config();
$pull = medidata_mb360_pull_attendance($cfg);

if (($pull['error'] ?? null) !== null && $pull['error'] !== '') {
    fwrite(STDERR, 'Pull ZK error: ' . $pull['error'] . "\n");
    exit(2);
}

$records = $pull['records'] ?? [];
$recordCount = count($records);
if ($recordCount === 0) {
    echo date('Y-m-d H:i:s') . " Sin marcas en esta corrida.\n";
    exit(0);
}

echo date('Y-m-d H:i:s') . " Marcas leídas del MB360: {$recordCount}\n";

try {
    $pdo = medidata_biometric_agent_pdo_from_env();
} catch (Throwable $e) {
    fwrite(STDERR, 'MySQL remoto: ' . $e->getMessage() . "\n");
    fwrite(STDERR, "Revise cPanel → Remote MySQL y MEDIDATA_AGENT_DB_* en el .env.\n");
    exit(7);
}

$deviceSerial = trim((string) (getenv('MEDIDATA_AGENT_DEVICE_SERIAL') ?: '')) ?: null;

try {
    $result = medidata_biometric_push_records($pdo, $siteCode, $records, $deviceSerial);
} catch (Throwable $e) {
    fwrite(STDERR, 'Insert: ' . $e->getMessage() . "\n");
    exit(8);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE) . "\n";

$pdo = null;

exit(($result['success'] ?? false) ? 0 : 6);
