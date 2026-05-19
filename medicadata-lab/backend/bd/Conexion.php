<?php

$dbLocal = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => 'hpk7pdwM4',
    'name' => 'medic9ue_medi_data',
];

$dbProduccion = [
    'host' => '162.241.123.41',
    'user' => 'medic9ue_moisesc',
    'pass' => 'Mrecords%7',
    'name' => 'medic9ue_medi_data',
];

$httpHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
$esEntornoLocal = ($httpHost === '')
    || $httpHost === 'localhost'
    || strpos($httpHost, '127.0.0.1') === 0;

$dbCfg = $esEntornoLocal ? $dbLocal : $dbProduccion;

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
    $connect = new PDO(
        'mysql:host=' . dbhost . ';dbname=' . dbname,
        dbuser,
        dbpass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]
    );
    $connect->query('set names utf8;');
    $GLOBALS[$pdoReuseKey] = $connect;
} catch (PDOException $e) {
  error_log('Conexion.php PDO: ' . $e->getMessage());
  if (!headers_sent()) {
    http_response_code(503);
  }
  throw $e;
}
