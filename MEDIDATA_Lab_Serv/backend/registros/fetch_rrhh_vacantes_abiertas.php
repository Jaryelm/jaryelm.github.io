<?php
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/rrhh_aplica_bridge.php';

header('Content-Type: application/json; charset=utf-8');

medidata_rrhh_aplica_bridge_ensure_schema();

$vacantes = medidata_rrhh_fetch_vacantes_abiertas();
$data = array_map(static function (array $row): array {
    return [
        'id' => (int) $row['id'],
        'label' => ($row['vacant_name'] ?? '') . ' — ' . ($row['position_name'] ?? ''),
        'vacant_name' => (string) ($row['vacant_name'] ?? ''),
        'position_name' => (string) ($row['position_name'] ?? ''),
        'priority' => (string) ($row['priority'] ?? ''),
        'end_date' => (string) ($row['end_date'] ?? ''),
    ];
}, $vacantes);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
