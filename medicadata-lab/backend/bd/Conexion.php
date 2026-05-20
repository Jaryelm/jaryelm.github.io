<?php

if (!defined('dbhost')) {
  define('dbhost', 'localhost');
}
if (!defined('dbuser')) {
  define('dbuser', 'root');
}
if (!defined('dbpass')) {
  define('dbpass', 'hpk7pdwM4');
}
if (!defined('dbname')) {
  define('dbname', 'medic9ue_medi_data');
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
