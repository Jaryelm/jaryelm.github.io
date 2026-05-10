<?php
require_once __DIR__ . '\..\sdk\zkteco\vendor\autoload.php';

use Jmrashed\Zkteco\Lib\ZKTeco;

$zk = new ZkTeco(ip: "192.168.1.203", port: 4370);

$connect = $zk->connect();

echo ($connect) ? "Se ha logrado conectar con el biometrico." : "No se ha logrado conectar.";
try 
{
    echo $zk->fmVersion();
}
catch(Exception $ex) 
{
    echo $ex->getMessage();
}