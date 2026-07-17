<?php
/**
 * Utilidades compartidas para generar PDF de informes radiológicos transcritos.
 */

function rx_pdf_fail(int $code, string $message): void
{
    if (ob_get_length()) {
        ob_end_clean();
    }
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
    exit;
}

function rx_pdf_to_latin(?string $text): string
{
    $text = (string) $text;
    if ($text === '') {
        return '';
    }
    $converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
    return $converted !== false ? $converted : utf8_decode($text);
}

function rx_pdf_formatear_fecha($fecha): string
{
    if (!$fecha) {
        return '';
    }
    try {
        $fechaObj = new DateTime((string) $fecha);
    } catch (Exception $e) {
        return (string) $fecha;
    }
    $meses = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
        5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
        9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];
    $mes = (int) $fechaObj->format('n');
    return $fechaObj->format('j') . ' DE ' . ($meses[$mes] ?? '') . ' DE ' . $fechaObj->format('Y');
}

function rx_pdf_limpiar_nombre(?string $nombre): string
{
    $nombre = preg_replace('/[\^\\*]/', ' ', (string) $nombre);
    $nombre = preg_replace('/\s+/', ' ', trim($nombre));
    return ucwords(strtolower($nombre));
}

function rx_pdf_slug_part(?string $text, string $fallback = 'sin_dato'): string
{
    $text = preg_replace('/[\^\\*\/\\\\:?<>|"]/', ' ', (string) $text);
    $text = preg_replace('/\s+/', '_', trim($text));
    if ($text === '' || $text === '_') {
        return $fallback;
    }

    $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if (is_string($ascii) && $ascii !== '') {
        $text = $ascii;
    }

    $text = preg_replace('/[^A-Za-z0-9_\-]/', '', $text);
    $text = preg_replace('/_+/', '_', $text);
    $text = trim($text, '_');

    return $text !== '' ? $text : $fallback;
}

function rx_pdf_build_download_filename(array $row): string
{
    $patient = rx_pdf_slug_part(rx_pdf_limpiar_nombre($row['patient_name'] ?? ''), 'Paciente');
    $study = trim((string) ($row['study_description'] ?? ''));
    if ($study === '') {
        $study = trim((string) ($row['modality'] ?? ''));
    }
    $study = rx_pdf_slug_part($study, 'Estudio');

    $date = '';
    if (!empty($row['study_date'])) {
        $ts = strtotime((string) $row['study_date']);
        if ($ts !== false) {
            $date = date('Y-m-d', $ts);
        }
    }

    $filename = 'Informe_' . $patient . '_' . $study;
    if ($date !== '') {
        $filename .= '_' . $date;
    }

    return $filename . '.pdf';
}

function rx_pdf_calcular_edad(?string $identidad): string
{
    if (empty($identidad)) {
        return 'N/A';
    }
    if (preg_match('/^\d{4}-(\d{4})-\d{5}$/', $identidad, $matches)) {
        $anioNacimiento = (int) $matches[1];
        $edad = (int) date('Y') - $anioNacimiento;
        if ($edad >= 0 && $edad <= 120) {
            return $edad . ' años';
        }
    }
    return 'N/A';
}

