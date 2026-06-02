<?php
/**
 * Reloj biométrico Medicasa — ZKTeco MB360 (protocolo ZK por UDP).
 *
 * IP/puerto según equipo (Ethernet): mismo puerto habitual 4370 para TCP visible en pantalla y para ZK/UDP.
 * La prueba TCP (fsockopen) es solo diagnóstico de red.
 *
 * Implementación técnica: SDK locales en backend/sdk/zkteco (jmrashed/zkteco).
 */
declare(strict_types=1);

<<<<<<< Updated upstream
=======
require_once __DIR__ . '/biometric_marcas_db.php';
>>>>>>> Stashed changes
require_once __DIR__ . '/medidata_zkteco_autoload.php';

use Jmrashed\Zkteco\Lib\ZKTeco;

/**
 * Verifica alcance TCP a IP:puerto (diagnóstico; no garantiza UDP ZK).
 */
function medidata_mb360_tcp_reachable(array $cfg): bool
{
    $ip = $cfg['ip'] ?? '';
    $port = (int) ($cfg['port'] ?? 0);
    $sec = (float) ($cfg['connect_timeout_sec'] ?? 10);
    if ($ip === '' || $port < 1) {
        return false;
    }
    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($ip, $port, $errno, $errstr, $sec);
    if ($fp !== false) {
        fclose($fp);
        return true;
    }
    return false;
}

/**
 * Diagnóstico técnico para mostrar en UI / CLI cuando falla ZK UDP.
 *
 * @return array{
 *     sockets_extension: bool,
 *     tcp_ok: bool,
 *     zk_udp_connect: bool,
 *     socket_errno: ?int,
 *     socket_strerror: string,
 *     recv_buffer_bytes: int
 * }
 */
function medidata_mb360_diagnostic_skeleton(array $cfg): array
{
    return [
        'sockets_extension' => extension_loaded('sockets'),
        'tcp_ok' => medidata_mb360_tcp_reachable($cfg),
        'zk_udp_connect' => false,
        'socket_errno' => null,
        'socket_strerror' => '',
        'recv_buffer_bytes' => 0,
    ];
}

/**
 * Crea cliente ZKTeco y ejecuta connect() una sola vez; devuelve la instancia o error + diag.
 *
 * @return array{zk: ZKTeco|null, error: ?string, diag: array<string,mixed>}
 */
function medidata_mb360_try_zk_session(array $cfg): array
{
    $diag = medidata_mb360_diagnostic_skeleton($cfg);

    if (!$diag['sockets_extension']) {
        return [
            'zk' => null,
            'error' => 'La extensión PHP «sockets» no está habilitada (necesaria para UDP ZK).',
            'diag' => $diag,
        ];
    }

    $ip = trim((string) ($cfg['ip'] ?? ''));
    $port = (int) ($cfg['port'] ?? 4370);
    if ($ip === '' || $port < 1) {
        return [
            'zk' => null,
            'error' => 'IP o puerto del MB360 no configurados.',
            'diag' => $diag,
        ];
    }

    try {
        $zk = new ZKTeco($ip, $port);
    } catch (\Throwable $e) {
        error_log('medidata_mb360_try_zk_session: ' . $e->getMessage());

        return [
            'zk' => null,
            'error' => 'No se pudo abrir socket ZK: ' . $e->getMessage(),
            'diag' => $diag,
        ];
    }

    $ok = $zk->connect();
    $diag['zk_udp_connect'] = $ok;
    $diag['recv_buffer_bytes'] = strlen((string) $zk->_data_recv);
    $errno = @socket_last_error($zk->_zkclient);
    $diag['socket_errno'] = is_int($errno) ? $errno : null;
    $diag['socket_strerror'] = is_int($errno) ? socket_strerror($errno) : '';

    if (!$ok) {
        return [
            'zk' => null,
            'error' => 'No fue posible completar el handshake del protocolo ZK (UDP) con el MB360 en '
                . $ip . ':' . $port
                . '. Abrir «Diagnóstico técnico» en esta página: distingue TCP (alcance al puerto) de la sesión ZK real.',
            'diag' => $diag,
        ];
    }

    return ['zk' => $zk, 'error' => null, 'diag' => $diag];
}

