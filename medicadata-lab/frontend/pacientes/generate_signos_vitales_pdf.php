<?php
require('../../backend/fpdf/fpdf.php');
require('../../backend/bd/Conexion.php');

class PDFWithFooter extends FPDF
{
    function Footer()
    {
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }
}

date_default_timezone_set('America/Tegucigalpa');

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

$stmt = $connect->prepare("SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC");
$stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdf = new PDFWithFooter('L', 'mm', 'Letter');
$pdf->AddPage();

$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50);

$pdf->Ln(10);
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, -5, mb_convert_encoding(mb_strtoupper('DATOS PACIENTE', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

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
$colWidth = ($pageWidth - 15) / 2;
$rowHeight = 7;
$offset = 2;
$startX = ($pageWidth - (2 * $colWidth)) / 2 + $offset;
$startY = $pdf->GetY() + 10;
$labelWidthRatio = 0.40;
$valueWidthRatio = 1 - $labelWidthRatio;
$col = 0;

$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 8);

foreach ($patientDetails as $label => $value) {
    $xPos = $startX + ($col * $colWidth);
    $pdf->SetXY($xPos, $startY);

    $labelWidth = $colWidth * $labelWidthRatio;
    $valueWidth = $colWidth * $valueWidthRatio;

    $labelText = mb_convert_encoding(mb_strtoupper("$label:", 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($labelWidth, $rowHeight, $labelText, 0, 'L', true);

    $pdf->SetXY($xPos + $labelWidth, $startY);
    $valueText = mb_convert_encoding(mb_strtoupper($value, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($valueWidth, $rowHeight, $valueText, 0, 'L', false);

    $col++;
    if ($col == 2) {
        $startY += $rowHeight;
        $col = 0;
    }
}

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, mb_convert_encoding('SIGNOS VITALES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

$headers = ['Fecha', 'Hora', 'Realizado Por', 'Revisado Por', 'Peso', 'Estatura', 'PA', 'PAM', 'FC', 'FR', 'SAT', 'Temp', 'Glucosa'];

$colWidths = [20, 18, 35, 35, 14, 14, 22, 14, 13, 13, 13, 15, 15];

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(200, 200, 200);

foreach ($headers as $i => $header) {
    $pdf->Cell($colWidths[$i], 7, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Arial', '', 8);

foreach ($data as $row) {
    $pdf->Cell($colWidths[0], 6, mb_convert_encoding($row['fecha'], 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($colWidths[1], 6, mb_convert_encoding($row['hora'], 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($colWidths[2], 6, mb_convert_encoding($row['processed_by'], 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($colWidths[3], 6, mb_convert_encoding($row['reviews_by'] ?? '-', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($colWidths[4], 6, $row['weight'], 1);
    $pdf->Cell($colWidths[5], 6, $row['stature'], 1);
    $pdf->Cell($colWidths[6], 6, $row['blood_pressure'], 1);
    $pdf->Cell($colWidths[7], 6, $row['map_pressure'], 1);
    $pdf->Cell($colWidths[8], 6, $row['heart_rate'], 1);
    $pdf->Cell($colWidths[9], 6, $row['respiratory_rate'], 1);
    $pdf->Cell($colWidths[10], 6, $row['oxygen_saturation'], 1);
    $pdf->Cell($colWidths[11], 6, $row['temperature'], 1);
    $pdf->Cell($colWidths[12], 6, $row['glucose'], 1);
    $pdf->Ln();
}

$pdf->Output('D', 'Signos_Vitales.pdf');