function rx_pdf_resolve_logo(string $imgDir): ?string
{
    foreach ([$imgDir . '/logo_medicasa.png', $imgDir . '/icon.png'] as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

function rx_pdf_blob_to_string($blob): ?string
{
    if (is_resource($blob)) {
        $blob = stream_get_contents($blob);
    }
    if (!is_string($blob) || $blob === '') {
        return null;
    }
    if (strncmp($blob, 'data:image', 10) === 0) {
        $blob = base64_decode((string) preg_replace('#^data:image/\w+;base64,#', '', $blob), true) ?: '';
    }
    return strlen($blob) >= 8 ? $blob : null;
}

function rx_pdf_signature_blob(PDO $connect, int $userId): ?string
{
    if ($userId < 1) {
        return null;
    }

    try {
        $stmt = $connect->prepare('SELECT TO_BASE64(signature) AS signature_b64 FROM user_signatures WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $b64 = $stmt->fetchColumn();
        if (is_string($b64) && $b64 !== '') {
            $decoded = base64_decode($b64, true);
            if ($decoded !== false && $decoded !== '') {
                return rx_pdf_blob_to_string($decoded);
            }
        }
    } catch (Throwable $e) {
        error_log('rx_pdf_signature_blob TO_BASE64: ' . $e->getMessage());
    }

    $stmt = $connect->prepare('SELECT signature FROM user_signatures WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row) || !isset($row['signature'])) {
        return null;
    }
    return rx_pdf_blob_to_string($row['signature']);
}

function rx_pdf_lookup_user_id_by_name(PDO $connect, string $nombre): int
{
    $nombre = trim($nombre);
    if ($nombre === '' || $nombre === '—' || $nombre === '-') {
        return 0;
    }

    $stmt = $connect->prepare('SELECT id FROM users WHERE TRIM(LOWER(name)) = TRIM(LOWER(?)) LIMIT 1');
    $stmt->execute([$nombre]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    $stmt = $connect->prepare('SELECT id FROM users WHERE TRIM(name) LIKE ? ORDER BY LENGTH(name) ASC LIMIT 1');
    $stmt->execute(['%' . $nombre . '%']);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    $tokens = preg_split('/\s+/', $nombre);
    if (is_array($tokens) && count($tokens) >= 2) {
        $stmt = $connect->prepare('SELECT id FROM users WHERE TRIM(name) LIKE ? AND TRIM(name) LIKE ? LIMIT 1');
        $stmt->execute(['%' . $tokens[0] . '%', '%' . $tokens[count($tokens) - 1] . '%']);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }
    }

    return 0;
}

/**
 * @return array{user_id: int, blob: ?string, name: string}
 */
function rx_pdf_resolve_radiologist_signature(PDO $connect, array $row): array
{
    $candidateIds = [];
    foreach (['radiologist_id', 'report_user_id', 'worklist_radiologist_id'] as $key) {
        $uid = (int) ($row[$key] ?? 0);
        if ($uid > 0) {
            $candidateIds[] = $uid;
        }
    }

    $names = array_unique(array_filter([
        trim((string) ($row['radiologist_name'] ?? '')),
        trim((string) ($row['worklist_radiologist_name'] ?? '')),
    ]));

    foreach ($names as $nombre) {
        $uid = rx_pdf_lookup_user_id_by_name($connect, $nombre);
        if ($uid > 0) {
            $candidateIds[] = $uid;
        }
    }

    $candidateIds = array_values(array_unique(array_filter($candidateIds)));
    $displayName = trim((string) ($row['radiologist_name'] ?? ''));
    if ($displayName === '') {
        $displayName = trim((string) ($row['worklist_radiologist_name'] ?? ''));
    }

    foreach ($candidateIds as $uid) {
        $check = $connect->prepare('SELECT id, name FROM users WHERE id = ? LIMIT 1');
        $check->execute([$uid]);
        $user = $check->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            continue;
        }
        $blob = rx_pdf_signature_blob($connect, (int) $user['id']);
        if ($blob) {
            $name = $displayName !== '' ? $displayName : (string) ($user['name'] ?? '');
            return ['user_id' => (int) $user['id'], 'blob' => $blob, 'name' => $name];
        }
    }

    $fallbackId = $candidateIds[0] ?? 0;
    if ($fallbackId > 0 && $displayName === '') {
        $stmt = $connect->prepare('SELECT name FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$fallbackId]);
        $displayName = trim((string) ($stmt->fetchColumn() ?: ''));
    }

    return ['user_id' => $fallbackId, 'blob' => null, 'name' => $displayName];
}

function rx_pdf_ensure_temp_dir(string $preferred): string
{
    $candidates = [$preferred, sys_get_temp_dir() . '/medidata_rx_pdf'];
    foreach ($candidates as $dir) {
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            continue;
        }
        if (is_writable($dir)) {
            return $dir;
        }
    }
    return $preferred;
}

/**
 * Convierte un blob de imagen a archivo embebible por FPDF (JPEG sobre fondo blanco).
 *
 * @return array{path: string, type: string}|null
 */
function rx_pdf_prepare_image_file($blob, string $tempDir, string $prefix): ?array
{
    $blob = rx_pdf_blob_to_string($blob);
    if ($blob === null) {
        return null;
    }

    $dir = rx_pdf_ensure_temp_dir($tempDir);

    if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring($blob);
        if ($src !== false) {
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w > 0 && $h > 0) {
                $dst = imagecreatetruecolor($w, $h);
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefill($dst, 0, 0, $white);
                imagealphablending($dst, true);
                imagecopy($dst, $src, 0, 0, 0, 0, $w, $h);
                $jpg = $dir . '/' . $prefix . '_' . uniqid('', true) . '.jpg';
                if (@imagejpeg($dst, $jpg, 92)) {
                    imagedestroy($src);
                    imagedestroy($dst);
                    return ['path' => $jpg, 'type' => 'JPEG'];
                }
                imagedestroy($dst);
            }
            imagedestroy($src);
        }
    }

    $info = @getimagesizefromstring($blob);
    if (is_array($info) && !empty($info['mime'])) {
        $ext = 'png';
        $type = 'PNG';
        if (strpos($info['mime'], 'jpeg') !== false || strpos($info['mime'], 'jpg') !== false) {
            $ext = 'jpg';
            $type = 'JPEG';
        } elseif (strpos($info['mime'], 'gif') !== false) {
            $ext = 'gif';
            $type = 'GIF';
        }
        $path = $dir . '/' . $prefix . '_' . uniqid('', true) . '.' . $ext;
        if (@file_put_contents($path, $blob) !== false) {
            return ['path' => $path, 'type' => $type];
        }
    }

    error_log('rx_pdf_prepare_image_file: no se pudo preparar imagen (' . $prefix . ')');
    return null;
}

