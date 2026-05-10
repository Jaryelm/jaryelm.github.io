<?php
/**
 * Prueba de conexión con jmrashed/zkteco (Composer).
 * Instalación: desde backend/sdk/zkteco ejecutar: php composer.phar install
 * (o composer install si tiene Composer global).
 */
require_once __DIR__ . '/../sdk/zkteco/vendor/autoload.php';
require_once __DIR__ . '/../php/reloj_biometrico_config.php';

use Jmrashed\Zkteco\Lib\ZKTeco;

header('Content-Type: text/plain; charset=utf-8');

$cfg = medidata_reloj_biometrico_config();
$zk = new ZKTeco($cfg['ip'], (int) $cfg['port']);

$connect = $zk->connect();

echo ($connect ? 'Se ha logrado conectar con el biométrico.' : 'No se ha logrado conectar.')
    . "\n";

if ($connect) {
    try {
        echo 'Versión FM: ' . $zk->fmVersion() . "\n";
    } catch (Exception $ex) {
        echo 'Error al leer versión: ' . $ex->getMessage() . "\n";
    } finally {
        $zk->disconnect();
    }
}
