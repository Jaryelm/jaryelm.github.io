<?php
declare(strict_types=1);

/**
 * Devuelve tamaño estimado de un estudio Orthanc para avisar antes de descargar el ZIP.
 * GET ?study_id={orthancStudyId}
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/orthanc_curl_config.php';

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$studyId = trim((string) ($_GET['study_id'] ?? ''));
if ($studyId === '' || $studyId === 'N/A' || !preg_match('/^[A-Za-z0-9_-]{8,128}$/', $studyId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de estudio inválido.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$username = 'dev';
$password = 'Mrecords7';
$url = 'https://medicloud.medicasa.hn/orthanc/studies/' . rawurlencode($studyId) . '/statistics';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
medicasa_orthanc_apply_curl_tls($ch);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$curlErr = curl_errno($ch) ? curl_error($ch) : '';
$httpStatus = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
unset($ch);

if ($curlErr !== '') {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'No se pudo consultar Orthanc: ' . $curlErr], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpStatus !== 200) {
    http_response_code($httpStatus >= 400 ? $httpStatus : 502);
    echo json_encode([
        'success' => false,
        'error' => 'Orthanc no devolvió estadísticas (HTTP ' . $httpStatus . ').',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode((string) $response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Respuesta inválida de Orthanc.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$bytes = isset($data['DiskSize']) ? (int) $data['DiskSize'] : 0;
if ($bytes <= 0 && isset($data['UncompressedSize'])) {
    $bytes = (int) $data['UncompressedSize'];
}
$sizeMb = isset($data['DiskSizeMB']) ? (float) $data['DiskSizeMB'] : ($bytes > 0 ? round($bytes / 1048576, 1) : 0.0);

// Estimaciones orientativas (informe rendimiento MH-PACS): remoto ~50 KB/s; LAN ~8 MB/s.
$remoteBytesPerSec = 50 * 1024;
$lanBytesPerSec = 8 * 1024 * 1024;
$etaRemoteSec = $bytes > 0 ? (int) ceil($bytes / $remoteBytesPerSec) : 0;
$etaLanSec = $bytes > 0 ? (int) ceil($bytes / $lanBytesPerSec) : 0;

echo json_encode([
    'success' => true,
    'study_id' => $studyId,
    'bytes' => $bytes,
    'size_mb' => $sizeMb,
    'size_label' => medidata_format_bytes($bytes > 0 ? $bytes : (int) round($sizeMb * 1048576)),
    'count_instances' => (int) ($data['CountInstances'] ?? 0),
    'count_series' => (int) ($data['CountSeries'] ?? 0),
    'eta_remote_sec' => $etaRemoteSec,
    'eta_lan_sec' => $etaLanSec,
    'eta_remote_label' => medidata_format_duration($etaRemoteSec),
    'eta_lan_label' => medidata_format_duration($etaLanSec),
    'is_large' => $bytes >= 200 * 1048576 || $sizeMb >= 200,
], JSON_UNESCAPED_UNICODE);

function medidata_format_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return 'desconocido';
    }
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    $n = (float) $bytes;
    while ($n >= 1024 && $i < count($units) - 1) {
        $n /= 1024;
        $i++;
    }
    $decimals = $i >= 3 ? 2 : ($i >= 2 ? 1 : 0);
    return number_format($n, $decimals, '.', ',') . ' ' . $units[$i];
}

function medidata_format_duration(int $seconds): string
{
    if ($seconds <= 0) {
        return 'unos segundos';
    }
    if ($seconds < 60) {
        return $seconds . ' s';
    }
    $minutes = (int) ceil($seconds / 60);
    if ($minutes < 60) {
        return $minutes . ' min';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $m > 0 ? ($h . ' h ' . $m . ' min') : ($h . ' h');
}