/**
 * @return array{path: string, type: string}|null
 */
function rx_pdf_prepare_existing_image(string $filePath, string $tempDir, string $prefix): ?array
{
    if (!is_file($filePath)) {
        return null;
    }
    $data = @file_get_contents($filePath);
    if ($data === false || $data === '') {
        return null;
    }
    return rx_pdf_prepare_image_file($data, $tempDir, $prefix);
}

function rx_pdf_signature_temp_png(string $blob, string $tempDir): ?string
{
    $prepared = rx_pdf_prepare_image_file($blob, $tempDir, 'sig');
    return $prepared['path'] ?? null;
}

function rx_pdf_maybe_transparent_png(string $path): string
{
    return $path;
}

function rx_pdf_generate_qr_file(string $rootDir, string $tempDir, string $studyId, int $suffix): ?string
{
    $studyId = trim($studyId);
    if ($studyId === '') {
        return null;
    }
    $dir = rx_pdf_ensure_temp_dir($tempDir);
    $qrFile = $dir . '/qr_' . $suffix . '_' . uniqid('', true) . '.png';
    try {
        require_once $rootDir . '/backend/phpqrcode/qrlib.php';
        $qrUrl = 'https://medicloud.medicasa.hn/orthanc/studies/' . rawurlencode($studyId) . '/archive';
        QRcode::png($qrUrl, $qrFile, QR_ECLEVEL_L, 6, 2);
        if (is_file($qrFile) && filesize($qrFile) > 0) {
            return $qrFile;
        }
    } catch (Throwable $e) {
        error_log('rx_pdf_generate_qr_file: ' . $e->getMessage());
    }
    if (is_file($qrFile)) {
        @unlink($qrFile);
    }
    return null;
}

function rx_pdf_write_letterhead(RxInformePDF $pdf, string $imgDir): void
{
    $pdf->SetFont('Arial', '', 8.5);
    $pdf->SetTextColor(90, 90, 90);
    $x = RxInformePDF::M_LEFT;
    $pdf->SetXY($x, RxInformePDF::M_TOP);
    foreach ([
        'Comayagüela 7ma ave. Entre 10 y 11 calle edificio 949',
        'Esquina Opuesta Mercado Mama Chepa Tel. 2242-6281/2242-6272(1)',
        'Honduras, Centro América',
        'www.medicasa.hn',
    ] as $line) {
        $pdf->SetX($x);
        $pdf->Cell(0, 3.8, rx_pdf_to_latin($line), 0, 1);
    }

    $logoPath = rx_pdf_resolve_logo($imgDir);
    if ($logoPath) {
        $pdf->Image($logoPath, $pdf->GetPageWidth() - RxInformePDF::M_RIGHT - 46, RxInformePDF::M_TOP - 1, 46);
    }

    $pdf->SetY(38);
}

