#!/usr/bin/env php
<?php
/**
 * Auditoría del servidor físico de sede (LAN 192.168.x + agente → MediDATA).
 *
 * Uso en Ubuntu (192.168.1.102):
 *   set -a; . /etc/medicasa-biometric-agent.env; set +a
 *   php /opt/MedicasaDATAUpdate2/scripts/biometric_agent_linux/server_audit.php
 *
 * Opciones:
 *   --env-file=/ruta/al.env     Cargar variables antes de auditar
 *   --skip-pull                 No descargar marcas del reloj (solo red + ingest GET)
 *   --run-agent                 Ejecutar run_once.php al final (envía marcas reales a producción)
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Solo CLI.\n");
    exit(1);
}

$opts = getopt('', ['env-file:', 'skip-pull', 'run-agent']);
$envFile = isset($opts['env-file']) ? (string) $opts['env-file'] : '/etc/medicasa-biometric-agent.env';
$skipPull = array_key_exists('skip-pull', $opts);
$runAgent = array_key_exists('run-agent', $opts);

function audit_line(string $label, string $value, bool $ok = true): void
{
    $tag = $ok ? '[OK]' : '[FAIL]';
    echo $tag . ' ' . $label . ': ' . $value . "\n";
}

function audit_section(string $title): void
{
    echo "\n=== " . $title . " ===\n";
}

function load_env_file(string $path): void
{
    if (!is_readable($path)) {
        audit_line('Archivo env', $path . ' (no legible, se usan variables ya exportadas)', false);
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
        $val = trim((string) $m[2], "\"' \t");
        if (getenv($key) === false || getenv($key) === '') {
            putenv($key . '=' . $val);
            $_ENV[$key] = $val;
        }
    }
    audit_line('Archivo env cargado', $path);
}

audit_section('Host y red local');
$hostname = (string) gethostname();
audit_line('Hostname', $hostname);
$lanIp = trim((string) shell_exec("hostname -I 2>/dev/null | awk '{print $1}'") ?: '');
if ($lanIp === '') {
    $lanIp = '(no detectada — hostname -I)';
}
audit_line('IP LAN principal', $lanIp);

load_env_file($envFile);

$repo = rtrim((string) (getenv('MEDIDATA_AGENT_REPO_ROOT') ?: ''), '/');
if ($repo === '') {
    $repo = dirname(__DIR__, 2);
    putenv('MEDIDATA_AGENT_REPO_ROOT=' . $repo);
}
audit_line('MEDIDATA_AGENT_REPO_ROOT', $repo, is_dir($repo . '/backend/php'));

$ingestUrl = trim((string) (getenv('MEDIDATA_AGENT_INGEST_URL') ?: ''));
$secret = (string) (getenv('MEDIDATA_AGENT_INGEST_SECRET') ?: (getenv('MEDIDATA_AGENT_SECRET') ?: ''));
$siteCode = trim((string) (getenv('MEDIDATA_AGENT_SITE_CODE') ?: ''));

require_once $repo . '/backend/php/reloj_biometrico_config.php';
require_once $repo . '/backend/php/reloj_biometrico_mb360.php';

$cfg = medidata_reloj_biometrico_config();
$relojIp = $cfg['ip'];
$relojPort = (int) $cfg['port'];

audit_section('Variables del agente');
audit_line('MEDIDATA_AGENT_INGEST_URL', $ingestUrl !== '' ? $ingestUrl : '(vacía)', $ingestUrl !== '');
$secretOk = strlen($secret) >= 24;
audit_line('MEDIDATA_AGENT_INGEST_SECRET', $secretOk ? ('definida (' . strlen($secret) . ' chars)') : '(falta o < 24 chars)', $secretOk);
audit_line('MEDIDATA_AGENT_SITE_CODE', $siteCode !== '' ? $siteCode : '(vacío)', $siteCode !== '');
audit_line('Reloj ZK destino', $relojIp . ':' . $relojPort);

audit_section('PHP CLI');
audit_line('Versión PHP', PHP_VERSION);
$sockets = extension_loaded('sockets');
$curl = extension_loaded('curl');
audit_line('Extensión sockets', $sockets ? 'sí' : 'NO — ejecutar: sudo phpenmod sockets', $sockets);
audit_line('Extensión curl', $curl ? 'sí' : 'NO', $curl);

audit_section('Alcance al reloj (ICMP + TCP + ZK)');
$pingCmd = PHP_OS_FAMILY === 'Windows'
    ? 'ping -n 1 -w 3000 ' . escapeshellarg($relojIp)
    : 'ping -c 1 -W 2 ' . escapeshellarg($relojIp) . ' 2>&1';
$pingOut = shell_exec($pingCmd) ?? '';
$pingOk = stripos($pingOut, 'ttl=') !== false
    || stripos($pingOut, 'TTL=') !== false
    || stripos($pingOut, 'tiempo=') !== false
    || stripos($pingOut, 'time=') !== false;
audit_line('PING ' . $relojIp, $pingOk ? 'responde' : 'sin respuesta (ICMP puede estar bloqueado)', $pingOk);

$tcpOk = medidata_mb360_tcp_reachable($cfg);
audit_line('TCP puerto ' . $relojPort, $tcpOk ? 'alcance OK (fsockopen)' : 'sin respuesta', $tcpOk);

$opened = medidata_mb360_try_zk_session($cfg);
$diag = $opened['diag'];
$zkOk = !empty($diag['zk_udp_connect']);
audit_line('ZK UDP connect()', $zkOk ? 'OK' : 'falló', $zkOk);
audit_line('Bytes recv buffer', (string) ($diag['recv_buffer_bytes'] ?? 0));
if (!$zkOk) {
    $hint = medidata_mb360_socket_errno_hint_es($diag['socket_errno'] ?? null);
    if ($hint !== '') {
        echo '    errno: ' . $hint . "\n";
    }
    $it = medidata_mb360_diagnostic_it_note_es($diag);
    if ($it !== '') {
        echo '    IT: ' . $it . "\n";
    }
}
if ($opened['zk'] instanceof \Jmrashed\Zkteco\Lib\ZKTeco) {
    try {
        @$opened['zk']->disconnect();
    } catch (Throwable $_) {
    }
}

if (!$skipPull && $zkOk) {
    audit_section('Pull de marcas (misma lógica que run_once.php)');
    $pull = medidata_mb360_pull_attendance($cfg);
    if (($pull['error'] ?? null) !== null && $pull['error'] !== '') {
        audit_line('Pull', (string) $pull['error'], false);
    } else {
        $n = count($pull['records'] ?? []);
        audit_line('Registros obtenidos', (string) $n, $n > 0);
        if ($n > 0) {
            $sample = $pull['records'][0];
            echo '    Ejemplo primera fila: ' . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "    El reloj respondió ZK pero la lista está vacía (sin marcas almacenadas o timeout en recData).\n";
        }
    }
} elseif ($skipPull) {
    audit_line('Pull marcas', 'omitido (--skip-pull)', true);
}

audit_section('API ingest MediDATA (GET ready)');
if ($ingestUrl === '') {
    audit_line('Ingest', 'URL no configurada', false);
} elseif (!$curl) {
    audit_line('Ingest', 'curl no disponible', false);
} else {
    $verifyEnv = getenv('MEDIDATA_AGENT_VERIFY_SSL');
    $verifySsl = ($verifyEnv === false || $verifyEnv === '')
        ? true
        : filter_var($verifyEnv, FILTER_VALIDATE_BOOLEAN);
    $ch = curl_init($ingestUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ]);
    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);
    if ($errno !== 0) {
        audit_line('GET ingest', 'cURL errno ' . $errno, false);
    } else {
        audit_line('GET ingest HTTP', (string) $http, $http >= 200 && $http < 300);
        $json = json_decode((string) $body, true);
        if (is_array($json)) {
            $ready = !empty($json['ready']);
            audit_line('ready (secreto en servidor web)', $ready ? 'true' : 'false', $ready);
            if (!$ready) {
                echo "    → En medidata.medicasa.hn falta MEDIDATA_AGENT_INGEST_SECRET (Apache SetEnv).\n";
            }
        } else {
            echo '    Respuesta: ' . substr((string) $body, 0, 200) . "\n";
        }
    }
}

audit_section('Cron y log (solo Linux)');
if (PHP_OS_FAMILY !== 'Windows') {
    $cronRoot = trim((string) shell_exec('sudo crontab -l 2>/dev/null') ?: '');
    $cronUser = trim((string) shell_exec('crontab -l 2>/dev/null') ?: '');
    $hasCron = stripos($cronRoot . $cronUser, 'run_once.php') !== false
        || stripos($cronRoot . $cronUser, 'biometric_agent') !== false;
    audit_line('Crontab con run_once.php', $hasCron ? 'encontrado' : 'NO encontrado — ver GUIA agente C5', $hasCron);
    if ($cronRoot !== '') {
        echo "    root crontab (extracto):\n";
        foreach (preg_split('/\R/', $cronRoot) as $line) {
            if (stripos($line, 'biometric') !== false || stripos($line, 'run_once') !== false) {
                echo '      ' . $line . "\n";
            }
        }
    }
    $logPath = '/var/log/medicasa-biometric-agent.log';
    if (is_readable($logPath)) {
        $tail = trim((string) shell_exec('tail -n 5 ' . escapeshellarg($logPath) . ' 2>/dev/null') ?: '');
        audit_line('Últimas líneas log', $logPath);
        echo $tail !== '' ? "    " . str_replace("\n", "\n    ", $tail) . "\n" : "    (vacío)\n";
    } else {
        audit_line('Log agente', $logPath . ' (aún no existe)', false);
    }
} else {
    echo "    Omitido en Windows.\n";
}

if ($runAgent) {
    audit_section('Ejecutar run_once.php (envío real a producción)');
    $runScript = $repo . '/scripts/biometric_agent_linux/run_once.php';
    if (!is_readable($runScript)) {
        audit_line('run_once', 'no encontrado', false);
    } else {
        passthru(PHP_BINARY . ' ' . escapeshellarg($runScript), $code);
        audit_line('Exit code run_once', (string) $code, $code === 0);
    }
}

audit_section('Resumen');
$blockers = [];
if (!$sockets) {
    $blockers[] = 'Habilitar extensión PHP sockets';
}
if (!$zkOk) {
    $blockers[] = 'Corregir handshake ZK con MB360 (IP 192.168.1.91, clave PC, ADMS, firmware)';
}
if ($ingestUrl === '' || !$secretOk || $siteCode === '') {
    $blockers[] = 'Completar /etc/medicasa-biometric-agent.env';
}
if ($blockers === []) {
    echo "Sin bloqueadores críticos detectados en esta auditoría.\n";
    echo "Siguiente: --run-agent para una corrida real, o revisar cron + biometric_marcas en producción.\n";
} else {
    echo "Bloqueadores:\n";
    foreach ($blockers as $b) {
        echo "  - " . $b . "\n";
    }
}

exit($zkOk && $sockets ? 0 : 2);
