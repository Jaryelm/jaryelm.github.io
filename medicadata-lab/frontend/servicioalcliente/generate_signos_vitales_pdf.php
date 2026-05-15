<?php
/* Llamar a la librería FPDF */
require('../../backend/fpdf/fpdf.php');
require('../../backend/bd/Conexion.php');

// Extender la clase FPDF para agregar el método Footer
class PDFWithFooter extends FPDF
{
    function Footer()
    {
        // Posicionar el pie de página a 30 mm del final
        $this->SetY(-30);
        // Agregar la imagen del footer
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }
}

// Configurar la zona horaria
date_default_timezone_set('America/Tegucigalpa');

// Verificar si 'idpa' está definido
if (!isset($_GET['idpa']) || empty($_GET['idpa'])) {
    die('Error: ID del paciente no proporcionado.');
}

// Obtener el ID del paciente
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

// Consultar los datos adicionales desde la tabla consult
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

// Manejar valores nulos en caso de que no haya registros en la tabla consult
$patient['medico_tratante'] = $consultData['medico_tratante'] ?? 'No registrado';
$patient['especialidad'] = $consultData['especialidad'] ?? 'No registrado';
$patient['servicio'] = $consultData['servicio'] ?? 'No registrado';

// Consultar los signos vitales
$stmt = $connect->prepare("SELECT * FROM signos_vitales WHERE idpa = :idpa ORDER BY created_at DESC");
$stmt->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear un nuevo PDF con tamaño Carta en orientación Horizontal (Landscape)
$pdf = new PDFWithFooter('L', 'mm', 'Letter');
$pdf->AddPage();

// Agregar el logo
$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50);

// Información del encabezado
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(130, 15);
$pdf->Cell(0, 5, mb_convert_encoding('HOSPITAL MEDICASA S. D R.L.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(10); // Espacio después del título

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

// Agregar título "Reporte de Signos Vitales" en la tabla de signos vitales
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, mb_convert_encoding('SIGNOS VITALES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(5); // Espacio entre título y tabla

// Tabla de signos vitales
$headers = ['Fecha', 'Hora', 'Realizado Por', 'Revisado Por', 'Peso', 'Estatura', 'PA', 'PAM', 'FC', 'FR', 'SAT', 'Temp', 'Glucosa'];

// Calcular ancho de las columnas (Total aprox 259mm para Letter Landscape con márgenes)
$colWidths = [
    20, // Fecha
    18, // Hora
    35, // Realizado Por
    35, // Revisado Por
    14, // Peso
    14, // Estatura
    22, // PA (blood_pressure)
    14, // PAM (map_pressure)
    13, // FC (heart_rate)
    13, // FR (respiratory_rate)
    13, // SAT (oxygen_saturation)
    15, // Temp (temperature)
    15  // Glucosa (glucose)
];

// Encabezados de la tabla
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(200, 200, 200);

foreach ($headers as $i => $header) {
    $pdf->Cell($colWidths[$i], 7, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();

// Datos de la tabla
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

// Salida del archivo para descargar
$pdf->Output('D', 'Signos_Vitales.pdf');
