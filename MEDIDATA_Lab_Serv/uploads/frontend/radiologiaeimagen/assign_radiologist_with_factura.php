<?php
// Iniciar sesión y configurar headers ANTES de incluir otros archivos
session_start();
date_default_timezone_set('America/Tegucigalpa');

// Deshabilitar el reporte de errores para evitar interferencias
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configurar header JSON
header('Content-Type: application/json');

// Ahora incluir los archivos necesarios
require_once('../../backend/bd/Conexion.php');
require_once('validate_radiologist_user.php');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $study_id = $data['study_id'] ?? null;
    $radiologist_id = $data['radiologist_id'] ?? null;
    $factura_id = $data['factura_id'] ?? null; // Nueva: ID específico de factura
    $assignment_notes = $data['assignment_notes'] ?? '';
    $technician_id = $data['technician_id'] ?? $_SESSION['id'] ?? null; // Usar el que viene del JavaScript, fallback a sesión

    if (!$study_id || !$radiologist_id || !$technician_id) {
        throw new Exception('Datos incompletos: study_id, radiologist_id y technician_id son requeridos');
    }

    // Obtener nombre del técnico
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$technician_id]);
    $tech = $stmt->fetch(PDO::FETCH_ASSOC);
    $technician_name = $tech ? $tech['name'] : null;

    // === NUEVA VALIDACIÓN: Obtener user_id correspondiente ===
    $validation_result = validateAndGetUserIds($radiologist_id);
    
    if (!$validation_result) {
        throw new Exception('No se pudo validar el radiólogo');
    }
    
    $radiologist_name = $validation_result['doctor_name'];
    $user_id = $validation_result['user_id'];
    $remitente = $radiologist_name;
    
    // Si no existe el usuario, crearlo automáticamente
    if (!$user_id) {
        $user_id = createUserIfNotExists($radiologist_id);
        if (!$user_id) {
            throw new Exception('No se pudo crear el usuario para el radiólogo');
        }
        error_log("Usuario creado automáticamente para: " . $radiologist_name . " (ID: " . $user_id . ")");
    }
    
    error_log("Radiólogo validado: " . $radiologist_name . " (Doctor ID: " . $radiologist_id . ", User ID: " . $user_id . ")");

    if (!$technician_name || !$radiologist_name) {
        throw new Exception('No se pudo obtener el nombre del usuario o médico.');
    }

    // Obtener datos básicos del estudio desde worklist ANTES de la transacción
    error_log("Obteniendo datos del estudio...");
    $stmt = $connect->prepare("SELECT study_id, patient_id, patient_name, modality, study_description, study_date FROM worklist WHERE id = ?");
    $stmt->execute([$study_id]);
    $study = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$study) {
        throw new Exception('Estudio no encontrado en worklist');
    }

    // Iniciar transacción SOLO para las operaciones críticas
    error_log("Iniciando transacción...");
    $connect->beginTransaction();

    try {
        // 1. Asignar el radiólogo y técnico, y marcar como completado
        error_log("Actualizando worklist...");
        $stmt = $connect->prepare("UPDATE worklist SET radiologist_id = ?, radiologist_name = ?, technician_id = ?, technician_name = ?, status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$radiologist_id, $radiologist_name, $technician_id, $technician_name, $study_id]);

        // 2. Procesar vinculación con factura (si se proporcionó una)
        $factura_updated = false;
        $factura_info = null;

        if ($factura_id) {
            error_log("Procesando vinculación con factura ID: " . $factura_id);
            
            // Verificar que la factura existe y obtener información actual
            $stmt_factura = $connect->prepare("
                SELECT idord, invoice_number, nomcl, dni_paciente, placed_on, remitente, total_price 
                FROM orders 
                WHERE idord = ?
            ");
            $stmt_factura->execute([$factura_id]);
            $factura_data = $stmt_factura->fetch(PDO::FETCH_ASSOC);
            
            if ($factura_data) {
                $medico_anterior = $factura_data['remitente'] ?: 'Sin médico';
                
                // Actualizar el campo remitente en la factura específica
                error_log("Actualizando campo remitente en factura...");
                $stmt_update = $connect->prepare("UPDATE orders SET remitente = ? WHERE idord = ?");
                $result = $stmt_update->execute([$remitente, $factura_id]);
                
                if ($result) {
                    $factura_updated = true;
                    $factura_info = [
                        'numero' => $factura_data['invoice_number'],
                        'paciente' => $factura_data['nomcl'],
                        'fecha' => date('d/m/Y', strtotime($factura_data['placed_on'])),
                        'medico_anterior' => $medico_anterior,
                        'medico_nuevo' => $radiologist_name,
                        'total' => $factura_data['total_price']
                    ];
                    
                    error_log("EXITO: Factura actualizada - " . $factura_data['invoice_number'] . 
                              " | Médico: " . $medico_anterior . " → " . $radiologist_name);
                } else {
                    error_log("ERROR: No se pudo actualizar la factura " . $factura_id);
                }
            } else {
                error_log("ERROR: Factura no encontrada con ID " . $factura_id);
            }
        } else {
            error_log("Sin vinculación a factura - estudio completado sin generar honorarios");
        }

        // Confirmar transacción
        error_log("Confirmando transacción (commit)...");
        $connect->commit();
        error_log("Transacción confirmada exitosamente");

        // Operaciones POST-transacción (sin transacción)
        if ($factura_updated && $factura_info) {
            // Registrar el cambio en un log específico (sin transacción)
            error_log("Creando tabla de log si no existe...");
            $sql_create_log = "CREATE TABLE IF NOT EXISTS radiologia_factura_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                study_id VARCHAR(50) NOT NULL,
                factura_id INT NOT NULL,
                radiologist_id INT NOT NULL,
                radiologist_name VARCHAR(255) NOT NULL,
                medico_anterior VARCHAR(255),
                technician_id INT NOT NULL,
                technician_name VARCHAR(100) NOT NULL,
                assignment_notes TEXT,
                fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_study (study_id),
                INDEX idx_factura (factura_id),
                INDEX idx_fecha (fecha_asignacion)
            )";
            $connect->exec($sql_create_log);
            
            error_log("Insertando registro en log...");
            $stmt_log = $connect->prepare("
                INSERT INTO radiologia_factura_log 
                (study_id, factura_id, radiologist_id, radiologist_name, medico_anterior, technician_id, technician_name, assignment_notes, fecha_asignacion) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt_log->execute([
                $study['study_id'], $factura_id, $radiologist_id, $radiologist_name, 
                $medico_anterior, $technician_id, $technician_name, $assignment_notes
            ]);
        }

        // Verificar si ya existe un registro en radiology_reports para este study_id (sin transacción)
        error_log("Verificando radiology_reports...");
        $stmtCheck = $connect->prepare("SELECT id FROM radiology_reports WHERE study_id = ?");
        $stmtCheck->execute([$study['study_id']]);
        if (!$stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            // Insertar registro en radiology_reports (sin transacción)
            error_log("Insertando registro en radiology_reports...");
            // Obtener la hora actual en zona horaria de Honduras
            $fecha_actual = date('Y-m-d H:i:s');
            
            $stmtInsert = $connect->prepare("
                INSERT INTO radiology_reports (
                    study_id, patient_id, radiologist_id, radiologist_name, user_id, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            $stmtInsert->execute([
                $study['study_id'],
                $study['patient_id'],
                $radiologist_id,
                $radiologist_name,
                $user_id,
                $fecha_actual,
                $fecha_actual
            ]);
        }

        // Preparar respuesta exitosa
        $response = [
            'success' => true, 
            'message' => 'Estudio asignado y finalizado correctamente',
            'radiologist_name' => $radiologist_name,
            'factura_updated' => $factura_updated,
            'study_info' => [
                'patient_name' => $study['patient_name'],
                'patient_id' => $study['patient_id'],
                'study_id' => $study['study_id']
            ]
        ];

        if ($factura_info) {
            $response['factura_info'] = $factura_info;
        }

        error_log("Enviando respuesta JSON...");
        echo json_encode($response);
        error_log("Respuesta enviada exitosamente");

    } catch (Exception $e) {
        error_log("Error en transacción: " . $e->getMessage());
        // Verificar si hay una transacción activa antes de hacer rollback
        if ($connect->inTransaction()) {
            error_log("Haciendo rollback de transacción...");
            $connect->rollBack();
            error_log("Rollback completado");
        } else {
            error_log("No hay transacción activa para hacer rollback");
        }
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error en assign_radiologist_with_factura: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

error_log("FIN: Script completado");
?> 