/**
 * Texto para IT cuando falla UDP ZK: prioriza equipo/configuración y software compatible;
 * menciona rutas/red solo como causas complementarias si TCP también falla.
 *
 * @param array<string,mixed> $diag Resultado típico de medidata_mb360_diagnostic_skeleton después de intento connect.
 */
function medidata_mb360_diagnostic_it_note_es(array $diag): string
{
    $tcp = !empty($diag['tcp_ok']);
    $udp = !empty($diag['zk_udp_connect']);

    if ($udp) {
        return '';
    }

    if ($tcp) {
        return 'Cuando TCP al puerto es OK pero no llega handshake ZK por UDP '
            . '(buffer de recepción vacío o timeout), el host alcanza el puerto pero el MB360 '
            . 'no devuelve un paquete ZK que esta librería interprete. En Medicasa revise en el equipo menú '
            . '«Comunicación»: Conexión a PC (clave deben conocer equipos/software compatibles — MediDATA aún '
            . 'no envía clave PC en el cliente ZK estándar), ADMS si no usan servidor central, firmware y reboot '
            . 'tras cambios Ethernet. Contraste con lectura mediante software oficial ZKTeco en la misma VLAN. '
            . 'Si en servidor Linux `tcpdump` muestra sólo UDP saliente y nunca respuesta del reloj, conviene validar primero contra ZKBio/ZKTime antes de asumir bloqueos de red externos.';
    }

    return 'Ni la prueba TCP ni el handshake ZK funcionaron bien: primera prioridad revisar '
        . 'IP, puerto (`reloj_biometrico_config` / variables de entorno) y rutas físicas hasta el MB360 '
        . 'antes de afinar causa ZKTeco/firmware.';
}

/**
 * Explicación breve en español para códigos de error de socket tras intento UDP.
 */
function medidata_mb360_socket_errno_hint_es(?int $errno): string
{
    if ($errno === null) {
        return '';
    }

    return match ($errno) {
        10060 => 'Código WSAETIMEDOUT (Windows): venció el tiempo de espera sin recibir un datagrama ZK válido. '
            . 'Si en el mismo diagnóstico «Prueba TCP» es OK en este host, causas típicas son configuración del MB360 '
            . '(clave en Conexión a PC, modo ADMS, firmware) incompatibilidad con el cliente UDP; la red ya demostró alcanzar el puerto TCP.',
        11 => '(Linux errno 11, EAGAIN) habitualmente tiempo de espera del socket en UDP sin datos del equipo — mismo enfoque de diagnóstico que un timeout.',
        10054 => 'Respuesta abrupta desde la red o el equipo (conexión restablecida).',
        10061 => 'Conexión rechazada en contextos TCP donde aplica ese protocolo.',
        default => '',
    };
}

/**
 * Descarga marcas del MB360 y las normaliza para la tabla MEDIDATA.
 *
 * @return array{
 *     records: list<array{0: string, 1: string, 2: string, 3: string}>,
 *     error: ?string,
 *     diag?: array{sockets_extension: bool, tcp_ok: bool, zk_udp_connect: bool, socket_errno: ?int, socket_strerror: string, recv_buffer_bytes: int}
 * }
 */
