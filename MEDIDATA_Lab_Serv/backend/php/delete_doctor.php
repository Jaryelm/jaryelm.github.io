<?php
include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/doctor_delete_cascade.php';

header('Content-Type: application/json; charset=utf-8');

$idodc = (int) ($_POST['idodc'] ?? $_POST['id'] ?? 0);
$result = medidata_delete_doctor_cascade($connect, $idodc);

echo json_encode($result);
