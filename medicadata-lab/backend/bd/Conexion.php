<?php
require_once __DIR__ . '/ConexionBuilder.php';

try {
    $builder = (new ConexionBuilder())->configureEnvironment();
    
    // Conexión Principal (Main)
    $connect = $builder->setDbConection('main')->build();
    
    // Define constantes para compatibilidad con el código legacy (Base de datos Main)
    $dbCfg = $builder->getDbConf();
    if ($dbCfg) {
        if (!defined('dbhost')) define('dbhost', $dbCfg['host']);
        if (!defined('dbuser')) define('dbuser', $dbCfg['user']);
        if (!defined('dbpass')) define('dbpass', $dbCfg['pass']);
        if (!defined('dbname')) define('dbname', $dbCfg['name']);
    }
    
    // Conexión Secundaria (RRHH / Interviews)
    // Se mantiene en una variable separada para evitar switching constante
    $connect_rrhh = $builder->setDbConection('rrhh')->build();

} catch (Exception $e) {
    error_log("Error en Conexion.php: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(503);
    }
    die("Error de conexión a la base de datos.");
}
