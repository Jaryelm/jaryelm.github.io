<?php
declare(strict_types=1);

session_start();
date_default_timezone_set('America/Tegucigalpa');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/validate_radiologist_user.php';

try {
    if (!isset($_SESSION['id'])) {
        throw new Exception('Acceso no autorizado.');
    }

    $data = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Datos inválidos.');
    }

    $worklistId = (int) ($data['study_id'] ?? 0);
    $radiologistId = (int) ($data['radiologist_id'] ?? 0);
    $technicianId = (int) ($data['technician_id'] ?? $_SESSION['id'] ?? 0);

    if ($worklistId <= 0 || $radiologistId <= 0 || $technicianId <= 0) {
        throw new Exception('Datos incompletos: estudio, médico radiólogo y técnico son requeridos.');
    }

    $stmt = $connect->prepare(
        'SELECT id, study_id, patient_id, patient_name, radiologist_id, radiologist_name, status
         FROM worklist WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$worklistId]);
    $worklist = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$worklist) {
        throw new Exception('Estudio no encontrado en la lista de trabajo.');
    }

    if (($worklist['status'] ?? '') === 'cancelled') {
        throw new Exception('No se puede reasignar un estudio cancelado.');
    }

    $stmtReport = $connect->prepare(
        'SELECT id, status, user_id FROM radiology_reports WHERE study_id = ? ORDER BY id DESC LIMIT 1'
    );
    $stmtReport->execute([$worklist['study_id']]);
    $report = $stmtReport->fetch(PDO::FETCH_ASSOC);

    $blockedStatuses = ['pending_transcription', 'final', 'transcribed', 'reviewed'];
    if ($report && in_array((string) $report['status'], $blockedStatuses, true)) {
        throw new Exception(
            'No se puede reasignar: el informe ya avanzó en el flujo (' . $report['status'] . ').'
        );
    }

    if ((int) ($worklist['radiologist_id'] ?? 0) === $radiologistId) {
        throw new Exception('El estudio ya está asignado a ese médico radiólogo.');
    }

    $validation = validateAndGetUserIds($radiologistId);
    if (!$validation) {
        throw new Exception('No se pudo validar el médico radiólogo seleccionado.');
    }

    $radiologistName = $validation['doctor_name'];
    $userId = $validation['user_id'];
    if (!$userId) {
        $userId = createUserIfNotExists($radiologistId);
        if (!$userId) {
            throw new Exception('No se pudo vincular el usuario del médico radiólogo.');
        }
    }

    $stmtTech = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmtTech->execute([$technicianId]);
    $technicianName = (string) ($stmtTech->fetchColumn() ?: '');

    $previousRadiologist = trim((string) ($worklist['radiologist_name'] ?? ''));
    $now = date('Y-m-d H:i:s');

    $connect->beginTransaction();

    $stmtUpdateWl = $connect->prepare(
        'UPDATE worklist
         SET radiologist_id = ?, radiologist_name = ?, status = \'completed\', updated_at = NOW()
         WHERE id = ?'
    );
    $stmtUpdateWl->execute([$radiologistId, $radiologistName, $worklistId]);

    if ($report) {
        $stmtUpdateReport = $connect->prepare(
            'UPDATE radiology_reports
             SET radiologist_id = ?, radiologist_name = ?, user_id = ?, updated_at = ?
             WHERE id = ?'
        );
        $stmtUpdateReport->execute([
            $radiologistId,
            $radiologistName,
            $userId,
            $now,
            (int) $report['id'],
        ]);
    } else {
        $stmtInsertReport = $connect->prepare(
            "INSERT INTO radiology_reports (
                study_id, patient_id, radiologist_id, radiologist_name, user_id, status, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)"
        );
        $stmtInsertReport->execute([
            $worklist['study_id'],
            $worklist['patient_id'],
            $radiologistId,
            $radiologistName,
            $userId,
            $now,
            $now,
        ]);
    }

    $connect->commit();

    echo json_encode([
        'success'              => true,
        'message'              => 'Estudio reasignado correctamente.',
        'radiologist_name'     => $radiologistName,
        'previous_radiologist' => $previousRadiologist !== '' ? $previousRadiologist : null,
        'study_info'           => [
            'patient_name' => $worklist['patient_name'],
            'patient_id'   => $worklist['patient_id'],
            'study_id'     => $worklist['study_id'],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($connect) && $connect->inTransaction()) {
        $connect->rollBack();
    }
    error_log('reassign_radiologist.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
