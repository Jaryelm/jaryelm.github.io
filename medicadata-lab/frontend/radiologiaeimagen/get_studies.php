<?php
declare(strict_types=1);

/**
 * Lista paginada MH-PACS desde worklist (MySQL local).
 * Evita descargar miles de estudios de Orthanc en cada visita.
 */

while (ob_get_level() > 0) {
    ob_end_clean();
}
ob_start();

@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/php/mh_pacs_studies_repository.php';

if (!function_exists('getStudiesSendJson')) {
    function getStudiesSendJson(array $payload, int $httpCode = 200): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code($httpCode);
            header('Content-Type: application/json; charset=utf-8');
        }
        $flags = JSON_UNESCAPED_UNICODE;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($payload, $flags);
        if ($json === false) {
            $json = json_encode(
                ['success' => false, 'error' => 'No se pudo generar la respuesta JSON.'],
                JSON_UNESCAPED_UNICODE
            );
        }
        echo $json;
        exit;
    }
}

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err === null) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];
    if (!in_array($err['type'], $fatalTypes, true) || headers_sent()) {
        return;
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    getStudiesSendJson(
        ['success' => false, 'error' => 'Error interno al procesar estudios. Revise el log del servidor.'],
        500
    );
});

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

try {
    $result = medidata_mh_pacs_fetch_studies($connect, $page, $limit, $search);

<<<<<<< Updated upstream
        $ch = curl_init($orthanc_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception("Error de cURL: " . curl_error($ch));
        }

        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($http_status != 200) {
            throw new Exception("Error HTTP al obtener estudios: " . $http_status);
        }

        $data = json_decode($response, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decodificando respuesta de Orthanc: " . json_last_error_msg());
        }

        if (!is_array($data)) {
            throw new Exception("La respuesta de Orthanc no es un array válido");
        }

        // Formatear los datos usando curl_multi para obtener modalidades en paralelo
        $formattedData = [];
        $batch_size = 25;
        
        foreach (array_chunk($data, $batch_size) as $study_batch) {
            $mh = curl_multi_init();
            $channels = [];
            
            foreach ($study_batch as $index => $study) {
                if (!isset($study['ID'])) continue;
                
                // Primero intentar obtener la modalidad del estudio
                $modality = $study['MainDicomTags']['Modality'] ?? null;
                
                // Si no hay modalidad en el estudio y hay series, preparar para obtener de la primera serie
                if (!$modality && !empty($study['Series'])) {
                    $series_url = "https://medicloud.medicasa.hn/orthanc/series/{$study['Series'][0]}";
                    $ch = curl_init($series_url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
                    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    
                    curl_multi_add_handle($mh, $ch);
                    $channels[$index] = [
                        'ch' => $ch,
                        'study' => $study
                    ];
                } else {
                    // Si ya tenemos la modalidad o no hay series, formatear directamente
                    $formattedData[] = [
                        'ID' => $study['ID'],
                        'PatientName' => $study['PatientMainDicomTags']['PatientName'] ?? 'N/A',
                        'PatientSex' => $study['PatientMainDicomTags']['PatientSex'] ?? 'N/A',
                        'PatientID' => $study['PatientMainDicomTags']['PatientID'] ?? 'N/A',
                        'StudyDate' => $study['MainDicomTags']['StudyDate'] ?? 'N/A',
                        'Modality' => $modality ?? 'N/A',
                        'StudyDescription' => $study['MainDicomTags']['StudyDescription'] ?? 'N/A',
                        'InstitutionName' => $study['MainDicomTags']['InstitutionName'] ?? 'N/A',
                        'ReferringPhysicianName' => $study['MainDicomTags']['ReferringPhysicianName'] ?? 'N/A',
                        'FirstSeriesId' => $study['Series'][0] ?? null
                    ];
                }
            }
            
            if (!empty($channels)) {
                $running = null;
                do {
                    curl_multi_exec($mh, $running);
                    if ($running > 0) {
                        curl_multi_select($mh);
                    }
                } while ($running > 0);

                foreach ($channels as $index => $channel) {
                    $ch = $channel['ch'];
                    $study = $channel['study'];
                    $series_response = curl_multi_getcontent($ch);
                    
                    $modality = 'N/A';
                    if ($series_response) {
                        $series_data = json_decode($series_response, true);
                        if ($series_data && isset($series_data['MainDicomTags']['Modality'])) {
                            $modality = $series_data['MainDicomTags']['Modality'];
                        }
                    }

                    $formattedData[] = [
                        'ID' => $study['ID'],
                        'PatientName' => $study['PatientMainDicomTags']['PatientName'] ?? 'N/A',
                        'PatientSex' => $study['PatientMainDicomTags']['PatientSex'] ?? 'N/A',
                        'PatientID' => $study['PatientMainDicomTags']['PatientID'] ?? 'N/A',
                        'StudyDate' => $study['MainDicomTags']['StudyDate'] ?? 'N/A',
                        'Modality' => $modality,
                        'StudyDescription' => $study['MainDicomTags']['StudyDescription'] ?? 'N/A',
                        'InstitutionName' => $study['MainDicomTags']['InstitutionName'] ?? 'N/A',
                        'ReferringPhysicianName' => $study['MainDicomTags']['ReferringPhysicianName'] ?? 'N/A',
                        'FirstSeriesId' => $study['Series'][0] ?? null
                    ];

                    curl_multi_remove_handle($mh, $ch);
                }
                curl_multi_close($mh);
            }
        }

        // Ordenar los estudios por StudyDate (de más reciente a más antiguo)
        usort($formattedData, function ($a, $b) {
            $dateA = $a['StudyDate'] ?? '';
            $dateB = $b['StudyDate'] ?? '';
            return strcmp($dateB, $dateA);
        });

        $allData = $formattedData;

        // Guardar en caché
        if (file_put_contents($cacheFile, json_encode($allData), LOCK_EX) === false) {
            error_log("Error writing to cache file: " . $cacheFile);
        }
    }

    // Filtrar por búsqueda si existe
    if (!empty($search)) {
        $filteredData = array_filter($allData, function($study) use ($search) {
            $searchLower = strtolower($search);
            return (
                stripos($study['PatientName'] ?? '', $search) !== false ||
                stripos($study['StudyDescription'] ?? '', $search) !== false ||
                stripos($study['Modality'] ?? '', $search) !== false ||
                stripos($study['PatientID'] ?? '', $search) !== false
            );
        });
    } else {
        $filteredData = $allData;
    }

    // Obtener el total de registros filtrados
    $totalRecords = count($filteredData);

    // Obtener solo los registros de la página actual
    $pagedData = array_slice($filteredData, $offset, $limit);

    // Devolver los datos paginados
    echo json_encode([
        'success' => true,
        'studies' => $pagedData,
        'total' => $totalRecords,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($totalRecords / $limit)
    ]);

} catch (Exception $e) {
    error_log("Error en get_studies.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
=======
    getStudiesSendJson([
        'success'   => true,
        'studies'   => $result['studies'],
        'total'     => $result['total'],
        'page'      => $result['page'],
        'limit'     => $result['limit'],
        'totalPages'=> $result['totalPages'],
        'last_sync' => $result['last_sync'],
        'source'    => 'worklist',
    ], 200);
} catch (Throwable $e) {
    error_log('get_studies.php: ' . $e->getMessage());
    getStudiesSendJson([
>>>>>>> Stashed changes
        'success' => false,
        'error'   => 'No se pudieron cargar los estudios. Use «Sincronizar Orthanc» si la lista está vacía.',
    ], 500);
}
