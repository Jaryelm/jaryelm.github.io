<?php
/**
 * Diagnóstico rápido MB360 / librería ZK (solo consola): TCP + UDP connect desde la misma config que MediDATA.
 *
 * Uso desde la raíz del proyecto:
 *   C:\xampp\php\php.exe backend\php\zk_mb360_connect_diagnose.php
 *
 * IPs distintas a reloj_biometrico_config.php:
 *   C:\xampp\php\php.exe backend\php\zk_mb360_connect_diagnose.php 192.168.1.91 4370
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'Solo ejecutar desde línea de comandos (CLI).';
    exit(1);
}

require_once __DIR__ . '/reloj_biometrico_config.php';
require_once __DIR__ . '/reloj_biometrico_mb360.php';

$argvIp = isset($argv[1]) ? trim((string) $argv[1]) : '';
$argvPort = isset($argv[2]) ? (int) $argv[2] : 0;

$cfg = medidata_reloj_biometrico_config();
if ($argvIp !== '') {
    $cfg['ip'] = $argvIp;
}
if ($argvPort > 0) {
    $cfg['port'] = $argvPort;
}

$ip = $cfg['ip'];
$port = $cfg['port'];

echo "Destino {$ip}:{$port} (MB360 protocolo ZK por UDP puerto habitual 4370)\n";

$opened = medidata_mb360_try_zk_session($cfg);
$diag = $opened['diag'];

echo 'Extension PHP «sockets»: ' . (($diag['sockets_extension'] ?? false) ? 'sí' : 'no') . "\n";

echo 'Prueba TCP (fsockopen): ' . (($diag['tcp_ok'] ?? false) ? 'llegó al puerto' : 'NO llegó / rechazado / timeout') . "\n";

echo 'ZK UDP connect(): ' . (($diag['zk_udp_connect'] ?? false) ? 'true' : 'false') . "\n";

if (($diag['socket_errno'] ?? null) !== null) {
    $e = $diag['socket_errno'];
    $s = $diag['socket_strerror'] ?? '';
    echo "socket_last_error: {$e} — {$s}\n";
}

echo 'Octetos en buffer recv: ' . (int) ($diag['recv_buffer_bytes'] ?? 0) . "\n";

$hint = medidata_mb360_socket_errno_hint_es($diag['socket_errno'] ?? null);
if ($hint !== '') {
    echo 'Código errno: ' . $hint . "\n";
}

$itNote = medidata_mb360_diagnostic_it_note_es($diag);
if ($itNote !== '') {
    echo "\n--- Interpretación Medicasa IT ---\n" . $itNote . "\n";
}

if (($opened['zk'] ?? null) !== null && (int) ($diag['recv_buffer_bytes'] ?? 0) >= 8) {
    try {
        @$opened['zk']->disconnect();
    } catch (Throwable $_) {
    }
}

exit(($diag['zk_udp_connect'] ?? false) ? 0 : 3);
