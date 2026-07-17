<?php
require_once '../../backend/bd/Conexion.php';
session_start();
header('Content-Type: application/json');

date_default_timezone_set('America/Tegucigalpa');

/**
 * Ejecuta SQL auxiliar sin abortar el guardado principal del informe.
 */
function medidata_rx_save_optional(PDO $connect, string $label, callable $fn): void
{
    try {
        $fn();
    } catch (Throwable $e) {
        error_log('save_report.php [' . $label . ']: ' . $e->getMessage());
    }
}

try {
    $userId = (int) ($_SESSION['id'] ?? 0);
    if ($userId <= 0) {
        throw new Exception('Sesión no válida. Vuelva a iniciar sesión.');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        throw new Exception('Datos del informe no válidos.');
    }

    $stmt = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $radiologistName = $user ? (string) $user['name'] : '';

    $studyId = trim((string) ($data['study_id'] ?? ''));
    $clinicalHistory = trim((string) ($data['clinical_history'] ?? ''));
    $findings = trim((string) ($data['findings'] ?? ''));
    $impression = trim((string) ($data['impression'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'draft'));
    $isCritical = !empty($data['is_critical']) ? 1 : 0;
    $urgencyLevel = $isCritical ? trim((string) ($data['urgency_level'] ?? '')) : null;
    $notifiedTo = $isCritical ? trim((string) ($data['notified_to'] ?? '')) : null;

    if ($studyId === '' || $clinicalHistory === '' || $findings === '' || $impression === '') {
        throw new Exception('Todos los campos del informe son requeridos.');
    }

    $allowedStatus = ['draft', 'pending', 'pending_transcription', 'transcribed', 'reviewed', 'final'];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'draft';
    }

    $localTime = date('Y-m-d H:i:s');

    $connect->beginTransaction();

    $stmt = $connect->prepare('
        SELECT id, user_id
        FROM radiology_reports
        WHERE study_id = ?
        ORDER BY updated_at DESC, id DESC
        LIMIT 1
    ');
    $stmt->execute([$studyId]);
    $existingReport = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingReport) {
        $stmt = $connect->prepare("
            UPDATE radiology_reports
            SET clinical_history = ?,
                findings = ?,
                impression = ?,
                status = ?,
                is_critical = ?,
                urgency_level = ?,
                notified_to = ?,
                radiologist_id = ?,
                radiologist_name = ?,
                user_id = ?,
                updated_at = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $clinicalHistory,
            $findings,
            $impression,
            $status,
            $isCritical,
            $urgencyLevel !== '' ? $urgencyLevel : null,
            $notifiedTo !== '' ? $notifiedTo : null,
            $userId,
            $radiologistName,
            $userId,
            $localTime,
            (int) $existingReport['id'],
        ]);
        $reportId = (int) $existingReport['id'];
    } else {
        $stmt = $connect->prepare("
            INSERT INTO radiology_reports (
                study_id,
                radiologist_id,
                radiologist_name,
                user_id,
                clinical_history,
                findings,
                impression,
                status,
                is_critical,
                urgency_level,
                notified_to,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $studyId,
            $userId,
            $radiologistName,
            $userId,
            $clinicalHistory,
            $findings,
            $impression,
            $status,
            $isCritical,
            $urgencyLevel !== '' ? $urgencyLevel : null,
            $notifiedTo !== '' ? $notifiedTo : null,
            $localTime,
            $localTime,
        ]);
        $reportId = (int) $connect->lastInsertId();
    }

    $connect->commit();

    if ($isCritical) {
        medidata_rx_save_optional($connect, 'critical_findings', function () use ($connect, $reportId, $findings, $urgencyLevel, $notifiedTo, $userId) {
            $stmt = $connect->prepare('DELETE FROM critical_findings WHERE report_id = ?');
            $stmt->execute([$reportId]);

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
                $reportId,
                $findings,
                $urgencyLevel,
                $notifiedTo,
                $userId,
            ]);
        });
    }

    medidata_rx_save_optional($connect, 'productivity_stats', function () use ($connect, $userId) {
        $stmt = $connect->prepare("
            INSERT INTO productivity_stats (user_id, date, reports_created, created_at)
            VALUES (?, CURDATE(), 1, NOW())
            ON DUPLICATE KEY UPDATE reports_created = reports_created + 1
        ");
        $stmt->execute([$userId]);
    });

    if (in_array($status, ['pending_transcription', 'final'], true)) {
        medidata_rx_save_optional($connect, 'report_transcriptions', function () use (
            $connect,
            $reportId,
            $clinicalHistory,
            $findings,
            $impression
        ) {
            $stmt = $connect->prepare('SELECT id, status FROM report_transcriptions WHERE report_id = ? LIMIT 1');
            $stmt->execute([$reportId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if (($existing['status'] ?? '') === 'completed') {
                    return;
                }
                $stmt = $connect->prepare("
                    UPDATE report_transcriptions
                    SET status = 'pending',
                        clinical_history = COALESCE(clinical_history, ?),
                        findings = COALESCE(findings, ?),
                        impression = COALESCE(impression, ?),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$clinicalHistory, $findings, $impression, (int) $existing['id']]);
                return;
            }

            $stmt = $connect->prepare("
                INSERT INTO report_transcriptions (
                    report_id,
                    clinical_history,
                    findings,
                    impression,
                    status,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
            ");
            $stmt->execute([$reportId, $clinicalHistory, $findings, $impression]);
        });
    }

    echo json_encode([
        'success' => true,
        'message' => 'Informe guardado correctamente',
        'report_id' => $reportId,
    ]);
} catch (Throwable $e) {
    if (isset($connect) && $connect instanceof PDO && $connect->inTransaction()) {
        $connect->rollBack();
    }
    error_log('save_report.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
