<?php

/**
 * Crea filas faltantes en report_transcriptions para informes ya enviados.
 */
function medidata_sync_transcription_queue(PDO $connect): void
{
    try {
        $stmt = $connect->query("
            SELECT r.id, r.clinical_history, r.findings, r.impression
            FROM radiology_reports r
            LEFT JOIN report_transcriptions rt ON rt.report_id = r.id
            WHERE rt.id IS NULL
              AND r.status IN ('pending_transcription', 'final')
        ");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($rows as $row) {
            try {
                $ins = $connect->prepare("
                    INSERT INTO report_transcriptions (
                        report_id, clinical_history, findings, impression, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())
                ");
                $ins->execute([
                    (int) $row['id'],
                    $row['clinical_history'] ?? null,
                    $row['findings'] ?? null,
                    $row['impression'] ?? null,
                ]);
            } catch (Throwable $e) {
                $ins = $connect->prepare("
                    INSERT INTO report_transcriptions (report_id, status, created_at, updated_at)
                    VALUES (?, 'pending', NOW(), NOW())
                ");
                $ins->execute([(int) $row['id']]);
            }
        }
    } catch (Throwable $e) {
        error_log('medidata_sync_transcription_queue: ' . $e->getMessage());
    }
}
