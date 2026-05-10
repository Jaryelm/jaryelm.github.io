<?php
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
session_start();
header('Content-Type: application/json');

date_default_timezone_set('America/Tegucigalpa');
$local_time = date('Y-m-d H:i:s');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $radiologist_id = $_SESSION['id'];
    
    // Obtener nombre del radiólogo
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$radiologist_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $radiologist_name = $user ? $user['name'] : null;

    // Validar datos requeridos
    if (empty($data['study_id']) || empty($data['clinical_history']) || 
        empty($data['findings']) || empty($data['impression'])) {
        throw new Exception("Todos los campos son requeridos");
    }

    $connect->beginTransaction();

    // Verificar si ya existe un informe para este estudio
    $stmt = $connect->prepare("
        SELECT id FROM radiology_reports 
        WHERE study_id = ?
    ");
    $stmt->execute([$data['study_id']]);
    $existing_report = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_report) {
        // Actualizar informe existente
        $stmt = $connect->prepare("
            UPDATE radiology_reports 
            SET clinical_history = ?,
                findings = ?,
                impression = ?,
                status = ?,
                is_critical = ?,
                urgency_level = ?,
                notified_to = ?,
                radiologist_name = ?,
                updated_at = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['clinical_history'],
            $data['findings'],
            $data['impression'],
            $data['status'],
            $data['is_critical'],
            $data['urgency_level'] ?? null,
            $data['notified_to'] ?? null,
            $radiologist_name,
            $local_time,
            $existing_report['id']
        ]);

        $report_id = $existing_report['id'];
    } else {
        // Insertar nuevo informe
        $stmt = $connect->prepare("
            INSERT INTO radiology_reports (
                study_id,
                radiologist_id,
                radiologist_name,
                clinical_history,
                findings,
                impression,
                status,
                is_critical,
                urgency_level,
                notified_to,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['study_id'],
            $radiologist_id,
            $radiologist_name,
            $data['clinical_history'],
            $data['findings'],
            $data['impression'],
            $data['status'],
            $data['is_critical'],
            $data['urgency_level'] ?? null,
            $data['notified_to'] ?? null,
            $local_time,
            $local_time
        ]);

        $report_id = $connect->lastInsertId();
    }

    // Si es un hallazgo crítico, registrarlo
    if ($data['is_critical']) {
        // Eliminar hallazgos críticos anteriores si existen
        $stmt = $connect->prepare("
            DELETE FROM critical_findings 
            WHERE report_id = ?
        ");
        $stmt->execute([$report_id]);

        // Insertar nuevo hallazgo crítico
        $stmt = $connect->prepare("
            INSERT INTO critical_findings (
                report_id,
                finding_description,
                urgency_level,
                notified_to,
                notification_time,
                created_by
            ) VALUES (?, ?, ?, ?, NOW(), ?)
        ");

        $stmt->execute([
            $report_id,
            $data['findings'],
            $data['urgency_level'],
            $data['notified_to'],
            $radiologist_id
        ]);
    }

    // Actualizar estadísticas de productividad
    $stmt = $connect->prepare("
        INSERT INTO productivity_stats (
            user_id,
            date,
            reports_created,
            created_at
        ) VALUES (?, CURDATE(), 1, NOW())
        ON DUPLICATE KEY UPDATE
        reports_created = reports_created + 1
    ");
    $stmt->execute([$radiologist_id]);

    // Si el informe se finaliza (status = 'final'), crear registro en report_transcriptions si no existe
    if ($data['status'] === 'final') {
        // Verificar si ya existe una transcripción para este informe
        $stmt = $connect->prepare("SELECT id FROM report_transcriptions WHERE report_id = ?");
        $stmt->execute([$report_id]);
        $existing_transcription = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing_transcription) {
            $stmt = $connect->prepare("
                INSERT INTO report_transcriptions (
                    report_id, transcriber_id, status, created_at, updated_at
                ) VALUES (?, NULL, 'pending', NOW(), NOW())
            ");
            $stmt->execute([$report_id]);
        }
    }

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Informe guardado correctamente',
        'report_id' => $report_id
    ]);

} catch (Exception $e) {
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 