function rx_pdf_write_report_title(RxInformePDF $pdf, string $title): void
{
    $w = $pdf->GetPageWidth() - RxInformePDF::M_LEFT - RxInformePDF::M_RIGHT;
    $pdf->SetFont('Arial', 'B', 15);
    $pdf->SetTextColor(3, 92, 103);
    $pdf->SetX(RxInformePDF::M_LEFT);
    $pdf->Cell($w, 8, rx_pdf_to_latin($title), 0, 1, 'C');
    $pdf->Ln(3);
}

function rx_pdf_write_patient_block(RxInformePDF $pdf, array $data): void
{
    $labelW = 24;
    $rows = [
        'NOMBRE:' => rx_pdf_limpiar_nombre($data['patient_name'] ?? ''),
        'EDAD:' => rx_pdf_calcular_edad($data['patient_id'] ?? ''),
        'IDENTIDAD:' => (string) ($data['patient_id'] ?? 'N/A'),
        'FECHA:' => rx_pdf_formatear_fecha($data['study_date'] ?? ''),
    ];

    $pdf->SetTextColor(0, 0, 0);
    foreach ($rows as $label => $value) {
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetX(RxInformePDF::M_LEFT);
        $pdf->Cell($labelW, 4.8, rx_pdf_to_latin($label), 0, 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 4.8, rx_pdf_to_latin($value), 0, 1);
    }
    $pdf->Ln(4);
}

