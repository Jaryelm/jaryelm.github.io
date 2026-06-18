<?php
require_once('../../backend/bd/Conexion.php');
require_once __DIR__ . '/orthanc_curl_config.php';
header('Content-Type: application/json');

// Aumentar límites de tiempo y memoria
ini_set('max_execution_time', 300); // 5 minutos
ini_set('memory_limit', '256M');

// Lock global para evitar sincronizaciones simultáneas (picos de escrituras/MySQL)
// Nota: en hosting compartido idealmente el directorio del temp es escribible.
$lockFp = null;
$lockFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'medidata_sync_orthanc.lock';
$lockFp = @fopen($lockFile, 'c');
if ($lockFp !== false) {
    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Sincronizacion en progreso. Intente en unos segundos.',
        ], JSON_UNESCAPED_UNICODE);
        exit(0);
    }
} else {
    // Si no se puede abrir lock, dejamos un log pero seguimos (riesgo: concurrencia).
    error_log('sync_orthanc: no se pudo abrir lock file: ' . $lockFile);
}

try {
    // Ajuste solo en producción (la DB no la puedo configurar): guardamos last_sync con hora Tegucigalpa desde PHP.
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $isProd = preg_match('/\.hn$/', $host) === 1;
    $nowTegucigalpa = null;
    if ($isProd) {
        $tzTeg = new DateTimeZone('America/Tegucigalpa');
        $nowTegucigalpa = (new DateTime('now', $tzTeg))->format('Y-m-d H:i:s');
    }

    // URL base de la API de Orthanc
    $orthanc_url = 'https://medicloud.medicasa.hn/orthanc/studies?expand=true';
    $username = 'dev';
    $password = 'Mrecords7';

    // Obtener la última fecha de sincronización
    $stmt = $connect->prepare("SELECT MAX(last_sync) as last_sync FROM worklist");
    $stmt->execute();
    $last_sync = $stmt->fetch(PDO::FETCH_ASSOC)['last_sync'];

    // Iniciar cURL para obtener los estudios
    $ch = curl_init($orthanc_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    medicasa_orthanc_apply_curl_tls($ch);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout de 60 segundos para la conexión
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // Timeout de 30 segundos para establecer conexión

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        throw new Exception('Error de cURL: ' . curl_error($ch));
    }

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    unset($ch);

    if ($http_status != 200) {
        throw new Exception("Error al obtener estudios de Orthanc. Código HTTP: " . $http_status);
    }

    $studies = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON de Orthanc: ' . json_last_error_msg());
    }

    if (!is_array($studies)) {
        throw new Exception('La respuesta de Orthanc no es un array válido');
    }

    // Priorizar lo más reciente primero (si se corta por timeout, al menos quedan los últimos sincronizados).
    usort($studies, static function ($a, $b) {
        $aU = isset($a['LastUpdate']) ? strtotime((string) $a['LastUpdate']) : 0;
        $bU = isset($b['LastUpdate']) ? strtotime((string) $b['LastUpdate']) : 0;
        if ($aU === $bU) {
            $aId = (string)($a['ID'] ?? '');
            $bId = (string)($b['ID'] ?? '');
            return strcmp($bId, $aId);
        }
        return $bU <=> $aU;
    });

    /**
     * Sincronización sin transacción global: antes beginTransaction() cubría TODOS los estudios
     * y retenía bloqueos InnoDB durante minutos → Lock wait timeout (1205).
     * UPSERT atómico evita carrera SELECT+INSERT entre crons/usuarios y duplicados unique_study (1062).
     */
    if ($isProd) {
        // Usar hora Tegucigalpa desde PHP para que last_sync coincida con el huso esperado.
        $upsert_stmt = $connect->prepare(
            'INSERT INTO worklist (
                study_id,
                series_id,
                patient_id,
                patient_name,
                study_date,
                modality,
                study_description,
                status,
                priority,
                last_sync,
                last_update,
                created_at
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
        // Local: se mantiene el comportamiento original usando NOW() de MySQL.
        $upsert_stmt = $connect->prepare(
            'INSERT INTO worklist (
                study_id,
                series_id,
                patient_id,
                patient_name,
                study_date,
                modality,
                study_description,
                status,
                priority,
                last_sync,
                last_update,
                created_at
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

    // Más grande = menos overhead de curl_multi; suficientemente pequeño para no saturar.
    $batch_size = 60;
    $processed = 0;
    $total_studies = count($studies);

    foreach (array_chunk($studies, $batch_size) as $study_batch) {
        $mh = curl_multi_init();
        $channels = [];

        // Preparar SOLO las solicitudes necesarias (evitar /studies/{id}/series?expand para todos).
        // Con studies?expand=true ya viene Series[0] y a veces Modality.
        foreach ($study_batch as $index => $study) {
            $modality = $study['MainDicomTags']['Modality'] ?? null;
            $series_id = $study['Series'][0] ?? null;

            if ($modality || !$series_id) {
                continue;
            }

            $series_url = "https://medicloud.medicasa.hn/orthanc/series/{$series_id}";
            $ch = curl_init($series_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            medicasa_orthanc_apply_curl_tls($ch);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);

            curl_multi_add_handle($mh, $ch);
            $channels[$index] = [
                'ch' => $ch,
                'study' => $study,
                'series_id' => $series_id,
            ];
        }

        // Ejecutar las solicitudes en paralelo
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh, 0.7);
            }
        } while ($running > 0);

        // Cache de modalidades obtenidas de series (por si se repite un series_id).
        $seriesModalityCache = [];
        foreach ($channels as $channel) {
            $ch = $channel['ch'];
            $series_id = (string) ($channel['series_id'] ?? '');
            $modality = null;

            $series_response = curl_multi_getcontent($ch);
            if ($series_response) {
                $series_data = json_decode($series_response, true);
                if (is_array($series_data) && isset($series_data['MainDicomTags']['Modality'])) {
                    $modality = $series_data['MainDicomTags']['Modality'];
                }
            }
            if ($series_id !== '' && $modality) {
                $seriesModalityCache[$series_id] = $modality;
            }

            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);

        // Procesar UPSERT para todo el lote (con o sin llamada extra).
        foreach ($study_batch as $study) {
            $series_id = $study['Series'][0] ?? null;
            $modality = $study['MainDicomTags']['Modality'] ?? null;
            if (!$modality && $series_id && isset($seriesModalityCache[(string) $series_id])) {
                $modality = $seriesModalityCache[(string) $series_id];
            }

            $study_last_update = $study['LastUpdate'] ?? ($isProd ? $nowTegucigalpa : date('Y-m-d H:i:s'));

            // Procesar la fecha del estudio
            $study_date = $study['MainDicomTags']['StudyDate'] ?? null;
            if ($study_date) {
                // Si la fecha viene en formato DICOM (YYYYMMDD), convertirla
                if (strlen($study_date) === 8 && is_numeric($study_date)) {
                    $year = substr($study_date, 0, 4);
                    $month = substr($study_date, 4, 2);
                    $day = substr($study_date, 6, 2);
                    $study_date = "$year-$month-$day 00:00:00";
                } else {
                    // Si ya viene en formato correcto, agregar la hora si no la tiene
                    if (strlen($study_date) === 10) { // Solo fecha YYYY-MM-DD
                        $study_date .= " 00:00:00";
                    }
                }
            } else {
                $study_date = date('Y-m-d H:i:s'); // Usar fecha actual si no hay fecha
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

    echo json_encode([
        'success' => true,
        'message' => "Sincronización completada. Procesados: $processed de $total_studies estudios",
        'processed' => $processed,
        'total' => $total_studies
    ]);

} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    error_log("Error en sync_orthanc.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($lockFp) && is_resource($lockFp)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
    }
}
?> 