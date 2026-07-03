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

/**
 * Obtiene user_id para cargar firma en user_signatures si el INSERT antiguo dejó *_user_id en NULL pero sí existe el nombre en users.name.
 *
 * @return int 0 si no se encuentra
 */
function sv_sv_resolve_user_id(PDO $connect, ?int $storedUid, ?string $nombreVisible): int
{
    $u = (int) $storedUid;
    if ($u > 0) {
        return $u;
    }
    $n = trim((string) $nombreVisible);
    if ($n === '' || $n === '—' || $n === '-') {
        return 0;
    }
    $stmt = $connect->prepare('SELECT id FROM users WHERE TRIM(name) = ? LIMIT 1');
    $stmt->execute([$n]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : 0;
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

if (!isset($_GET['idpa']) || trim((string) $_GET['idpa']) === '') {
    die('Error: ID del paciente no proporcionado.');
}

$idpa = (int) $_GET['idpa'];
if ($idpa < 1) {
    die('Error: ID del paciente no válido.');
}

$esAmbulatorio = isset($_GET['tipo'])
    && strtolower(trim((string) $_GET['tipo'])) === 'ambulatorio';

if ($esAmbulatorio) {
    $stmtPatient = $connect->prepare('
        SELECT
            nompa,
            apepa,
            numhs AS dni,
            cump AS fecha_nacimiento
        FROM patients_ambulatorios
        WHERE id = :id LIMIT 1
    ');
    $stmtPatient->execute([':id' => $idpa]);
    $rowAmb = $stmtPatient->fetch(PDO::FETCH_ASSOC);
    if (!$rowAmb) {
        die('Error: Paciente ambulatorio no encontrado.');
    }
    $nombre = trim(trim((string) ($rowAmb['nompa'] ?? '')) . ' ' . trim((string) ($rowAmb['apepa'] ?? '')));
    if ($nombre === '') {
        $nombre = 'No registrado';
    }
    $edadAnosStr = '';
    $fnRaw = isset($rowAmb['fecha_nacimiento']) ? (string) $rowAmb['fecha_nacimiento'] : '';
    if ($fnRaw !== '' && preg_match('/^\d{4}-\d{2}-\d{2}/', $fnRaw)) {
        $stAge = $connect->prepare('SELECT TIMESTAMPDIFF(YEAR, :cump, CURDATE())');
        $stAge->execute([':cump' => substr($fnRaw, 0, 10)]);
        $edanos = $stAge->fetchColumn();
        $edadAnosStr = ($edanos !== false && $edanos !== null) ? (string) $edanos : '';
    }
    $patient = [
        'full_name' => $nombre,
        'dni' => $rowAmb['dni'] ?? 'No registrado',
        'fecha_nacimiento' => $fnRaw !== '' ? substr($fnRaw, 0, 10) : 'No registrado',
        'edad' => $edadAnosStr !== '' ? $edadAnosStr : '-',
        'servicio' => 'Pre-clínica / Consulta externa',
        'habitacion_no' => 'N/A',
        'medico_tratante' => 'No registrado',
        'especialidad' => 'No registrado',
        'fecha_hora_ingreso' => 'No registrado',
        'fecha_hora_egreso' => 'No registrado',
    ];
} else {
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
}

$signoId = isset($_GET['signo_id']) ? (int) $_GET['signo_id'] : 0;

if ($esAmbulatorio) {
    if ($signoId > 0) {
        $stSig = $connect->prepare(
            'SELECT * FROM signos_vitales_outpatients WHERE id = ? AND id_outpatient = ? LIMIT 1'
        );
        $stSig->execute([$signoId, $idpa]);
        $solo = $stSig->fetch(PDO::FETCH_ASSOC);
        if (!$solo) {
            die('Error: El registro de signos vitales no existe o no pertenece a este paciente ambulatorio.');
        }
        $data = [$solo];
    } else {
        $stmt = $connect->prepare(
            'SELECT * FROM signos_vitales_outpatients WHERE id_outpatient = :id ORDER BY created_at DESC'
        );
        $stmt->execute([':id' => $idpa]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} else {
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
    /** Alto bloque firma (etiqueta + imagen/línea + nombre) para salto de página */
    $estimatedBlockMm = 34;
    
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

    $nombreReal = trim((string) ($row['processed_by'] ?? ''));
    $nombreRevRaw = trim((string) ($row['reviews_by'] ?? ''));
    $hayRevision = ($nombreRevRaw !== '' && $nombreRevRaw !== '-');

    $uidP = sv_sv_resolve_user_id(
        $connect,
        isset($row['processed_by_user_id']) ? (int) $row['processed_by_user_id'] : 0,
        $nombreReal
    );
    $blobP = $uidP > 0 ? sv_sv_signature_blob($connect, $uidP) : null;
    $tmpP = $blobP ? sv_sv_sig_temp_png($blobP) : null;
    if ($tmpP) {
        $tmpP = sv_sv_maybe_transparent_png($tmpP);
    }

    $uidRstored = isset($row['reviewed_by_user_id']) ? (int) $row['reviewed_by_user_id'] : 0;
    $nombreRev = $hayRevision ? (($nombreRevRaw !== '' && $nombreRevRaw !== '-') ? $nombreRevRaw : '—') : '—';

    $uidR = ($hayRevision && $uidRstored > 0)
        ? $uidRstored
        : (($hayRevision) ? sv_sv_resolve_user_id($connect, 0, $nombreRevRaw) : 0);

    $blobR = ($uidR > 0 && $hayRevision) ? sv_sv_signature_blob($connect, $uidR) : null;
    $tmpR = $blobR ? sv_sv_sig_temp_png($blobR) : null;
    if ($tmpR) {
        $tmpR = sv_sv_maybe_transparent_png($tmpR);
    }

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
    /**
     * Orden institucional (por columna, de arriba hacia abajo):
     * 1) dibujo de firma PNG (o cuadro de aviso),
     * 2) texto REALIZADO POR / REVISADO POR,
     * 3) nombre de la persona.
     */
    $captionH = 5;
    $nameH = 4.8;
    $yImgBase = $yBlk + 0.3;
    $yCaption = $yImgBase + $imgH + 0.6;
    $yNameRow = $yCaption + $captionH + 0.5;

    $xImgL = $xL + (($wHalf - $imgW) / 2);
    $xImgR = $xR + (($wHalf - $imgW) / 2);

    /* --- Paso 1: zonas de firma (izquierda y derecha) --- */
    foreach ([[$tmpP, $xImgL, $yImgBase], [$tmpR, $xImgR, $yImgBase]] as $idx => $trip) {
        [$tmpSig, $xImg, $yI] = $trip;
        if ($idx === 1 && !$hayRevision) {
            /** Columna revisión sin revisar: mismo hueco pero mensaje corto */
            $pdf->SetDrawColor(200, 202, 208);
            $pdf->Rect($xImg, $yI, $imgW, $imgH);
            $pdf->SetFont('Arial', 'I', 7);
            $pdf->SetTextColor(120, 120, 118);
            $pdf->SetXY($xImg, $yI + ($imgH / 2) - 2.5);
            $pdf->Cell($imgW, 4, sv_sv_enc('Sin revisión'), 0, 0, 'C');
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetTextColor(0, 0, 0);
            continue;
        }
        if ($tmpSig && is_string($tmpSig)) {
            try {
                $pdf->Image($tmpSig, $xImg, $yI, $imgW, $imgH);
            } catch (\Throwable $e) {
                $tmpSig = null;
            }
        }
        if (!$tmpSig) {
            $pdf->SetDrawColor(190, 192, 198);
            $pdf->Rect($xImg, $yI, $imgW, $imgH);
            $pdf->SetFont('Arial', 'I', 6.8);
            $pdf->SetTextColor(115, 115, 113);
            $pdf->SetXY($xImg, $yI + ($imgH / 2) - 2);
            $msg = (($idx === 0 ? $uidP : $uidR) > 0)
                ? 'Firma PNG no válida'
                : 'Sin firma en perfil MEDIDATA';
            $pdf->MultiCell($imgW, 3, sv_sv_enc($msg), 0, 'C', false);
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetTextColor(0, 0, 0);
        }
    }

    /* --- Paso 2: títulos de rol --- */
    $pdf->SetXY($xL, $yCaption);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell($wHalf, $captionH, sv_sv_enc('REALIZADO POR'), 0, 0, 'C');
    $pdf->SetXY($xR, $yCaption);
    $pdf->Cell($wHalf, $captionH, sv_sv_enc('REVISADO POR'), 0, 0, 'C');

    /* --- Paso 3: nombres --- */
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