function rx_pdf_write_section(RxInformePDF $pdf, string $title, ?string $text): void
{
    $text = trim((string) $text);
    if ($text === '') {
        return;
    }

    $contentW = $pdf->GetPageWidth() - RxInformePDF::M_LEFT - RxInformePDF::M_RIGHT;
    $usableBottom = $pdf->GetPageHeight() - RxInformePDF::FOOTER_LINE_H - 8;
    if ($pdf->GetY() + 14 > $usableBottom) {
        $pdf->AddPage();
    }

    $pdf->Ln(2);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(3, 92, 103);
    $pdf->SetX(RxInformePDF::M_LEFT);
    $pdf->Cell($contentW, 5, rx_pdf_to_latin($title), 0, 1);
    $pdf->Ln(1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetX(RxInformePDF::M_LEFT);
    $pdf->MultiCell($contentW, 5, rx_pdf_to_latin($text), 0, 'J');
}

class RxInformePDF extends FPDF
{
    public const M_LEFT = 20.0;
    public const M_RIGHT = 20.0;
    public const M_TOP = 15.0;
    public const FOOTER_LINE_H = 10.0;
    public const CLOSING_BLOCK_H = 46.0;

    /** @var ?string */
    public $sigImagePath = null;
    /** @var string */
    public $sigImageType = 'JPEG';
    /** @var string */
    public $sigDoctorName = '';
    /** @var ?string */
    public $qrImagePath = null;
    /** @var string */
    public $qrImageType = 'JPEG';

    public function __construct($orientation = 'P', $unit = 'mm', $size = 'Letter')
    {
        parent::__construct($orientation, $unit, $size);
        $this->SetMargins(self::M_LEFT, self::M_TOP, self::M_RIGHT);
        $this->SetAutoPageBreak(true, self::FOOTER_LINE_H + 8);
    }

    /**
     * Bloque final: firma + QR en la última página, sin forzar páginas en blanco.
     */
    public function drawClosingAssets(): void
    {
        $pageH = $this->GetPageHeight();
        $pageW = $this->GetPageWidth();
        $blockTop = $pageH - self::FOOTER_LINE_H - self::CLOSING_BLOCK_H;
        $overlap = $this->GetY() - $blockTop;

        if ($overlap > 12) {
            $this->AddPage();
            $pageH = $this->GetPageHeight();
            $pageW = $this->GetPageWidth();
            $blockTop = $pageH - self::FOOTER_LINE_H - self::CLOSING_BLOCK_H;
        }

        $this->SetAutoPageBreak(false);

        $sigX = self::M_LEFT;
        $sigW = 62.0;
        $sigH = 18.0;
        $lineW = 88.0;
        $sigY = $blockTop;
        $lineY = $sigY + $sigH + 3.0;
        $nombre = trim($this->sigDoctorName);

        if ($this->sigImagePath && is_file($this->sigImagePath)) {
            try {
                $this->Image($this->sigImagePath, $sigX, $sigY, $sigW, $sigH, $this->sigImageType);
            } catch (Throwable $e) {
                error_log('RxInformePDF firma: ' . $e->getMessage());
            }
        }

        $this->SetDrawColor(3, 92, 103);
        $this->SetLineWidth(0.4);
        $this->Line($sigX, $lineY, $sigX + $lineW, $lineY);
        $this->SetDrawColor(0, 0, 0);
        $this->SetLineWidth(0.2);

        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Text($sigX, $lineY + 5.5, rx_pdf_to_latin($nombre !== '' ? $nombre : 'Médico radiólogo'));
        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(6, 173, 191);
        $this->Text($sigX, $lineY + 10.5, rx_pdf_to_latin('Médico radiólogo'));
        $this->SetTextColor(0, 0, 0);

        if ($this->qrImagePath && is_file($this->qrImagePath)) {
            $qrSize = 30.0;
            $qrX = $pageW - self::M_RIGHT - $qrSize;
            $qrY = $blockTop + 2.0;
            try {
                $this->SetFont('Arial', 'B', 8);
                $this->SetTextColor(6, 173, 191);
                $this->Text($qrX, $qrY - 2, rx_pdf_to_latin('QR Estudio'));
                $this->Image($this->qrImagePath, $qrX, $qrY + 2, $qrSize, $qrSize, $this->qrImageType);
                $this->SetTextColor(0, 0, 0);
            } catch (Throwable $e) {
                error_log('RxInformePDF QR: ' . $e->getMessage());
            }
        }

        $this->SetY($blockTop + self::CLOSING_BLOCK_H);
    }

    public function Footer(): void
    {
        $this->SetY(-self::FOOTER_LINE_H);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(130, 130, 130);
        $this->SetX(self::M_LEFT);
        $this->Cell(0, 5, rx_pdf_to_latin('Este informe es confidencial y solo para uso médico.'), 0, 0, 'L');
    }
}

function rx_pdf_cleanup_paths(array $paths): void
{
    foreach ($paths as $path) {
        if (is_string($path) && is_file($path)) {
            @unlink($path);
        }
    }
}

/**
 * Sincroniza radiologist_id en informes antiguos.
 */
function rx_pdf_backfill_radiologist_ids(PDO $connect): void
{
    $connect->exec("
        UPDATE radiology_reports
        SET radiologist_id = user_id
        WHERE user_id IS NOT NULL AND user_id > 0
          AND (radiologist_id IS NULL OR radiologist_id = 0)
    ");

    $sql = "
        UPDATE radiology_reports rr
        INNER JOIN worklist w ON w.study_id = rr.study_id
        INNER JOIN users u ON TRIM(LOWER(u.name)) = TRIM(LOWER(w.radiologist_name))
        SET rr.radiologist_id = u.id,
            rr.user_id = COALESCE(NULLIF(rr.user_id, 0), u.id)
        WHERE w.radiologist_name IS NOT NULL
          AND TRIM(w.radiologist_name) <> ''
          AND (rr.radiologist_id IS NULL OR rr.radiologist_id = 0 OR rr.user_id IS NULL OR rr.user_id = 0)
    ";
    try {
        $connect->exec($sql);
    } catch (Throwable $e) {
        error_log('rx_pdf_backfill_radiologist_ids: ' . $e->getMessage());
    }
}
