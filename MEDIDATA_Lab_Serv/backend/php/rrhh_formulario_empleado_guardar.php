<?php
require_once __DIR__ . '/../bd/Conexion.php';
require_once __DIR__ . '/rrhh_employee_form_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$token = trim((string) ($_POST['token'] ?? ''));
$allowed = [
    'birthdate', 'marital_status', 'direction',
    'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
    'dependents_count', 'dependents_names',
    'bank_account', 'bank_name',
    'personal_reference_1', 'personal_reference_2',
    'professional_reference_1', 'professional_reference_2',
    'observations',
];

$fields = [];
foreach ($allowed as $key) {
    if (array_key_exists($key, $_POST)) {
        $fields[$key] = trim((string) $_POST[$key]);
    }
}

echo json_encode(
    medidata_rrhh_employee_form_save_public($token, $fields),
    JSON_UNESCAPED_UNICODE
);
