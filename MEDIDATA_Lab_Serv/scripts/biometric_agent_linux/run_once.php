#!/usr/bin/env php
<?php
/**
 * Agente: ZK pull local al MB360 y envío JSON al endpoint central MediDATA.
 *
 * Requisitos en el servidor físico (Ubuntu):
 *   - PHP CLI 8+ con extensión sockets
 *   - Copia o rsync del proyecto (al menos backend/php + backend/sdk/zkteco)
 *
 * Variables de entorno obligatorias:
 *   MEDIDATA_AGENT_REPO_ROOT     Ruta absoluta al repo (directorio que contiene /backend)
 *   MEDIDATA_AGENT_INGEST_URL    URL HTTPS del ingest, ej. https://tudominio.com/MedicasaDATAUpdate2/backend/api/agent_biometric_ingest.php
 *   MEDIDATA_AGENT_INGEST_SECRET   Mismo valor que en el servidor web (o MEDIDATA_AGENT_SECRET legado).
 *   MEDIDATA_AGENT_SITE_CODE     Ej. Sucursal_1
 *
 * Opcionales (reloj en esta sede):
 *   MEDIDATA_RELOJ_IP            (default en reloj_biometrico_config si no se pone)
 *   MEDIDATA_RELOJ_PORT
 *   MEDIDATA_AGENT_DEVICE_SERIAL
 *   MEDIDATA_AGENT_VERIFY_SSL    0 para desactivar verificación SSL (solo pruebas)
 *
 * Si el hosting devuelve HTTP 409 (WAF humans_*), usar en su lugar:
 *   scripts/biometric_agent_linux/run_once_db.php  (MySQL remoto; ver backend/docs/INGESTA_MYSQL_DESDE_SEDE.md)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$repo = rtrim((string) (getenv('MEDIDATA_AGENT_REPO_ROOT') ?: ''), '/');
if ($repo === '' || !is_dir($repo . '/backend/php')) {
    fwrite(STDERR, "Defina MEDIDATA_AGENT_REPO_ROOT apuntando al proyecto (debe existir backend/php).\n");
    exit(1);
}

$ingestUrl = trim((string) (getenv('MEDIDATA_AGENT_INGEST_URL') ?: ''));
// Mismo secreto que MEDIDATA_AGENT_INGEST_SECRET en el servidor web (nombre unificado opcional MEDIDATA_AGENT_SECRET compat).
$secret = (string) (getenv('MEDIDATA_AGENT_INGEST_SECRET') ?: (getenv('MEDIDATA_AGENT_SECRET') ?: ''));
$siteCode = trim((string) (getenv('MEDIDATA_AGENT_SITE_CODE') ?: ''));

if ($ingestUrl === '' || $secret === '' || $siteCode === '') {
    fwrite(STDERR, "Faltan MEDIDATA_AGENT_INGEST_URL, MEDIDATA_AGENT_INGEST_SECRET (o MEDIDATA_AGENT_SECRET) o MEDIDATA_AGENT_SITE_CODE.\n");
    exit(1);
}

require_once $repo . '/backend/php/reloj_biometrico_config.php';
require_once $repo . '/backend/php/reloj_biometrico_mb360.php';

$cfg = medidata_reloj_biometrico_config();
$pull = medidata_mb360_pull_attendance($cfg);

if (($pull['error'] ?? null) !== null && $pull['error'] !== '') {
    fwrite(STDERR, 'Pull ZK error: ' . $pull['error'] . "\n");
    exit(2);
}

$records = $pull['records'] ?? [];
$recordCount = count($records);
if ($recordCount === 0) {
    echo date('Y-m-d H:i:s') . " Sin marcas en esta corrida (pull ZK OK, lista vacía en el reloj).\n";
    exit(0);
}

echo date('Y-m-d H:i:s') . " Marcas leídas del MB360: {$recordCount}\n";

$payload = [
    'site_code' => $siteCode,
    'device_serial' => trim((string) (getenv('MEDIDATA_AGENT_DEVICE_SERIAL') ?: '')) ?: null,
    'records' => $records,
];

$json = json_encode($payload, JSON_UNESCAPED_UNICODE);
if ($json === false) {
    fwrite(STDERR, "No se pudo codificar JSON.\n");
    exit(3);
}

$verifyEnv = getenv('MEDIDATA_AGENT_VERIFY_SSL');
$verifySslPeer = ($verifyEnv === false || $verifyEnv === '')
    ? true
    : filter_var($verifyEnv, FILTER_VALIDATE_BOOLEAN);

$ch = curl_init($ingestUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json; charset=utf-8',
        'Authorization: Bearer ' . $secret,
        'X-MEDIDATA-AGENT-TOKEN: ' . $secret,
        'User-Agent: MediDATA-Biometric-Agent/1.0',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => $json,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => $verifySslPeer,
    CURLOPT_SSL_VERIFYHOST => $verifySslPeer ? 2 : 0,
]);

$body = curl_exec($ch);
$errno = curl_errno($ch);
$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

if ($errno !== 0) {
    fwrite(STDERR, 'cURL error: ' . $errno . "\n");
    exit(4);
}

echo $body . "\n";

if ($http < 200 || $http >= 300) {
    fwrite(STDERR, "HTTP $http\n");
    $bodyStr = (string) $body;
    if (str_contains($bodyStr, 'humans_')) {
        fwrite(STDERR, "El hosting bloqueó el POST (anti-bot). Use run_once_db.php + Remote MySQL en cPanel.\n");
        fwrite(STDERR, "Ver: backend/docs/INGESTA_MYSQL_DESDE_SEDE.md\n");
    } else {
        fwrite(STDERR, substr($bodyStr, 0, 200) . "\n");
    }
    exit(5);
}

$resp = json_decode((string) $body, true);
if (isset($resp['success']) && $resp['success'] === true) {
    exit(0);
}

exit(6);
