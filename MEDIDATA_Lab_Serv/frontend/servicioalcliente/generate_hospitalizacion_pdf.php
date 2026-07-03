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

// Verificar si 'idpa' está definido
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

// Consultar los datos de hospitalización
$stmtHospital = $connect->prepare("SELECT * FROM hospitalizacion WHERE idpa = :idpa ORDER BY fere DESC");
$stmtHospital->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtHospital->execute();
$data = $stmtHospital->fetchAll(PDO::FETCH_ASSOC);

// Crear un nuevo PDF con tamaño Carta
$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// Agregar logo e información del encabezado
$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(130, 15);
$pdf->Cell(0, 5, mb_convert_encoding('HOSPITAL MEDICASA S. D R.L.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'L');
$pdf->Ln(10);

$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, -5, mb_convert_encoding(mb_strtoupper('DATOS PACIENTE', 'UTF-8'), 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// Datos del paciente en formato homogéneo
$fields = [
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

$pdf->SetFillColor(240, 240, 240); // Fondo para etiquetas
$pdf->SetFont('Arial', 'B', 8); // Fuente para etiquetas

// Iterar sobre los campos y dibujar en dos columnas
foreach ($fields as $label => $value) {
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

// Ajustar posición Y para la siguiente sección
$pdf->Ln(10);

// Configuración de las secciones
$sections = [
    'CONTROL DE OXÍGENO' => ['oxigenoInicio', 'oxigenoHora', 'oxigenoFinaliza', 'oxigenoObservacion'],
    'CONTROL DE USO COLCHÓN AIRE' => ['colchonInicio', 'colchonHora', 'colchonFinaliza', 'colchonObservacion'],
    'CONTROL DE OXÍGENO CON RESERVORIO' => ['reservorioInicio', 'reservorioHora', 'reservorioFinaliza', 'reservorioObservacion'],
    'CONTROL DE MONITORIZACIÓN' => ['monitorInicio', 'monitorHora', 'monitorFinaliza', 'monitorObservacion'],
    'NEBULIZACIONES' => ['nebulizacionInicio', 'nebulizacionHora', 'nebulizacionFinaliza', 'nebulizacionObservacion'],
    'SUCCIÓN' => ['succionInicio', 'succionHora', 'succionFinaliza', 'succionObservacion']
];

// Configuración inicial
$currentY = 90; // Posición vertical inicial
$leftMargin = 10; // Margen izquierdo
$rightMargin = $pdf->GetPageWidth() - 10; // Margen derecho
$pageWidth = $rightMargin - $leftMargin; // Ancho usable
$columnWidth = ($pageWidth / 2) - 5; // Cada columna ocupa la mitad del espacio disponible
$sectionSpacing = 2; // Espaciado entre filas

// Dibujar las secciones
$sectionIndex = 0;
foreach ($sections as $title => $fields) {
    $xPosition = $leftMargin + ($sectionIndex % 2) * ($columnWidth + 10);
    $pdf->SetXY($xPosition, $currentY);

    // Dibujar título de la sección
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell($columnWidth, 7, mb_convert_encoding($title, 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

    // Dibujar encabezados para INICIO, HORA y FINALIZA
    $pdf->SetFont('Arial', 'B', 9);
    $headerWidths = [33, 27, 33];
    $headers = ['Inicio', 'Hora', 'Finaliza'];
    $pdf->SetX($xPosition);
    foreach ($headers as $i => $header) {
        $pdf->Cell($headerWidths[$i], 6, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    }
    $pdf->Ln();

    // Dibujar filas de datos para INICIO, HORA y FINALIZA
    $pdf->SetFont('Arial', '', 9);
    foreach ($data as $row) {
        $pdf->SetX($xPosition);
        foreach (array_slice($fields, 0, 3) as $i => $field) {
            $pdf->Cell($headerWidths[$i], 6, mb_convert_encoding($row[$field] ?? '-', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        }
        $pdf->Ln();

        // Dibujar fila para "Observación" con un espacio estático más alto
        $pdf->SetX($xPosition);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(33, 14, mb_convert_encoding('Observación:', 'ISO-8859-1', 'UTF-8'), 1, 0, 'L'); // Etiqueta "Observación"
        $pdf->SetFont('Arial', '', 9);
        $pdf->MultiCell($columnWidth - 33, 14, mb_convert_encoding($row[$fields[3]] ?? '-', 'ISO-8859-1', 'UTF-8'), 1, 'L'); // Contenido de "Observación"
    }

    // Ajustar posición para la siguiente sección
    if ($sectionIndex % 2 == 1) {
        $currentY += 50; // Mover abajo después de dos columnas
    }
    $sectionIndex++;
}

// Centrar todo en la página
$currentY += 40;
$centerX = $leftMargin + ($pageWidth - ($columnWidth * 2)) / 2;
$pdf->SetLeftMargin($centerX);
$pdf->SetRightMargin($centerX + ($columnWidth * 2));

// Salida del PDF
$pdf->Output('D', 'Hoja_Hospitalizacion.pdf');

?>