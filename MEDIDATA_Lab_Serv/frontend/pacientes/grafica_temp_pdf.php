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

// Obtener datos de la gráfica de temperatura
$stmtGraph = $connect->prepare("
    SELECT temps, glucap_temp, turno, procesado_por, spo_2, frecuenciac, fresp_temp, tensiona, DATE_FORMAT(created_at, '%d-%m-%Y %H:%i:%s') as created_at
    FROM grafica_temperatura
    WHERE idpa = :idpa
    ORDER BY created_at ASC
");

$stmtGraph->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtGraph->execute();
$graphData = $stmtGraph->fetchAll(PDO::FETCH_ASSOC);

if (empty($graphData)) {
    die('Error: No se encontraron registros para la gráfica de temperatura.');
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

// Dibujar la gráfica de temperaturas
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 5, mb_convert_encoding(mb_strtoupper('GRÁFICA DE TEMPERATURA', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Configuración de la gráfica
$graphWidth = 185;
$graphHeight = 60;
$graphX = 20;
$graphY = $pdf->GetY() + 5;

$temps = array_map('floatval', array_column($graphData, 'temps'));
$numPoints = count($temps);

$maxTemp = max($temps);
$minTemp = min($temps);
// Evitar división por cero cuando hay un solo punto o todos iguales
$tempRange = ($maxTemp - $minTemp) > 0 ? ($maxTemp - $minTemp) : 1;

$pdf->SetDrawColor(0, 0, 0);
$pdf->Rect($graphX, $graphY, $graphWidth, $graphHeight);

for ($i = 0; $i <= 10; $i++) {
    $lineY = $graphY + ($graphHeight / 10) * $i;
    $pdf->Line($graphX, $lineY, $graphX + $graphWidth, $lineY);
    $labelTemp = $maxTemp - ($tempRange / 10) * $i;
    $pdf->Text($graphX - 10, $lineY + 2, round($labelTemp, 1));
}

$pointX = $graphX;
$pointY = $graphY + $graphHeight;

// Dibujar la línea entre puntos (solo si hay 2 o más)
$pdf->SetDrawColor(0, 0, 0);
$divisor = ($numPoints > 1) ? ($numPoints - 1) : 1;
for ($index = 0; $index < $numPoints - 1; $index++) {
    $startPointX = $graphX + (($graphWidth - 5) / $divisor) * $index;
    $startPointY = $graphY + $graphHeight - (($temps[$index] - $minTemp) / $tempRange) * $graphHeight;

    $endPointX = $graphX + (($graphWidth - 5) / $divisor) * ($index + 1);
    $endPointY = $graphY + $graphHeight - (($temps[$index + 1] - $minTemp) / $tempRange) * $graphHeight;

    $pdf->Line($startPointX, $startPointY, $endPointX, $endPointY);
}

// Dibujar los cuadros y las letras encima de la línea
foreach ($temps as $index => $temp) {
    $newPointX = $graphX + (($graphWidth - 5) / $divisor) * $index;
    $newPointY = $graphY + $graphHeight - (($temp - $minTemp) / $tempRange) * $graphHeight;

    // Cambiar color de relleno según el turno
    $turnoLabel = $graphData[$index]['turno'];
    if ($turnoLabel === 'A') {
        $pdf->SetFillColor(0, 0, 0); // Negro
    } elseif ($turnoLabel === 'B') {
        $pdf->SetFillColor(0, 255, 0); // Verde
    } elseif ($turnoLabel === 'C') {
        $pdf->SetFillColor(255, 0, 0); // Rojo
    } else {
        $pdf->SetFillColor(200, 200, 200); // Gris por defecto para casos no definidos
    }

    // Rellenar el cuadro
    $pdf->Rect($newPointX - 2.5, $newPointY - 2.5, 7, 7, 'F'); // Cuadro relleno

    // Etiqueta del turno (letra) encima del cuadro
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetTextColor(255, 255, 255); // Blanco para texto
    $pdf->Text($newPointX - 1.5, $newPointY + 2, mb_convert_encoding($turnoLabel, 'ISO-8859-1', 'UTF-8'));
}

// Restaurar colores predeterminados
$pdf->SetTextColor(0, 0, 0);
$pdf->SetDrawColor(0, 0, 0);


$pdf->Ln(70); // Espacio debajo de la gráfica
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 12, mb_convert_encoding(mb_strtoupper('DETALLES DE TEMPERATURA', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Encabezados actualizados
$columns = ['TEMPERATURA (°C)', 'TURNO', 'FECHA Y HORA', 'PROCESADO POR', 'GLUCOMETRIA', 'SP02', 'FC', 'FR', 'P/A'];
$columnWidths = [30, 15, 30, 30, 25, 15, 15, 15, 20]; // Anchos ajustados para mantener el margen

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(240, 240, 240);

// Dibujar los encabezados
foreach ($columns as $i => $col) {
    $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding(mb_strtoupper($col, 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();

// Configuración de la tabla de datos adicionales
$rowsPerPage = 10; // Máximo de registros por página
$rowCount = 0;

$pdf->SetFont('Arial', '', 8);
foreach ($graphData as $row) {
    if ($rowCount == $rowsPerPage) {
        $pdf->AddPage();
        $pdf->Ln(10); // Agregar espacio en la nueva página
        // Reimprimir encabezados de la tabla
        foreach ($columns as $i => $col) {
            $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding(mb_strtoupper($col, 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $rowCount = 0; // Reiniciar contador de filas
    }

    // Dibujar celdas de la fila
    $pdf->Cell($columnWidths[0], 6, mb_convert_encoding($row['temps'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[1], 6, mb_convert_encoding($row['turno'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[2], 6, mb_convert_encoding($row['created_at'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[3], 6, mb_convert_encoding($row['procesado_por'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[4], 6, mb_convert_encoding($row['glucap_temp'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    // Columnas adicionales (SP02, FC, FR, P/A)
    $pdf->Cell($columnWidths[5], 6, mb_convert_encoding($row['spo_2'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[6], 6, mb_convert_encoding($row['frecuenciac'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[7], 6, mb_convert_encoding($row['fresp_temp'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[8], 6, mb_convert_encoding($row['tensiona'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Ln();
    $rowCount++;
}


// Salida del PDF
$pdf->Output('D', 'Grafica Temperatura.pdf');
