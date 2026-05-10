<?php
/**
 * Reloj biométrico BT-8LITE (OEM) — integración Medicasa.
 *
 * El equipo se atiende por TCP (puerto habitual 5005), no por el protocolo ZK/UDP de ZKTeco puro.
 * Variables de entorno opcionales:
 *   MEDIDATA_RELOJ_IP
 *   MEDIDATA_RELOJ_PORT
 *   MEDIDATA_RELOJ_TIMEOUT  (segundos para prueba TCP; 3–45, predeterminado 12)
 *
 * @return array{device: string, transport: string, ip: string, port: int, connect_timeout_sec: int}
 */
function medidata_reloj_biometrico_config(): array
{
    $ip = getenv('MEDIDATA_RELOJ_IP');
    if ($ip === false || trim($ip) === '') {
        $ip = '192.168.1.203';
    }

    $portRaw = getenv('MEDIDATA_RELOJ_PORT');
    $port = ($portRaw !== false && $portRaw !== '') ? (int) $portRaw : 5005;

    $toRaw = getenv('MEDIDATA_RELOJ_TIMEOUT');
    $timeoutSec = ($toRaw !== false && $toRaw !== '') ? (int) $toRaw : 12;
    if ($timeoutSec < 3) {
        $timeoutSec = 3;
    }
    if ($timeoutSec > 45) {
        $timeoutSec = 45;
    }

    return [
        'device' => 'bt-8lite',
        'transport' => 'tcp',
        'ip' => trim($ip),
        'port' => $port > 0 ? $port : 5005,
        'connect_timeout_sec' => $timeoutSec,
    ];
}
