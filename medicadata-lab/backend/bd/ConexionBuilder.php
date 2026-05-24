<?php 
include __DIR__."/ConexionStorage.php";

class ConexionBuilder {
    private array $environments;
    private ?array $dbConf = null;
    private string $dbKey;

    function configureEnvironment() : ConexionBuilder {
        global $dbConfiguration;
        $httpHost = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        $isLocalEnvironment = ($httpHost === '')
            || $httpHost === 'localhost'
            || strpos($httpHost, '127.0.0.1') === 0;

        $this->environments = ($isLocalEnvironment) ? $dbConfiguration["local"] : $dbConfiguration["production"];
        return $this;
    }

    function setDbConection(string $db) : ConexionBuilder {
        if (!isset($this->environments[$db])) {
            throw new Exception("Configuración de base de datos '$db' no encontrada.");
        }
        $this->dbConf = $this->environments[$db];
        $this->dbKey = $db;
        return $this;
    }

    public function getDbConf(): ?array {
        return $this->dbConf;
    }

    public function build(): PDO {
        if ($this->dbConf === null) {
            throw new Exception("Debe llamar a setDbConection() antes de build().");
        }

        $pdoReuseKey = ($this->dbKey === 'main') ? '__MEDIDATA_PDO_SINGLETON__' : '__MEDIDATA_PDO_' . strtoupper($this->dbKey) . '__';

        if (!empty($GLOBALS[$pdoReuseKey]) && $GLOBALS[$pdoReuseKey] instanceof PDO) {
            return $GLOBALS[$pdoReuseKey];
        }
        try {
            $pdo = new PDO(
                'mysql:host=' . $this->dbConf['host'] . ';dbname=' . $this->dbConf['name'],
                $this->dbConf['user'],
                $this->dbConf['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]
            );
            $pdo->query('set names utf8;');

            if (in_array($this->dbKey, $this->environments['singletons'] ?? [])) {
                $GLOBALS[$pdoReuseKey] = $pdo;
            }

            return $pdo;
        } catch (PDOException $e) {
            error_log('ConexionBuilder PDO (' . $this->dbKey . '): ' . $e->getMessage());
            if (!headers_sent()) {
                http_response_code(503);
            }
            throw $e;
        }
    }
}