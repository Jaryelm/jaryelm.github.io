<?php
ob_start();

require_once __DIR__ . '/../../backend/registros/session_check.php';
require_once __DIR__ . '/../../backend/bd/Conexion.php';
require_once __DIR__ . '/../../backend/fpdf/fpdf.php';
require_once __DIR__ . '/rx_pdf_lib.php';

date_default_timezone_set('America/Tegucigalpa');

$rootDir = dirname(__DIR__, 2);
$imgDir = $rootDir . '/backend/img';
$tempDir = rx_pdf_ensure_temp_dir($rootDir . '/backend/temp');

$reportId = isset($_GET['report_id']) ? (int) $_GET['report_id'] : 0;
if ($reportId <= 0) {
    rx_pdf_fail(400, 'ID de informe no proporcionado');
}

$tempCleanup = [];

try {
    rx_pdf_backfill_radiologist_ids($connect);

    $stmt = $connect->prepare("
        SELECT
            rt.*,
            w.patient_name,
            w.patient_id,
            w.study_description,
            w.modality,
            w.study_date,
            w.study_id,
            r.user_id AS report_user_id,
            r.radiologist_id,
            r.radiologist_name,
            w.radiologist_id AS worklist_radiologist_id,
            w.radiologist_name AS worklist_radiologist_name,
            u.name AS transcriber_name
        FROM report_transcriptions rt
        INNER JOIN radiology_reports r ON rt.report_id = r.id
        INNER JOIN worklist w ON r.study_id = w.study_id
        LEFT JOIN users u ON rt.transcriber_id = u.id
        WHERE rt.report_id = ?
          AND rt.status = 'completed'
        ORDER BY rt.completed_at DESC, rt.id DESC
        LIMIT 1
    ");
    $stmt->execute([$reportId]);
    $transcription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transcription) {
        rx_pdf_fail(404, 'Transcripción completada no encontrada para este informe.');
    }

    $sigInfo = rx_pdf_resolve_radiologist_signature($connect, $transcription);
    $sigPath = null;
    $sigType = 'JPEG';
    if ($sigInfo['blob']) {
        $preparedSig = rx_pdf_prepare_image_file($sigInfo['blob'], $tempDir, 'sig');
        if ($preparedSig) {
            $sigPath = $preparedSig['path'];
            $sigType = $preparedSig['type'];
            $tempCleanup[] = $sigPath;
        }
    }

    $qrPath = null;
    $qrType = 'JPEG';
    $qrRaw = rx_pdf_generate_qr_file(
        $rootDir,
        $tempDir,
        (string) ($transcription['study_id'] ?? ''),
        (int) ($transcription['id'] ?? $reportId)
    );
    if ($qrRaw) {
        $tempCleanup[] = $qrRaw;
        $preparedQr = rx_pdf_prepare_existing_image($qrRaw, $tempDir, 'qr');
        if ($preparedQr) {
            $qrPath = $preparedQr['path'];
            $qrType = $preparedQr['type'];
            $tempCleanup[] = $qrPath;
        } else {
            $qrPath = $qrRaw;
            $qrType = 'PNG';
        }
    }

    $pdf = new RxInformePDF('P', 'mm', 'Letter');
    $pdf->sigImagePath = $sigPath;
    $pdf->sigImageType = $sigType;
    $pdf->sigDoctorName = $sigInfo['name'];
    $pdf->qrImagePath = $qrPath;
    $pdf->qrImageType = $qrType;
    $pdf->AddPage();

    rx_pdf_write_letterhead($pdf, $imgDir);
    rx_pdf_write_report_title($pdf, (string) ($transcription['report_title'] ?? 'INFORME RADIOLÓGICO'));
    rx_pdf_write_patient_block($pdf, $transcription);

    rx_pdf_write_section($pdf, 'INDICIO:', $transcription['clinical_history'] ?? '');
    rx_pdf_write_section($pdf, 'HALLAZGOS:', $transcription['findings'] ?? '');
    rx_pdf_write_section($pdf, 'IMPRESIÓN DIAGNÓSTICA:', $transcription['impression'] ?? '');
    rx_pdf_write_section($pdf, 'RECOMENDACIONES:', $transcription['comments'] ?? '');

    $filename = rx_pdf_build_download_filename($transcription);
    $inline = isset($_GET['view']) && $_GET['view'] === 'inline';
    $dest = $inline ? 'I' : 'D';

    if (ob_get_length()) {
        ob_end_clean();
    }

    $pdf->drawClosingAssets();
    $pdf->Output($dest, $filename);
    rx_pdf_cleanup_paths($tempCleanup);
    exit;
} catch (Throwable $e) {
    rx_pdf_cleanup_paths($tempCleanup);
    error_log('generar_pdf_informe.php: ' . $e->getMessage());
    rx_pdf_fail(500, 'No se pudo generar el PDF del informe. ' . $e->getMessage());
}
