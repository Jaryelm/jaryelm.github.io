<?php
require('../../backend/fpdf/fpdf.php');
require('../../backend/bd/Conexion.php');

date_default_timezone_set('America/Tegucigalpa');

function sv_sv_enc(?string $t): string
{
    return mb_convert_encoding((string) $t, 'ISO-8859-1', 'UTF-8');
}

/** @return ?string Firma desde user_signatures (BLOB PNG). */
function sv_sv_signature_blob(PDO $connect, ?int $userId): ?string
{
    $uid = (int) $userId;
    if ($uid < 1) {
        return null;
    }
    $stmt = $connect->prepare('SELECT signature FROM user_signatures WHERE user_id = ? LIMIT 1');
    $stmt->execute([$uid]);
    $blob = $stmt->fetchColumn();
    if (!$blob || !is_string($blob) || strlen($blob) < 8) {
        return null;
    }
    return $blob;
}

/** Escribe PNG temporal o null si inválido (extensión .png exigida por FPDF para detectar el tipo). */
function sv_sv_sig_temp_png(string $blob): ?string
{
    $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'svsig_' . uniqid('', true) . '.png';
    file_put_contents($path, $blob);
    if (@getimagesize($path) === false) {
        @unlink($path);
        return null;
    }
    return $path;
}

/** Atenúa fondo blanco (mismo criterio que documento.php). Devuelve ruta válida PNG. */
function sv_sv_maybe_transparent_png(string $path): string
{
    if (!extension_loaded('gd') || !function_exists('imagecreatefrompng')) {
        return $path;
    }
    $image = @imagecreatefrompng($path);
    if (!$image) {
        return $path;
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $out = imagecreatetruecolor($width, $height);
    imagesavealpha($out, true);
    $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefill($out, 0, 0, $transparent);
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            $color = imagecolorat($image, $x, $y);
            $r = ($color >> 16) & 0xFF;
            $g = ($color >> 8) & 0xFF;
            $b = $color & 0xFF;
            if ($r > 200 && $g > 200 && $b > 200) {
                imagesetpixel($out, $x, $y, $transparent);
            } else {
                imagesetpixel($out, $x, $y, $color);
            }
        }
    }
    imagepng($out, $path);
    unset($image, $out);
    return $path;
}

class PDFWithFooter extends FPDF
{
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'Letter')
    {
        parent::__construct($orientation, $unit, $size);
        /* Espacio antes del footer (imagen ~30 mm + margen seguro para firmas) */
        $this->SetAutoPageBreak(true, 38);
    }

    public function Footer()
    {
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }

    /** Ancho útil entre márgenes (FPDF marca lMargin/rMargin como protected). */
    public function SvUsableWidth(): float
    {
        return $this->GetPageWidth() - $this->lMargin - $this->rMargin;
    }

    public function SvLeftMargin(): float
    {
        return $this->lMargin;
    }
}

if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
    die('Error: ID del paciente no proporcionado.');
}

$idpa = intval($_GET['idpa']);

$stmtPatient = $connect->prepare("
    SELECT 
        CONCAT(patients.nompa, ' ', patients.apepa) AS full_name,
        patients.numhs AS dni,
        patients.cump AS fecha_nacimiento,
        TIMESTAMPDIFF(YEAR, patients.cump, CURDATE()) AS edad,
        consult.servicio AS servicio,
        consult.habitacion_no,
        consult.fecha_hora_ingreso,
        consult.fecha_hora_egreso,
        consult.medico_tratante,
        consult.especialidad
    FROM 
        patients
    LEFT JOIN 
        consult ON patients.idpa = consult.idpa
    WHERE 
        patients.idpa = :idpa
    ORDER BY 
        consult.fere DESC 
    LIMIT 1
");
$stmtPatient->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPatient->execute();
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Error: Paciente no encontrado.');
}

