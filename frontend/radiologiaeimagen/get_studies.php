<?php
// Aumentar el tiempo máximo de ejecución y memoria
ini_set('max_execution_time', 300); // 5 minutos
ini_set('memory_limit', '256M');

header('Content-Type: application/json');

// Archivo de caché
$cacheFile = 'orthanc_cache.json';
$cacheDuration = 900; // Duración del caché en segundos (15 minutos)

// Obtener parámetros de paginación y búsqueda
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Calcular el offset
$offset = ($page - 1) * $limit;

try {
    // Verificar si el archivo caché existe y es válido
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheDuration) {
        // Leer los datos desde el archivo caché
        $cacheContent = file_get_contents($cacheFile);
        if ($cacheContent === false) {
            throw new Exception("Error leyendo el archivo de caché");
        }
        
        $allData = json_decode($cacheContent, true);
        if ($allData === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decodificando el caché: " . json_last_error_msg());
        }
    } else {
        // URL base de la API de Orthanc
        $orthanc_url = 'https://medicloud.medicasa.hn/orthanc/studies?expand=true';
        $username = 'dev';
        $password = 'Mrecords7';

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
        curl_close($ch);

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
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>