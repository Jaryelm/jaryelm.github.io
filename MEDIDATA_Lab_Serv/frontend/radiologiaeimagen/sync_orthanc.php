<?php
/**
 * Sincroniza Orthanc → worklist.
 * Por defecto: incremental (solo estudios con LastUpdate >= last_sync - solape).
 * Full: ?full=1
 */
require_once('../../backend/bd/Conexion.php');
require_once __DIR__ . '/orthanc_curl_config.php';
header('Content-Type: application/json; charset=UTF-8');

ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

/**
 * Orthanc LastUpdate suele venir como 20260714T155603 (UTC).
 */
function medicasa_orthanc_parse_ts(?string $raw): int
{
    $raw = trim((string) $raw);
    if ($raw === '') {
        return 0;
    }
    if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})/', $raw, $m)) {
        $dt = DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $m[1] . '-' . $m[2] . '-' . $m[3] . ' ' . $m[4] . ':' . $m[5] . ':' . $m[6],
            new DateTimeZone('UTC')
        );
        return $dt ? $dt->getTimestamp() : 0;
    }
    $ts = strtotime($raw);
    return $ts !== false ? $ts : 0;
}

$lockFp = null;
$lockDir = __DIR__ . '/../../backend/tmp';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0775, true);
}
$lockFile = (is_dir($lockDir) && is_writable($lockDir))
    ? ($lockDir . DIRECTORY_SEPARATOR . 'medidata_sync_orthanc.lock')
    : (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'medidata_sync_orthanc.lock');

$lockFp = @fopen($lockFile, 'c');
if ($lockFp !== false) {
    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Sincronización en progreso. Intente en unos segundos.',
        ], JSON_UNESCAPED_UNICODE);
        exit(0);
    }
} else {
    error_log('sync_orthanc: no se pudo abrir lock file: ' . $lockFile);
}