$stmtConsult = $connect->prepare("
    SELECT 
        medico_tratante,
        especialidad,
        servicio
    FROM consult
    WHERE idpa = :idpa
    ORDER BY fere DESC
    LIMIT 1
");
$stmtConsult->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtConsult->execute();
$consultData = $stmtConsult->fetch(PDO::FETCH_ASSOC);

$patient['medico_tratante'] = $consultData['medico_tratante'] ?? 'No registrado';
$patient['especialidad'] = $consultData['especialidad'] ?? 'No registrado';
$patient['servicio'] = $consultData['servicio'] ?? 'No registrado';

$signoId = isset($_GET['signo_id']) ? (int) $_GET['signo_id'] : 0;

if ($signoId > 0) {
    $stSig = $connect->prepare('SELECT * FROM signos_vitales WHERE id = ? AND idpa = ? LIMIT 1');
    $stSig->execute([$signoId, $idpa]);
    $solo = $stSig->fetch(PDO::FETCH_ASSOC);
    if (!$solo) {
        die('Error: El registro de signos vitales no existe o no pertenece a este paciente.');
    }
    $data = [$solo];
} else {
    $stmt = $connect->prepare("SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC");
    $stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

$pdf->Image('../../backend/img/factura_logo.png', 10, 8, 45);

$pdf->Ln(22);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 8, sv_sv_enc(mb_strtoupper('DATOS PACIENTE', 'UTF-8')), 0, 1, 'C');

$patientDetails = [
    'NOMBRE COMPLETO' => $patient['full_name'] ?? 'No registrado',
    'DNI' => $patient['dni'] ?? 'No registrado',
    'FECHA DE NACIMIENTO' => $patient['fecha_nacimiento'] ?? 'No registrado',
    'EDAD' => ($patient['edad'] ?? '-') . ' años',
    'SERVICIO' => $patient['servicio'] ?? 'No registrado',
    'HABITACIÓN NO' => $patient['habitacion_no'] ?? 'No registrado',
    'MÉDICO TRATANTE' => $patient['medico_tratante'] ?? 'No registrado',
    'ESPECIALIDAD' => $patient['especialidad'] ?? 'No registrado',
    'FECHA / HORA DE INGRESO' => $patient['fecha_hora_ingreso'] ?? 'No registrado',
    'FECHA / HORA DE EGRESO' => $patient['fecha_hora_egreso'] ?? 'No registrado',
];

$pageWidth = $pdf->GetPageWidth();
$colWidth = ($pageWidth - 20) / 2;
$rowHeight = 7;
$offset = 2;
$startX = ($pageWidth - (2 * $colWidth)) / 2 + $offset;
$startY = $pdf->GetY() + 8;
$labelWidthRatio = 0.42;
$valueWidthRatio = 1 - $labelWidthRatio;
$col = 0;

$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 7);

