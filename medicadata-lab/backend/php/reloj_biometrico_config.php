<?php
/**
 * Reloj biométrico ZKTeco MB360 (Ethernet; protocolo ZK).
 *
 * Mismo puerto habitual (4370) que muestra la pantalla del equipo para TCP y para comunicación ZK por UDP.
 *
 * Lógica de lectura: backend/php/reloj_biometrico_mb360.php y SDK backend/sdk/zkteco/.
 *
 * Opcional: prueba TCP (fsockopen) solo como diagnóstico de rutas/red.
 *
 * Variables de entorno opcionales:
 *   MEDIDATA_RELOJ_IP
 *   MEDIDATA_RELOJ_PORT
 *   MEDIDATA_RELOJ_TIMEOUT  (solo prueba TCP, segundos 3–45, predeterminado 12)
 *   MEDIDATA_ZK_RECV_SEC   (timeouts socket_recv UDP ZK, 1–30 s, predeterminado en SDK: 8)
 *
 * @return array{device: string, transport: string, ip: string, port: int, connect_timeout_sec: int}
 */
function medidata_reloj_biometrico_config(): array
{
    $ip = getenv('MEDIDATA_RELOJ_IP');
    if ($ip === false || trim($ip) === '') {
<<<<<<< Updated upstream
        $ip = '192.168.1.201';
=======
        $ip = '192.168.1.91';
>>>>>>> Stashed changes
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
        'device' => 'MB360',
        'transport' => 'zk-udp-tcp-port',
        'ip' => trim($ip),
        'port' => $port > 0 ? $port : 4370,
        'connect_timeout_sec' => $timeoutSec,
    ];
}
