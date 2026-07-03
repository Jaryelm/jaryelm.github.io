<?php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/../php/biometric_marcas_db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($connect) || !($connect instanceof PDO)) {
    echo json_encode([
        'draw' => (int) ($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'No hay conexión a la base de datos.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$siteCode = medidata_biometric_resolve_site_code();
echo json_encode(
    medidata_biometric_datatables($connect, $_GET, $siteCode),
    JSON_UNESCAPED_UNICODE
);
