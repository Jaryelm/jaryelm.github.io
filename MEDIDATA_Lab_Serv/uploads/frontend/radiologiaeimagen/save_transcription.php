<?php
require_once('../../backend/bd/Conexion.php'); // Incluir el archivo de conexión
session_start();
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $transcriber_id = $_SESSION['id'];
    
    // Validar datos requeridos
    if (empty($data['report_id']) || empty($data['findings']) || 
        empty($data['impression']) || empty($data['report_title'])) {
        throw new Exception("Todos los campos son requeridos");
    }

    $connect->beginTransaction();

    // Obtener nombre del transcriptor
    $stmt = $connect->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$transcriber_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $transcriber_name = $user ? $user['name'] : null;

    // Verificar si ya existe una transcripción
    $stmt = $connect->prepare("
        SELECT id FROM report_transcriptions 
        WHERE report_id = ?
    ");
    $stmt->execute([$data['report_id']]);
    $existing_transcription = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_transcription) {
        // Actualizar transcripción existente
        $stmt = $connect->prepare("
            UPDATE report_transcriptions 
            SET clinical_history = ?,
                findings = ?,
                impression = ?,
                status = ?,
                comments = ?,
                report_title = ?,
                transcriber_id = ?,
                transcriber_name = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $data['clinical_history'] ?? null,
            $data['findings'],
            $data['impression'],
            $data['status'],
            $data['comments'] ?? null,
            $data['report_title'],
            $transcriber_id,
            $transcriber_name,
            $existing_transcription['id']
        ]);
        $transcription_id = $existing_transcription['id'];
    } else {
        // Insertar nueva transcripción SIEMPRE como 'pending' (no completed)
        $stmt = $connect->prepare("
            INSERT INTO report_transcriptions (
                report_id,
                transcriber_id,
                transcriber_name,
                clinical_history,
                findings,
                impression,
                status,
                comments,
                report_title,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['report_id'],
            $transcriber_id,
            $transcriber_name,
            $data['clinical_history'] ?? null,
            $data['findings'],
            $data['impression'],
            $data['comments'] ?? null,
            $data['report_title']
        ]);
        $transcription_id = $connect->lastInsertId();
    }

    // Si la transcripción está completada, actualizar el estado y la fecha
    if ($data['status'] === 'completed') {
        $stmt = $connect->prepare("
            UPDATE report_transcriptions 
            SET completed_at = NOW(), status = 'completed'
            WHERE id = ?
        ");
        $stmt->execute([$transcription_id]);
    }

    // Actualizar estadísticas de productividad
    $stmt = $connect->prepare("
        INSERT INTO productivity_stats (
            user_id,
            date,
            transcriptions_completed,
            created_at
        ) VALUES (?, CURDATE(), 1, NOW())
        ON DUPLICATE KEY UPDATE
        transcriptions_completed = transcriptions_completed + 1
    ");
    $stmt->execute([$transcriber_id]);

    $connect->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transcripción guardada correctamente',
        'transcription_id' => $transcription_id
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