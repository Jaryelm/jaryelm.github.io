<?php

/**
 * Conexión PDO MySQL — MEDIDATA (paquete laboratorio NEXAR / Joan Dev)
 *
 * Solo configuración local XAMPP. Sin credenciales ni host de producción.
 * Ajusta usuario/contraseña si tu MySQL local no usa root sin clave.
 */

$dbCfg = [
  'host' => 'localhost',
  'user' => 'root',
  'pass' => 'hpk7pdwM4',
  'name' => 'medic9ue_medi_data',
];

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

if (isset($connect) && $connect instanceof PDO) {
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
    ]
  );
  $connect->query('set names utf8;');
} catch (PDOException $e) {
  error_log('Conexion.php PDO: ' . $e->getMessage());
  if (!headers_sent()) {
    http_response_code(503);
  }
  throw $e;
}
