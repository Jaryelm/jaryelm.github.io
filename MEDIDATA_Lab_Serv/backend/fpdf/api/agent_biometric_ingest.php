<?php
/**
 * Ingesta de marcaciones desde el agente PHP en sede (post ZK pull vía cron).
 *
 * POST application/json:
 * {
 *   "site_code": "Sucursal_1",
 *   "device_serial": "opcional",
 *   "records": [ ["uid_txt","uid_num","Estado","Y-m-d H:i:s"], ... ]
 * }
 *
 * Cabecera: Authorization: Bearer <MEDIDATA_AGENT_INGEST_SECRET>.
 * Respaldo: si el servidor web no propaga Authorization a PHP (Apache CGI), usar cabecera
 * X-MEDIDATA-AGENT-TOKEN con el mismo valor (el cliente del agente envía ambas).
 *
 * Variables de entorno (Apache SetEnv / PHP-FPM / systemd):
 *   MEDIDATA_AGENT_INGEST_SECRET — token obligatorio (largo aleatorio).
 *
 * Laboratorio XAMPP sin SetEnv: cargar secret desde
 *   backend/php/biometric_ingest_secret.local.env (ver .example; no versionar).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/php/biometric_agent_secret_bootstrap.php';
medidata_biometric_bootstrap_agent_secret();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $secretPeek = getenv('MEDIDATA_AGENT_INGEST_SECRET');
    echo json_encode([
        'endpoint' => 'agent_biometric_ingest',
        'ready' => is_string($secretPeek) && strlen($secretPeek) >= 24,
        'use' => 'POST application/json + Authorization: Bearer ó X-MEDIDATA-AGENT-TOKEN',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$secret = getenv('MEDIDATA_AGENT_INGEST_SECRET');
if (!is_string($secret) || $secret === '') {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Servidor sin MEDIDATA_AGENT_INGEST_SECRET configurado.']);
    exit;
}

$hdr = isset($_SERVER['HTTP_AUTHORIZATION']) ? (string) $_SERVER['HTTP_AUTHORIZATION'] : '';
if ($hdr === '') {
    $hdr = isset($_SERVER['Authorization']) ? (string) $_SERVER['Authorization'] : '';
}
$bearerOk = preg_match('#^Bearer\s+(.+)$#i', $hdr, $m) === 1 && hash_equals($secret, trim($m[1]));
$xTok = isset($_SERVER['HTTP_X_MEDIDATA_AGENT_TOKEN'])
    ? trim((string) $_SERVER['HTTP_X_MEDIDATA_AGENT_TOKEN'])
    : '';
if (!$bearerOk && $xTok !== '') {
    $bearerOk = hash_equals($secret, $xTok);
}
if (!$bearerOk) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Cuerpo vacío.']);
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

$siteCode = isset($data['site_code']) ? trim((string) $data['site_code']) : '';
$deviceSerial = isset($data['device_serial']) ? trim((string) $data['device_serial']) : '';
$records = $data['records'] ?? null;

if ($siteCode === '' || !is_array($records)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan site_code o records[].']);
    exit;
}

require_once __DIR__ . '/../bd/Conexion.php';

if (!isset($connect) || !($connect instanceof PDO)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Base de datos no disponible.']);
    exit;
}

$insertSql = <<<SQL
INSERT IGNORE INTO biometric_marcas (
  site_code, uid_text, uid_numeric, estado, marca_datetime, device_serial
) VALUES (
  ?, ?, ?, ?, ?, ?
)
SQL;

$stmt = $connect->prepare($insertSql);

$received = count($records);
$inserted = 0;
$skipped = 0;

foreach ($records as $row) {
    if (!is_array($row) || count($row) < 4) {
        $skipped++;
        continue;
    }
    $uidTxt = trim((string) $row[0]);
    $uidNum = trim((string) $row[1]);
    $estado = trim((string) $row[2]);
    $ts = trim((string) $row[3]);

    if ($ts === '' || !preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $ts)) {
        $skipped++;
        continue;
    }

    try {
        $stmt->execute([
            $siteCode,
            $uidTxt,
            $uidNum,
            $estado,
            $ts,
            $deviceSerial !== '' ? $deviceSerial : null,
        ]);
        if ($stmt->rowCount() > 0) {
            $inserted++;
        } else {
            $skipped++;
        }
    } catch (PDOException $e) {
        error_log('agent_biometric_ingest: ' . $e->getMessage());
        $msg = (str_contains($e->getMessage(), 'biometric_marcas'))
            ? 'Tabla biometric_marcas no existe. Aplique backend/scripts/DDL_biometric_marcas.sql'
            : 'Error al insertar.';
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'site_code' => $siteCode,
    'received' => $received,
    'inserted' => $inserted,
    'skipped_or_duplicate' => $skipped,
]);
