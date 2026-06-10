<?php
require '../../backend/bd/Conexion.php';
require '../../backend/registros/rrhh_guard.php';

$pdo = medidata_rrhh_pdo();
$res = $pdo->query('SELECT * FROM interviews')->fetchAll(PDO::FETCH_ASSOC);
print_r($res);
