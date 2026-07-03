<?php
require('../../backend/fpdf/fpdf.php');
require('../../backend/bd/Conexion.php');

// Clase extendida para agregar el Footer personalizado
class PDFWithFooter extends FPDF {
    function Footer() {
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }
}

// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Validar el ID del paciente
if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
    die('Error: ID del paciente no proporcionado.');
}

$idpa = intval($_GET['idpa']);

// Consultar información del paciente
$stmtPatient = $connect->prepare("
    SELECT 
        CONCAT(patients.nompa, ' ', patients.apepa) AS full_name,
        patients.numhs AS dni,
        patients.cump AS fecha_nacimiento,
        TIMESTAMPDIFF(YEAR, patients.cump, CURDATE()) AS edad,
        consult.servicio,
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

// Consultar registros de la "Hoja de Medicamentos"
$stmtData = $connect->prepare("
    SELECT medicamento_tratamiento, fecha_hora, procesado_por 
    FROM control_medicamentos 
    WHERE idpa = :idpa 
    ORDER BY created_at ASC
");
$stmtData->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtData->execute();
$data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
    die('Error: No se encontraron registros para la Hoja de Medicamentos.');
}

// Crear el PDF
$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// Logo e información básica
$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(130, 15);
$pdf->Cell(0, 5, mb_convert_encoding('HOSPITAL MEDICASA S. D R.L.', 'ISO-8859-1'), 0, 1, 'L');
$pdf->Ln(10);

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, -5, mb_convert_encoding(mb_strtoupper('DATOS PACIENTE', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Datos del paciente en formato homogéneo
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

// Configuración de diseño para 2 columnas con ajuste dinámico
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

// Iterar sobre los campos y dibujar en dos columnas con ajuste dinámico
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

// Título de la tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 15, mb_convert_encoding(mb_strtoupper('REGISTROS DE MEDICAMENTOS', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Encabezado de la tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$columns = ['MEDICAMENTO Y TRATAMIENTO', 'FECHA Y HORA', 'PROCESADO POR'];
$columnWidths = [100, 45, 50];
foreach ($columns as $i => $col) {
    $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding($col, 'ISO-8859-1'), 1, 0, 'C', true);
}
$pdf->Ln();

// Agregar registros a la tabla
$pdf->SetFont('Arial', '', 9);
foreach ($data as $row) {
    $pdf->Cell($columnWidths[0], 6, mb_convert_encoding($row['medicamento_tratamiento'], 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[1], 6, mb_convert_encoding($row['fecha_hora'], 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[2], 6, mb_convert_encoding($row['procesado_por'], 'ISO-8859-1'), 1);
    $pdf->Ln();
}

// Salida del PDF
$pdf->Output('D', 'Hoja Medicamentos.pdf');
?>