function medidata_mb360_pull_attendance(array $cfg): array
{
    @ini_set('max_execution_time', '90');
    set_time_limit(90);

    if (!extension_loaded('sockets')) {
        return [
            'records' => [],
            'error' => 'La extensión PHP «sockets» no está habilitada (necesaria para UDP ZK).',
            'diag' => medidata_mb360_diagnostic_skeleton($cfg),
        ];
    }

    $ip = trim((string) ($cfg['ip'] ?? ''));
    $port = (int) ($cfg['port'] ?? 4370);
    if ($ip === '' || $port < 1) {
        return [
            'records' => [],
            'error' => 'IP o puerto del MB360 no configurados.',
            'diag' => medidata_mb360_diagnostic_skeleton($cfg),
        ];
    }

    $zk = null;
    /** @var array<string,mixed>|null */
    $diagHold = null;
    try {
        $opened = medidata_mb360_try_zk_session($cfg);
        $diagHold = $opened['diag'];

        if ($opened['zk'] === null || $opened['error'] !== null) {
            return [
                'records' => [],
                'error' => $opened['error'],
                'diag' => $diagHold,
            ];
        }

        $zk = $opened['zk'];
        /** @var list<array<string, mixed>> $raw */
        $raw = $zk->getAttendanceWithUser();
        if (!is_array($raw)) {
            $raw = [];
        }

        $records = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $uid = isset($row['uid']) ? (int) $row['uid'] : 0;

            $userBlock = isset($row['user']) && is_array($row['user']) ? $row['user'] : null;
            $uidTxt = '';
            if ($userBlock !== null) {
                $uidTxt = trim((string) ($userBlock['userid'] ?? ''));
                if ($uidTxt === '') {
                    $uidTxt = trim((string) ($userBlock['name'] ?? ''));
                }
                if ($uidTxt === '') {
                    $uidTxt = trim((string) ($userBlock['cardno'] ?? ''));
                }
            }
            if ($uidTxt === '') {
                $uidTxt = trim((string) ($row['id'] ?? ''));
            }
            if ($uidTxt === '' && $uid > 0) {
                $uidTxt = '(uid ' . $uid . ')';
            }

            $state = isset($row['state_name'])
                ? (string) $row['state_name']
                : ('Estado ' . (string) ($row['state'] ?? '?'));

            $ts = isset($row['timestamp']) ? (string) $row['timestamp'] : '';

            $records[] = [$uidTxt, (string) $uid, $state, $ts];
        }

        usort($records, static function (array $a, array $b): int {
            return strcmp((string) $b[3], (string) $a[3]);
        });

        return ['records' => $records, 'error' => null, 'diag' => $diagHold];
    } catch (\Throwable $e) {
        error_log('medidata_mb360_pull_attendance: ' . $e->getMessage());
        return [
            'records' => [],
            'error' => 'Error al leer el MB360: ' . $e->getMessage(),
            'diag' => is_array($diagHold) ? $diagHold : medidata_mb360_diagnostic_skeleton($cfg),
        ];
    } finally {
        if ($zk instanceof ZKTeco) {
            try {
                @$zk->disconnect();
            } catch (\Throwable $_) {
                // ignore
            }
        }
    }
}

<<<<<<< Updated upstream
/**
 * Lectura de marcas sincronizadas desde el agente (tabla biometric_marcas).
 *
 * @return list<array{0:string,1:string,2:string,3:string}>
 */
function medidata_biometric_fetch_marcas_agent_db(PDO $connect, string $siteCode, int $limit = 2000): array
{
    $siteCode = trim($siteCode);
    if ($siteCode === '') {
        return [];
    }
    $siteCode = substr($siteCode, 0, 48);

    $limit = max(1, min(5000, $limit));

    try {
        $sql = 'SELECT uid_text, uid_numeric, estado, marca_datetime
                FROM biometric_marcas
                WHERE site_code = ?
                ORDER BY marca_datetime DESC
                LIMIT ' . $limit;

        $stmt = $connect->prepare($sql);
        $stmt->execute([$siteCode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $ts = $row['marca_datetime'] ?? '';

            $tsStr = '';
            if ($ts instanceof \DateTimeInterface) {
                $tsStr = $ts->format('Y-m-d H:i:s');
            } else {
                $tsStr = trim((string) $ts);
                $tsStr = preg_replace('/\.\d+$/', '', $tsStr);
            }

            $out[] = [
                (string) ($row['uid_text'] ?? ''),
                (string) ($row['uid_numeric'] ?? ''),
                (string) ($row['estado'] ?? ''),
                $tsStr,
            ];
        }

        return $out;
    } catch (\Throwable $e) {
        error_log('medidata_biometric_fetch_marcas_agent_db: ' . $e->getMessage());

        return [];
    }
}

=======
>>>>>>> Stashed changes
