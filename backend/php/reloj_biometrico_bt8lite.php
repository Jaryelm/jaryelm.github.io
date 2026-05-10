<?php
/**
 * Integración BT-8LITE OEM (TCP). Implementar aquí el protocolo/API según documentación del fabricante.
 */
declare(strict_types=1);

/**
 * Comprueba si se puede abrir una sesión TCP al reloj (solo conectividad).
 */
function medidata_reloj_bt8lite_tcp_reachable(array $cfg): bool
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
 * Obtiene marcas de asistencia desde el reloj.
 * TODO: sustituir por llamadas reales al SDK/API TCP del fabricante (formato de tramas según BT-8LITE).
 *
 * @return list<array{0: string, 1: string, 2: string, 3: string}>  uid texto, id numérico, estado, fecha/hora
 */
function medidata_reloj_bt8lite_get_attendance(array $cfg): array
{
    unset($cfg);
    return [];
}
