<?php
/**
 * Configuración del reloj biométrico (ZKTeco / compatible, UDP puerto 4370).
 *
 * Opcional: variables de entorno del sistema o del servidor web:
 *   MEDIDATA_RELOJ_IP
 *   MEDIDATA_RELOJ_PORT
 *   MEDIDATA_RELOJ_TIMEOUT  (segundos por recepción UDP; 3–45, predeterminado 12)
 *
 * @return array{ip: string, port: int, socket_timeout_sec: int}
 */
function medidata_reloj_biometrico_config(): array
{
    $ip = getenv('MEDIDATA_RELOJ_IP');
    if ($ip === false || trim($ip) === '') {
        $ip = '192.168.1.203';
    }

    $portRaw = getenv('MEDIDATA_RELOJ_PORT');
    $port = ($portRaw !== false && $portRaw !== '') ? (int) $portRaw : 4370;

    $toRaw = getenv('MEDIDATA_RELOJ_TIMEOUT');
    $timeoutSec = ($toRaw !== false && $toRaw !== '') ? (int) $toRaw : 12;
    if ($timeoutSec < 3) {
        $timeoutSec = 3;
    }
    if ($timeoutSec > 45) {
        $timeoutSec = 45;
    }

    return [
        'ip' => trim($ip),
        'port' => $port > 0 ? $port : 4370,
        'socket_timeout_sec' => $timeoutSec,
    ];
}
