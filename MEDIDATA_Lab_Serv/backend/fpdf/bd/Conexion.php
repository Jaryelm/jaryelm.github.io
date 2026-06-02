<?php

$dbLocal = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'medic9ue_medi_data',
];

$dbProduccionRemoto = [
    'host' => '192.168.176.2',
    'user' => 'medic9ue_moisesc',
    'pass' => 'Mrecords%7',
    'name' => 'medic9ue_medi_data',
];

$dbProduccionHosting = $dbProduccionRemoto;
$dbProduccionHosting['host'] = '192.168.176.2';

$httpHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
$esEntornoLocal = ($httpHost === '')
    || $httpHost === 'localhost'
    || strpos($httpHost, '127.0.0.1') === 0;

/* PHP en el mismo cPanel que MySQL: localhost evita conexiones por IP pública (menos 1040). */
$esWebProduccionEnHosting = !$esEntornoLocal
    && (strpos($httpHost, 'medicasa.hn') !== false || strpos($httpHost, 'medic9ue') !== false);

$dbCfg = $esEntornoLocal
    ? $dbLocal
    : ($esWebProduccionEnHosting ? $dbProduccionHosting : $dbProduccionRemoto);

if (!defined('dbhost')) {
    define('dbhost', $dbCfg['host']);
}
if (!defined('dbuser')) {
    define('dbuser', $dbCfg['user']);
}
if (!defined('dbpass')) {
    define('dbpass', $dbCfg['pass']);
}
if (!defined('dbname')) {
    define('dbname', $dbCfg['name']);
}

/**
 * Una sola conexión PDO por proceso PHP (una por petición FPM típica).
 * Varios include/require deben repetir menos trabajo y no deben crear otro PDO.
 */
$pdoReuseKey = '__MEDIDATA_PDO_SINGLETON__';
if (!empty($GLOBALS[$pdoReuseKey]) && $GLOBALS[$pdoReuseKey] instanceof PDO) {
    $connect = $GLOBALS[$pdoReuseKey];
    return;
}

if (isset($connect) && $connect instanceof PDO) {
    $GLOBALS[$pdoReuseKey] = $connect;
    return;
}

try {
    $dsn = 'mysql:host=' . dbhost . ';dbname=' . dbname . ';charset=utf8mb4';

    $connect = new PDO(
        $dsn,
        dbuser,
        dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            /* En hosting compartido con límite bajo de conexiones MySQL evitar ATTR_PERSISTENT. */
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            /* Menos trabajo en PHP: preparativos nativos donde el servidor lo permite. */
            PDO::ATTR_EMULATE_PREPARES => false,
            /* Evita segundo round-trip "SET NAMES"; alinea cliente con UTF-8 completo. */
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ]
    );
    $GLOBALS[$pdoReuseKey] = $connect;
} catch (PDOException $e) {
    error_log('Conexion.php PDO: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(503);
    }
    throw $e;
}
