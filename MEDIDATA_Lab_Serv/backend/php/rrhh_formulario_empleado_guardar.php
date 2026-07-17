<?php
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/rrhh_employee_form_lib.php';
require_once __DIR__ . '/rrhh_employee_form_fields_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$token = trim((string) ($_POST['token'] ?? ''));
$fields = medidata_rrhh_employee_form_collect($_POST);

echo json_encode(
    medidata_rrhh_employee_form_save_public($token, $fields),
    JSON_UNESCAPED_UNICODE
);
