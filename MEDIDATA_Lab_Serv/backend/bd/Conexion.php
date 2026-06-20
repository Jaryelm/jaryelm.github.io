<?php

$dbLocal = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'hpk7pdwM4',
    'name' => 'medic9ue_medi_data',
];

$dbProduccionRemoto = [
    'host' => '192.168.176.2',
    'user' => 'dev',
    'pass' => 'Mrecords7',
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

require_once __DIR__ . '/medidata_paths.php';

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
$pdoRrhhKey = '__MEDIDATA_PDO_RRHH__';
$pdoPostulacionesKey = '__MEDIDATA_PDO_POSTULACIONES__';

if (!empty($GLOBALS[$pdoReuseKey]) && $GLOBALS[$pdoReuseKey] instanceof PDO) {
    $connect = $GLOBALS[$pdoReuseKey];
    if (!empty($GLOBALS[$pdoRrhhKey]) && $GLOBALS[$pdoRrhhKey] instanceof PDO) {
        $connect_rrhh = $GLOBALS[$pdoRrhhKey];
    } elseif (array_key_exists($pdoRrhhKey, $GLOBALS)) {
        $connect_rrhh = $GLOBALS[$pdoRrhhKey];
    }
    if (!empty($GLOBALS[$pdoPostulacionesKey]) && $GLOBALS[$pdoPostulacionesKey] instanceof PDO) {
        $connect_postulaciones = $GLOBALS[$pdoPostulacionesKey];
    } elseif (array_key_exists($pdoPostulacionesKey, $GLOBALS)) {
        $connect_postulaciones = $GLOBALS[$pdoPostulacionesKey];
    }
    return;
}

if (isset($connect) && $connect instanceof PDO) {
    $GLOBALS[$pdoReuseKey] = $connect;
    if (!empty($GLOBALS[$pdoRrhhKey]) && $GLOBALS[$pdoRrhhKey] instanceof PDO) {
        $connect_rrhh = $GLOBALS[$pdoRrhhKey];
    } elseif (array_key_exists($pdoRrhhKey, $GLOBALS)) {
        $connect_rrhh = $GLOBALS[$pdoRrhhKey];
    }
    if (!empty($GLOBALS[$pdoPostulacionesKey]) && $GLOBALS[$pdoPostulacionesKey] instanceof PDO) {
        $connect_postulaciones = $GLOBALS[$pdoPostulacionesKey];
    } elseif (array_key_exists($pdoPostulacionesKey, $GLOBALS)) {
        $connect_postulaciones = $GLOBALS[$pdoPostulacionesKey];
    }
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

/**
 * Conexión secundaria RRHH (entrevistas / reclutamiento).
 * Si la BD aún no existe en local, no tumba el resto de la aplicación.
 */
$rrhhErrorKey = '__MEDIDATA_RRHH_CONN_ERROR__';

if (!function_exists('medidata_conectar_rrhh')) {
    function medidata_conectar_rrhh(string $host): ?PDO
    {
        $dsnRrhh = 'mysql:host=' . $host . ';dbname=medic9ue_medi_rrhh_interviews;charset=utf8mb4';
        return new PDO(
            $dsnRrhh,
            dbuser,
            dbpass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ]
        );
    }
}

if (!empty($GLOBALS[$pdoRrhhKey]) && $GLOBALS[$pdoRrhhKey] instanceof PDO) {
    $connect_rrhh = $GLOBALS[$pdoRrhhKey];
} elseif (isset($connect_rrhh) && $connect_rrhh instanceof PDO) {
    $GLOBALS[$pdoRrhhKey] = $connect_rrhh;
} else {
    $connect_rrhh = null;
    $rrhhHosts = [dbhost];

    /* En hosting compartido, localhost suele ser el host correcto para PHP en el mismo servidor. */
    if (dbhost !== 'localhost' && dbhost !== '127.0.0.1') {
        $rrhhHosts[] = 'localhost';
    }

    foreach ($rrhhHosts as $rrhhHost) {
        try {
            $connect_rrhh = medidata_conectar_rrhh($rrhhHost);
            $GLOBALS[$pdoRrhhKey] = $connect_rrhh;
            unset($GLOBALS[$rrhhErrorKey]);
            break;
        } catch (PDOException $e) {
            $GLOBALS[$rrhhErrorKey] = $e->getMessage();
            error_log('Conexion.php PDO RRHH [' . $rrhhHost . ']: ' . $e->getMessage());
            $connect_rrhh = null;
        }
    }
}

$postulacionesErrorKey = '__MEDIDATA_POSTULACIONES_CONN_ERROR__';

if (!function_exists('medidata_conectar_postulaciones')) {
    function medidata_conectar_postulaciones(string $host): ?PDO
    {
        $dsn = 'mysql:host=' . $host . ';dbname=medic9ue_postulaciones;charset=utf8mb4';
        return new PDO(
            $dsn,
            dbuser,
            dbpass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ]
        );
    }
}

if (!empty($GLOBALS[$pdoPostulacionesKey]) && $GLOBALS[$pdoPostulacionesKey] instanceof PDO) {
    $connect_postulaciones = $GLOBALS[$pdoPostulacionesKey];
} elseif (isset($connect_postulaciones) && $connect_postulaciones instanceof PDO) {
    $GLOBALS[$pdoPostulacionesKey] = $connect_postulaciones;
} else {
    $connect_postulaciones = null;
    $postulacionesHosts = [dbhost];
    if (dbhost !== 'localhost' && dbhost !== '127.0.0.1') {
        $postulacionesHosts[] = 'localhost';
    }
    foreach ($postulacionesHosts as $postHost) {
        try {
            $connect_postulaciones = medidata_conectar_postulaciones($postHost);
            $GLOBALS[$pdoPostulacionesKey] = $connect_postulaciones;
            unset($GLOBALS[$postulacionesErrorKey]);
            break;
        } catch (PDOException $e) {
            $GLOBALS[$postulacionesErrorKey] = $e->getMessage();
            error_log('Conexion.php PDO postulaciones [' . $postHost . ']: ' . $e->getMessage());
            $connect_postulaciones = null;
        }
    }
}
