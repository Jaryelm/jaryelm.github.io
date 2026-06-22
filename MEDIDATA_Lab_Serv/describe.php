<?php
require 'backend/bd/Conexion.php';
$stmt = $connect->query('DESCRIBE nurse');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt2 = $connect->query('DESCRIBE doctor');
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
