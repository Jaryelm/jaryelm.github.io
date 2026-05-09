<?php
require_once __DIR__ . '\..\sdk\zkteco\vendor\autoload.php';

use Jmrashed\Zkteco\Lib\ZKTeco;

$zk = new ZkTeco(ip: "192.168.100.1");

$connect = $zk->connect();

echo ($connect) ? "Funka" : "No Funka";
