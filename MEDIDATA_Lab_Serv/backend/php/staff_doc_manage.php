<?php
include_once __DIR__ . '/../registros/session_check.php';
require_once __DIR__ . '/staff_doc_manage_lib.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado.']);
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$table = trim((string) ($_POST['table'] ?? 'staff_administrative'));
$id = (int) ($_POST['id'] ?? 0);
$doc = trim((string) ($_POST['doc'] ?? ''));

$file = null;
if ($action === 'upload' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
}

$result = medidata_staff_doc_manage($connect, $action, $table, $id, $doc, $file);
echo json_encode($result, JSON_UNESCAPED_UNICODE);