foreach ($patientDetails as $label => $value) {
    $xPos = $startX + ($col * $colWidth);
    $pdf->SetXY($xPos, $startY);

    $labelWidth = $colWidth * $labelWidthRatio;
    $valueWidth = $colWidth * $valueWidthRatio;

    $labelText = mb_convert_encoding(mb_strtoupper("$label:", 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($labelWidth, $rowHeight, $labelText, 0, 'L', true);

    $pdf->SetXY($xPos + $labelWidth, $startY);
    $valueText = mb_convert_encoding(mb_strtoupper((string) $value, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($valueWidth, $rowHeight, $valueText, 0, 'L', false);

    $col++;
    if ($col === 2) {
        $startY += $rowHeight;
        $col = 0;
    }
}

$pdf->SetY(max($pdf->GetY(), $startY + 8));
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 10, sv_sv_enc('SIGNOS VITALES'), 0, 1, 'C');

/** Tabla de datos: texto en «Realiz.» / «Revis.»; firmas sólo abajo antes del footer. */
$usableW = $pdf->SvUsableWidth();
$pdf->SetX($pdf->SvLeftMargin());

$baseWidths = [14, 12, 26, 26, 11, 11, 12, 11, 10, 10, 11, 12, 13];
$baseSum = array_sum($baseWidths);
$colWidths = [];
$cwAcc = 0.0;
for ($ci = 0; $ci < 13; $ci++) {
    if ($ci < 12) {
        $colWidths[$ci] = round($usableW * $baseWidths[$ci] / $baseSum, 3);
        $cwAcc += $colWidths[$ci];
    } else {
        $colWidths[$ci] = round($usableW - $cwAcc, 3);
    }
}

$hHdr = 7.0;
$hRow = 6.0;

$headers = ['Fecha', 'Hora', 'Realiz.', 'Revis.', 'Peso', 'Talla', 'PA', 'PAM', 'FC', 'FR', 'SAT', 'Temp', 'Glu'];

$pdf->SetFont('Arial', 'B', 7);
$pdf->SetFillColor(200, 200, 200);
for ($hi = 0; $hi < 13; $hi++) {
    $pdf->Cell($colWidths[$hi], $hHdr, sv_sv_enc($headers[$hi]), 1, 0, 'C', true);
}
$pdf->Ln($hHdr);

$pdf->SetFont('Arial', '', 7);
foreach ($data as $row) {
    if ($pdf->GetY() + $hRow > $pdf->GetPageHeight() - 38) {
        $pdf->AddPage();
    }

    $pdf->SetX($pdf->SvLeftMargin());
    $pdf->Cell($colWidths[0], $hRow, sv_sv_enc($row['fecha'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[1], $hRow, sv_sv_enc($row['hora'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[2], $hRow, sv_sv_enc(mb_substr((string) ($row['processed_by'] ?? ''), 0, 42)), 1, 0, 'L');
    $reviewsTxt = (($row['reviews_by'] ?? '') !== '' && ($row['reviews_by'] ?? '') !== '-') ? (string) $row['reviews_by'] : '-';
    $pdf->Cell($colWidths[3], $hRow, sv_sv_enc(mb_substr($reviewsTxt, 0, 42)), 1, 0, 'L');
    $pdf->Cell($colWidths[4], $hRow, sv_sv_enc($row['weight'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[5], $hRow, sv_sv_enc($row['stature'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[6], $hRow, sv_sv_enc($row['blood_pressure'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[7], $hRow, sv_sv_enc($row['map_pressure'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[8], $hRow, sv_sv_enc($row['heart_rate'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[9], $hRow, sv_sv_enc($row['respiratory_rate'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[10], $hRow, sv_sv_enc($row['oxygen_saturation'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[11], $hRow, sv_sv_enc($row['temperature'] ?? ''), 1, 0, 'C');
    $pdf->Cell($colWidths[12], $hRow, sv_sv_enc($row['glucose'] ?? ''), 1, 0, 'C');
    $pdf->Ln();
}

/* Firmas digitales: ubicadas junto al pie (encima del área segura del footer). 
   Solo se muestran si es un reporte INDIVIDUAL (count($data) === 1) para evitar que el reporte general sea demasiado extenso. */
if (count($data) === 1) {
    $footerBandMm = 38;
    $padAboveFooterMm = 4;
    $ySafeBottom = $pdf->GetPageHeight() - $footerBandMm - $padAboveFooterMm;

    $row = $data[0];
    $estimatedBlockMm = 32;
    
    // Si no cabe en la página actual, agregar una nueva
    if ($pdf->GetY() + $estimatedBlockMm > $ySafeBottom) {
        $pdf->AddPage();
        $ySafeBottom = $pdf->GetPageHeight() - $footerBandMm - $padAboveFooterMm;
    }

    // Intentar anclar al fondo de la página
    $idealY = $ySafeBottom - $estimatedBlockMm;
    if ($idealY > $pdf->GetY()) {
        $pdf->SetY($idealY);
    } else {
        $pdf->Ln(5);
    }

    $uidP = isset($row['processed_by_user_id']) ? (int) $row['processed_by_user_id'] : 0;
    $blobP = $uidP > 0 ? sv_sv_signature_blob($connect, $uidP) : null;
    $tmpP = $blobP ? sv_sv_sig_temp_png($blobP) : null;
    if ($tmpP) {
        $tmpP = sv_sv_maybe_transparent_png($tmpP);
    }

    $uidR = isset($row['reviewed_by_user_id']) ? (int) $row['reviewed_by_user_id'] : 0;
    $nombreRevRaw = trim((string) ($row['reviews_by'] ?? ''));
    $hayRevision = ($uidR > 0 || ($nombreRevRaw !== '' && $nombreRevRaw !== '-'));

    $blobR = ($uidR > 0 && $hayRevision) ? sv_sv_signature_blob($connect, $uidR) : null;
    $tmpR = $blobR ? sv_sv_sig_temp_png($blobR) : null;
    if ($tmpR) {
        $tmpR = sv_sv_maybe_transparent_png($tmpR);
    }

    $nombreReal = trim((string) ($row['processed_by'] ?? ''));
    $nombreRev = $hayRevision
        ? (($nombreRevRaw !== '' && $nombreRevRaw !== '-') ? $nombreRevRaw : '—')
        : '—';

    $lm = $pdf->SvLeftMargin();
    $uw = $pdf->SvUsableWidth();
    $gutter = 3;
    $wHalf = ($uw - $gutter) / 2;
    $xL = $lm;
    $xR = $lm + $wHalf + $gutter;

    $pdf->Ln(1);
    $yBlk = $pdf->GetY();
    $imgH = 14;
    $imgW = max(12, min(72, $wHalf - 2));

    /** Firma → leyenda del rol → nombre (todo centrado por columna). */
    $yImgBase = $yBlk + 0.35;
    $yCaption = $yImgBase + $imgH + 0.4;
    $captionH = 4.5;
    $nameH = 4.5;
    $yNameRow = $yCaption + $captionH + 0.25;

    /** Firma centrada dentro de cada columna (coord. X es borde izq. del PNG) */
    $xImgL = $xL + (($wHalf - $imgW) / 2);
    $xImgR = $xR + (($wHalf - $imgW) / 2);

    if ($tmpP) {
        $pdf->Image($tmpP, $xImgL, $yImgBase, $imgW, $imgH);
    }
    if ($tmpR) {
        $pdf->Image($tmpR, $xImgR, $yImgBase, $imgW, $imgH);
    }

    $pdf->SetXY($xL, $yCaption);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($wHalf, $captionH, sv_sv_enc('REALIZADO POR'), 0, 0, 'C');
    $pdf->SetXY($xR, $yCaption);
    $pdf->Cell($wHalf, $captionH, sv_sv_enc('REVISADO POR'), 0, 0, 'C');

    $nombreRealLbl = $nombreReal !== '' ? $nombreReal : '—';
    $pdf->SetXY($xL, $yNameRow);
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell($wHalf, $nameH, sv_sv_enc(mb_substr($nombreRealLbl, 0, 60)), 0, 0, 'C');
    $pdf->SetXY($xR, $yNameRow);
    $pdf->Cell($wHalf, $nameH, sv_sv_enc(mb_substr($nombreRev, 0, 60)), 0, 0, 'C');

    if ($tmpP) {
        @unlink($tmpP);
    }
    if ($tmpR) {
        @unlink($tmpR);
    }
}

$pdfFileOut = 'Signos_Vitales.pdf';
if (count($data) === 1 && isset($data[0]['id'])) {
    $fd = preg_replace('/[^0-9-]/', '', (string) ($data[0]['fecha'] ?? ''));
    $fid = (int) $data[0]['id'];
    $pdfFileOut = 'Signos_Vitales_' . ($fd !== '' ? $fd . '_' : '') . $fid . '.pdf';
}

$pdf->Output('D', $pdfFileOut);
