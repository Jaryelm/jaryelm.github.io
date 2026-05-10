<?php
/**
 * Conexión PDO MySQL — MEDIDATA
 *
 * Local (XAMPP) y producción en este archivo. Mismos datos que credenciales.txt (producción).
 */

// --- LOCAL (XAMPP) ---
$dbLocal = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'medic9ue_medi_data',
];

// --- PRODUCCIÓN (credenciales.txt líneas 8-11) ---
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

// Una sola PDO por petición aunque este archivo se ejecute más de una vez (p. ej. require sin _once tras session_check).
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
