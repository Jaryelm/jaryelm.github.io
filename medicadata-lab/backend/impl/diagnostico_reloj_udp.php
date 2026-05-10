<?php
/**
 * Diagnóstico: comprobar si hay respuesta UDP (protocolo ZK) al reloj.
 * Uso: abrir en el navegador la misma URL base que MEDIDATA (mismo Apache/PHP que relojbio.php).
 *
 * No sustituye un analizador de red; sirve para distinguir “PHP/sockets OK” de “sin respuesta UDP”.
 */
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../php/reloj_biometrico_config.php';

$cfg = medidata_reloj_biometrico_config();
$ip = $cfg['ip'];
$port = (int) $cfg['port'];
$timeout = min(8, max(3, (int) $cfg['socket_timeout_sec']));

echo "MEDIDATA — diagnóstico UDP / ZK\n";
echo str_repeat('=', 50) . "\n";
echo "Destino UDP: {$ip}:{$port}\n";
echo "Timeout recepción (esta prueba): {$timeout} s\n";
echo "extensión sockets: " . (extension_loaded('sockets') ? 'sí' : 'NO — active extension=sockets en php.ini') . "\n\n";

echo "NOTA: Test-NetConnection -Port 4370 en PowerShell prueba TCP, no UDP.\n";
echo "      Una prueba TCP exitosa NO confirma UDP hacia el reloj.\n\n";

if (!extension_loaded('sockets')) {
    exit(1);
}

require_once __DIR__ . '/../sdk/php_zklib-master/load_zklib.php';

$t0 = microtime(true);
$zk = new ZKLib($ip, $port, $timeout);
$ok = $zk->connect();
$ms = (int) round((microtime(true) - $t0) * 1000);

if ($ok) {
    echo "RESULTADO: respuesta válida al comando ZK de conexión (UDP + protocolo).\n";
    echo "Tiempo hasta respuesta: {$ms} ms\n";
    try {
        $zk->disconnect();
    } catch (Throwable $e) {
        /* ignore */
    }
} else {
    echo "RESULTADO: sin respuesta ZK válida en {$timeout} s.\n\n";
    echo "Posibles causas (orden típico de comprobación):\n";
    echo "  1) La ruta de red o la VPN no deja pasar UDP hacia {$ip}:{$port}.\n";
    echo "  2) Firewall de Windows bloquea UDP saliente de httpd.exe / PHP.\n";
    echo "  3) IP/puerto del reloj incorrectos o reloj apagado/desconectado.\n\n";
    echo "Cómo confirmar UDP a nivel de paquetes (recomendado):\n";
    echo "  • Wireshark: filtro  udp.port == {$port}  en la interfaz de la VPN.\n";
    echo "    Debe verse datagrama de su PC al reloj y respuesta del reloj a su PC.\n";
    echo "  • Si solo salen peticiones y nunca respuestas: bloqueo o ruta UDP.\n";
}
