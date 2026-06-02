<?php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/postulaciones_guard.php';

header('Content-Type: application/json; charset=utf-8');

echo json_encode(
    medidata_postulaciones_datatables($_GET),
    JSON_UNESCAPED_UNICODE
);
