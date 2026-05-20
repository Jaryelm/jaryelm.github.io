<?php
require_once('../../backend/bd/Conexion.php');
header('Content-Type: application/json');

// Aumentar límites de tiempo y memoria
ini_set('max_execution_time', 300); // 5 minutos
ini_set('memory_limit', '256M');

try {
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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

    $connect->beginTransaction();

    // Preparar las consultas
    $check_stmt = $connect->prepare("SELECT id, last_update FROM worklist WHERE study_id = ?");
    $insert_stmt = $connect->prepare("
        INSERT INTO worklist (
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 'routine', NOW(), ?, NOW())
    ");
    $update_stmt = $connect->prepare("
        UPDATE worklist SET 
            series_id = ?,
            patient_name = ?,
            study_date = ?,
            modality = ?,
            study_description = ?,
            last_sync = NOW(),
            last_update = ?
        WHERE study_id = ? AND last_update < ?
    ");

    $batch_size = 25; // Reducido el tamaño del lote para mejor rendimiento
    $processed = 0;
    $total_studies = count($studies);

    foreach (array_chunk($studies, $batch_size) as $study_batch) {
        $mh = curl_multi_init();
        $channels = [];

        // Preparar todas las solicitudes de series en paralelo
        foreach ($study_batch as $index => $study) {
            $series_url = "https://medicloud.medicasa.hn/orthanc/studies/{$study['ID']}/series?expand";
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
        }

        // Ejecutar las solicitudes en paralelo
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            if ($running > 0) {
                curl_multi_select($mh);
            }
        } while ($running > 0);

        // Procesar los resultados
        foreach ($channels as $index => $channel) {
            $ch = $channel['ch'];
            $study = $channel['study'];
            
            $series_response = curl_multi_getcontent($ch);
            $series_id = null;
            $modality = null;

            if ($series_response) {
                $series_list = json_decode($series_response, true);
                if (!empty($series_list)) {
                    $series_id = $series_list[0]['ID'] ?? null;
                    // Intentar obtener la modalidad en este orden:
                    // 1. Del estudio directamente
                    $modality = $study['MainDicomTags']['Modality'] ?? null;
                    
                    // 2. Si no está en el estudio, buscar en la primera serie
                    if (!$modality && isset($series_list[0])) {
                        $modality = $series_list[0]['MainDicomTags']['Modality'] ?? null;
                    }
                    
                    // 3. Si aún no hay modalidad, buscar en todas las series hasta encontrar una
                    if (!$modality) {
                        foreach ($series_list as $series) {
                            if (isset($series['MainDicomTags']['Modality'])) {
                                $modality = $series['MainDicomTags']['Modality'];
                                break;
                            }
                        }
                    }
                }
            }

            $study_last_update = $study['LastUpdate'] ?? date('Y-m-d H:i:s');

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
                // Verificar si el estudio existe y necesita actualización
                $check_stmt->execute([$study['ID']]);
                $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$existing) {
                    // Insertar nuevo estudio
                    $insert_stmt->execute([
                        $study['ID'],
                        $series_id,
                        $study['PatientMainDicomTags']['PatientID'] ?? null,
                        $study['PatientMainDicomTags']['PatientName'] ?? null,
                        $study_date, // Usar la fecha procesada
                        $modality, // Ahora guardamos la modalidad correctamente
                        $study['MainDicomTags']['StudyDescription'] ?? null,
                        $study_last_update
                    ]);
                } else if ($existing['last_update'] < $study_last_update || empty($existing['modality'])) {
                    // Actualizar estudio existente, incluyendo la modalidad si está vacía
                    $update_stmt->execute([
                        $series_id,
                        $study['PatientMainDicomTags']['PatientName'] ?? null,
                        $study_date, // Usar la fecha procesada
                        $modality, // Actualizamos la modalidad
                        $study['MainDicomTags']['StudyDescription'] ?? null,
                        $study_last_update,
                        $study['ID'],
                        $study_last_update
                    ]);
                }
                
                $processed++;
                
            } catch (PDOException $e) {
                error_log("Error procesando estudio {$study['ID']}: " . $e->getMessage());
                continue; // Continuar con el siguiente estudio
            }

            curl_multi_remove_handle($mh, $ch);
        }
        
        curl_multi_close($mh);
    }

    $connect->commit();
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
}
?> 