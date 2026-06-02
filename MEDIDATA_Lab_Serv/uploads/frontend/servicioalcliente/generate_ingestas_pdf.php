<?php
require('../../backend/fpdf/fpdf.php');
require('../../backend/bd/Conexion.php');

// Clase personalizada para agregar el Footer
class PDFWithFooter extends FPDF
{
    function Footer()
    {
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

// Consultar datos del paciente
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
    LIMIT 1
");
$stmtPatient->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPatient->execute();
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Error: Paciente no encontrado.');
}

// Consultar datos de INGESTAS
$stmtIngestas = $connect->prepare("
    SELECT fecha, hora, via_oral_tipo, via_oral_cantidad, via_parenteral_tipo, via_parenteral_cantidad, procesado_por 
    FROM ingestas 
    WHERE idpa = :idpa
    ORDER BY created_at ASC
");
$stmtIngestas->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtIngestas->execute();
$data = $stmtIngestas->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
    die('Error: No se encontraron registros para INGESTAS.');
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
$colWidth = ($pageWidth - 15) / 2; // Dos columnas con márgenes laterales ajustados
$rowHeight = 7; // Altura de las filas ajustada
$offset = 2; // Ajuste adicional para correr hacia la derecha
$startX = ($pageWidth - (2 * $colWidth)) / 2 + $offset; // Centramos la tabla con un desplazamiento
$startY = $pdf->GetY() + 10; // Posición inicial debajo del contenido anterior
$labelWidthRatio = 0.40; // Proporción del ancho para las etiquetas
$valueWidthRatio = 1 - $labelWidthRatio; // Proporción del ancho para los valores
$col = 0; // Contador de columnas

$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 8);

// Iterar sobre los campos y dibujar en dos columnas con ajuste dinámico
foreach ($patientDetails as $label => $value) {
    // Posicionar columna
    $xPos = $startX + ($col * $colWidth);
    $pdf->SetXY($xPos, $startY);

    // Calcular anchos dinámicamente
    $labelWidth = $colWidth * $labelWidthRatio;
    $valueWidth = $colWidth * $valueWidthRatio;

    // Dibujar etiqueta
    $labelText = mb_convert_encoding(mb_strtoupper("$label:", 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($labelWidth, $rowHeight, $labelText, 0, 'L', true);

    // Dibujar valor
    $pdf->SetXY($xPos + $labelWidth, $startY);
    $valueText = mb_convert_encoding(mb_strtoupper($value, 'UTF-8'), 'ISO-8859-1', 'UTF-8');
    $pdf->MultiCell($valueWidth, $rowHeight, $valueText, 0, 'L', false);

    // Ajustar posición para siguiente celda
    $col++;
    if ($col == 2) { // Si ya se completaron dos columnas, pasar a la siguiente fila
        $startY += $rowHeight;
        $col = 0;
    }
}

$pdf->Ln(10);

// Título de la tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 15, mb_convert_encoding(mb_strtoupper('REGISTROS DE INGESTAS', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Encabezado de la tabla
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 240, 240);
$columns = ['FECHA', 'HORA', 'VIA O. (TIPO)', 'CANTIDAD', 'VIA P. (TIPO)', 'CANTIDAD', 'PROCESADO POR'];
$columnWidths = [20, 18, 30, 22, 30, 25, 50];

foreach ($columns as $i => $col) {
    $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding(mb_strtoupper($col, 'UTF-8'), 'ISO-8859-1'), 1, 0, 'C', true);
}
$pdf->Ln();

// Datos de la tabla
$pdf->SetFont('Arial', '', 9);
foreach ($data as $row) {
    $pdf->Cell($columnWidths[0], 6, mb_convert_encoding($row['fecha'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($columnWidths[1], 6, mb_convert_encoding($row['hora'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($columnWidths[2], 6, mb_convert_encoding($row['via_oral_tipo'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($columnWidths[3], 6, mb_convert_encoding($row['via_oral_cantidad'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($columnWidths[4], 6, mb_convert_encoding($row['via_parenteral_tipo'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($columnWidths[5], 6, mb_convert_encoding($row['via_parenteral_cantidad'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Cell($columnWidths[6], 6, mb_convert_encoding($row['procesado_por'] ?? '', 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Ln();
}

// Salida del PDF
$pdf->Output('D', 'Hoja Ingestas.pdf');
?>