try {
    if (!isset($connect) || !($connect instanceof PDO)) {
        throw new RuntimeException('No hay conexión a la base de datos.');
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $isProd = preg_match('/\.hn$/', $host) === 1;
    $nowTegucigalpa = null;
    if ($isProd) {
        $tzTeg = new DateTimeZone('America/Tegucigalpa');
        $nowTegucigalpa = (new DateTime('now', $tzTeg))->format('Y-m-d H:i:s');
    }

    $forceFull = isset($_GET['full']) && (string) $_GET['full'] === '1';

    $orthanc_url = 'https://medicloud.medicasa.hn/orthanc/studies?expand=true';
    $username = 'dev';
    $password = 'Mrecords7';

    $stmt = $connect->prepare('SELECT MAX(last_sync) AS last_sync FROM worklist');
    $stmt->execute();
    $last_sync = $stmt->fetch(PDO::FETCH_ASSOC)['last_sync'] ?? null;

    // Corte incremental: last_sync en hora Tegucigalpa → UTC, con solape de 2h por TZ/retrasos.
    $cutoffTs = 0;
    if (!$forceFull && !empty($last_sync)) {
        $tzTeg = new DateTimeZone('America/Tegucigalpa');
        $dtSync = DateTime::createFromFormat('Y-m-d H:i:s', (string) $last_sync, $tzTeg);
        if ($dtSync instanceof DateTime) {
            $cutoffTs = $dtSync->getTimestamp() - 7200;
        } else {
            $ts = strtotime((string) $last_sync);
            $cutoffTs = $ts !== false ? ($ts - 7200) : 0;
        }
    }

    $ch = curl_init($orthanc_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    medicasa_orthanc_apply_curl_tls($ch);
    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('Error de cURL: ' . curl_error($ch));
    }
    $http_status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    if ($http_status !== 200) {
        throw new Exception('Error al obtener estudios de Orthanc. Código HTTP: ' . $http_status);
    }

    $studies = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON de Orthanc: ' . json_last_error_msg());
    }
    if (!is_array($studies)) {
        throw new Exception('La respuesta de Orthanc no es un array válido');
    }

    usort($studies, static function ($a, $b) {
        $aU = medicasa_orthanc_parse_ts($a['LastUpdate'] ?? null);
        $bU = medicasa_orthanc_parse_ts($b['LastUpdate'] ?? null);
        if ($aU === $bU) {
            return strcmp((string) ($b['ID'] ?? ''), (string) ($a['ID'] ?? ''));
        }
        return $bU <=> $aU;
    });

    $total_in_orthanc = count($studies);
    if ($cutoffTs > 0) {
        $filtered = [];
        foreach ($studies as $study) {
            $ts = medicasa_orthanc_parse_ts($study['LastUpdate'] ?? null);
            // Orden DESC: al pasar el corte, el resto es más viejo.
            if ($ts > 0 && $ts < $cutoffTs) {
                break;
            }
            $filtered[] = $study;
        }
        $studies = $filtered;
    }

    if ($isProd) {
        $upsert_stmt = $connect->prepare(
            'INSERT INTO worklist (
                study_id, series_id, patient_id, patient_name, study_date, modality,
                study_description, status, priority, last_sync, last_update, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\', \'routine\', ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                series_id = VALUES(series_id),
                patient_id = VALUES(patient_id),
                patient_name = VALUES(patient_name),
                study_date = VALUES(study_date),
                modality = VALUES(modality),
                study_description = VALUES(study_description),
                last_sync = VALUES(last_sync),
                last_update = VALUES(last_update)'
        );
    } else {
        $upsert_stmt = $connect->prepare(
            'INSERT INTO worklist (
                study_id, series_id, patient_id, patient_name, study_date, modality,
                study_description, status, priority, last_sync, last_update, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, \'pending\', \'routine\', NOW(), ?, NOW())
            ON DUPLICATE KEY UPDATE
                series_id = VALUES(series_id),
                patient_id = VALUES(patient_id),
                patient_name = VALUES(patient_name),
                study_date = VALUES(study_date),
                modality = VALUES(modality),
                study_description = VALUES(study_description),
                last_sync = NOW(),
                last_update = VALUES(last_update)'
        );
    }

    $batch_size = 60;
    $processed = 0;
    $total_studies = count($studies);

    foreach (array_chunk($studies, $batch_size) as $study_batch) {
        $mh = curl_multi_init();
        $channels = [];

        foreach ($study_batch as $index => $study) {
            $modality = $study['MainDicomTags']['Modality'] ?? null;
            $series_id = $study['Series'][0] ?? null;
            if ($modality || !$series_id) {
                continue;
            }

            $series_url = "https://medicloud.medicasa.hn/orthanc/series/{$series_id}";
            $chSeries = curl_init($series_url);
            curl_setopt($chSeries, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chSeries, CURLOPT_USERPWD, "$username:$password");
            curl_setopt($chSeries, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            medicasa_orthanc_apply_curl_tls($chSeries);
            curl_setopt($chSeries, CURLOPT_TIMEOUT, 20);
            curl_setopt($chSeries, CURLOPT_CONNECTTIMEOUT, 8);
            curl_multi_add_handle($mh, $chSeries);
            $channels[$index] = [
                'ch' => $chSeries,
                'series_id' => $series_id,
            ];
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 0.7);
            }
        } while ($running > 0);

        $seriesModalityCache = [];
        foreach ($channels as $channel) {
            $chSeries = $channel['ch'];
            $series_id = (string) ($channel['series_id'] ?? '');
            $series_response = curl_multi_getcontent($chSeries);
            if ($series_response) {
                $series_data = json_decode($series_response, true);
                if (is_array($series_data) && isset($series_data['MainDicomTags']['Modality'])) {
                    $seriesModalityCache[$series_id] = $series_data['MainDicomTags']['Modality'];
                }
            }
            curl_multi_remove_handle($mh, $chSeries);
        }
        curl_multi_close($mh);

        foreach ($study_batch as $study) {
            $series_id = $study['Series'][0] ?? null;
            $modality = $study['MainDicomTags']['Modality'] ?? null;
            if (!$modality && $series_id && isset($seriesModalityCache[(string) $series_id])) {
                $modality = $seriesModalityCache[(string) $series_id];
            }

            $study_last_update = $study['LastUpdate'] ?? ($isProd ? $nowTegucigalpa : date('Y-m-d H:i:s'));

            $study_date = $study['MainDicomTags']['StudyDate'] ?? null;
            if ($study_date) {
                if (strlen($study_date) === 8 && is_numeric($study_date)) {
                    $year = substr($study_date, 0, 4);
                    $month = substr($study_date, 4, 2);
                    $day = substr($study_date, 6, 2);
                    $study_date = "$year-$month-$day 00:00:00";
                } elseif (strlen($study_date) === 10) {
                    $study_date .= ' 00:00:00';
                }
            } else {
                $study_date = date('Y-m-d H:i:s');
            }

            try {
                if ($isProd) {
                    $upsert_stmt->execute([
                        $study['ID'],
                        $series_id,
                        $study['PatientMainDicomTags']['PatientID'] ?? null,
                        $study['PatientMainDicomTags']['PatientName'] ?? null,
                        $study_date,
                        $modality,
                        $study['MainDicomTags']['StudyDescription'] ?? null,
                        $nowTegucigalpa,
                        $study_last_update,
                        $nowTegucigalpa,
                    ]);
                } else {
                    $upsert_stmt->execute([
                        $study['ID'],
                        $series_id,
                        $study['PatientMainDicomTags']['PatientID'] ?? null,
                        $study['PatientMainDicomTags']['PatientName'] ?? null,
                        $study_date,
                        $modality,
                        $study['MainDicomTags']['StudyDescription'] ?? null,
                        $study_last_update,
                    ]);
                }
                $processed++;
            } catch (PDOException $e) {
                error_log("Error procesando estudio {$study['ID']}: " . $e->getMessage());
                continue;
            }
        }
    }

    // Si no había nada nuevo, igual refrescar last_sync del más reciente para marcar "al día".
    if ($processed === 0 && $isProd && $nowTegucigalpa !== null) {
        $touch = $connect->prepare('UPDATE worklist SET last_sync = ? WHERE id = (SELECT id FROM (SELECT id FROM worklist ORDER BY last_update DESC, id DESC LIMIT 1) t)');
        try {
            $touch->execute([$nowTegucigalpa]);
        } catch (Throwable $ignore) {
            // no crítico
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $forceFull
            ? "Sincronización completa. Procesados: $processed de $total_in_orthanc estudios"
            : "Sincronización incremental. Procesados: $processed de $total_studies candidatos ($total_in_orthanc en Orthanc)",
        'processed' => $processed,
        'candidates' => $total_studies,
        'total' => $total_in_orthanc,
        'mode' => $forceFull ? 'full' : 'incremental',
        'last_sync_before' => $last_sync,
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    if (isset($connect) && $connect instanceof PDO && $connect->inTransaction()) {
        $connect->rollBack();
    }
    error_log('Error en sync_orthanc.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($lockFp) && is_resource($lockFp)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
    }
}

?> 