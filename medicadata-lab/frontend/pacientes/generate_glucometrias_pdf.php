<?php
/* Llamar a la librería FPDF */
require('../../backend/fpdf/fpdf.php');
require('../../backend/bd/Conexion.php');

// Extender la clase FPDF para agregar el método Footer
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

// Consultar información del paciente incluyendo los nuevos campos
$stmtPatient = $connect->prepare("
    SELECT 
        CONCAT(patients.nompa, ' ', patients.apepa) AS full_name, -- Especificar tabla para evitar ambigüedad
        patients.numhs AS dni,
        patients.cump AS fecha_nacimiento,
        TIMESTAMPDIFF(YEAR, patients.cump, CURDATE()) AS edad,
        consult.servicio AS servicio,
        consult.habitacion_no, -- Especificar la tabla consult
        consult.fecha_hora_ingreso, -- Especificar la tabla consult
        consult.fecha_hora_egreso, -- Especificar la tabla consult
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

// Obtener datos de Glucometrías e Insulinas
$stmtData = $connect->prepare("SELECT fecha, turno, hora, glucometria, insulina_cristalina, nph, procesado_por, created_at FROM glucometrias WHERE idpa = :idpa ORDER BY created_at ASC");
$stmtData->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtData->execute();
$data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

if (empty($data)) {
    die('Error: No se encontraron registros para Glucometrías e Insulinas.');
}

// Crear el PDF
$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// Logo e información básica
$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50);

$pdf->Ln(10);

$pdf->Ln(15);
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
$pdf->Cell(0, 10, mb_convert_encoding(mb_strtoupper('REGISTRO DE GLUCOMETRÍAS E INSULINAS', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Encabezados de la tabla
$columns = ['FECHA', 'TURNO', 'HORA', 'GLUCOMETRÍA', 'INS. CRISTALINA', 'NPH', 'PROCESADO POR'];
$columnWidths = [25, 20, 20, 30, 30, 20, 50];

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(240, 240, 240);
foreach ($columns as $i => $col) {
    $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding(mb_strtoupper($col, 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();

// Agregar datos a la tabla
$pdf->SetFont('Arial', '', 8);
$rowCount = 0;
$rowsPerPage = 15; // Máximo de filas por página

foreach ($data as $row) {
    if ($rowCount == $rowsPerPage) {
        $pdf->AddPage();
        $pdf->Ln(10);
        // Reimprimir encabezados
        foreach ($columns as $i => $col) {
            $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding(mb_strtoupper($col, 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $rowCount = 0;
    }

    $pdf->Cell($columnWidths[0], 6, mb_convert_encoding($row['fecha'], 'UTF-8', 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[1], 6, mb_convert_encoding($row['turno'], 'UTF-8', 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[2], 6, mb_convert_encoding($row['hora'], 'UTF-8', 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[3], 6, mb_convert_encoding($row['glucometria'], 'UTF-8', 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[4], 6, mb_convert_encoding($row['insulina_cristalina'], 'UTF-8', 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[5], 6, mb_convert_encoding($row['nph'], 'UTF-8', 'ISO-8859-1'), 1);
    $pdf->Cell($columnWidths[6], 6, mb_convert_encoding($row['procesado_por'], 'ISO-8859-1', 'UTF-8'), 1);
    $pdf->Ln();
    $rowCount++;
}

// Salida del PDF
$pdf->Output('D', 'Glucometrias Insulinas.pdf');
?>
