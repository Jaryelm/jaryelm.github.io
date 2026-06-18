<?php
session_start();
require '../../backend/fpdf/fpdf.php';
require '../../backend/bd/Conexion.php';

// Clase extendida para el Header y Footer personalizados
class PDFWithHeaderFooter extends FPDF
{
    public $dni; // Propiedad para almacenar el DNI del paciente

    function Header()
    {
        // Logo en la parte izquierda
        $this->setY(12);
        $this->setX(10);
        $this->Image('../../backend/img/factura_logo.png', 10, 4, 50);
    
        // Texto principal del header
        $this->SetFont('Arial', 'B', 12);
        $this->Text(75, 15, mb_convert_encoding('HOSPITAL MEDICASA S. D R.L.', 'ISO-8859-1', 'UTF-8'));
        $this->Text(75, 20, mb_convert_encoding('Tel: +504 2242-6271 - 2242-6272', 'ISO-8859-1', 'UTF-8'));
    
        // Centrar "COMAYAGUELA M.D.C"
        $this->SetFont('Arial', 'B', 12);
        $pageWidth = $this->GetPageWidth(); // Ancho de la página
        $text = mb_convert_encoding('COMAYAGUELA M.D.C', 'ISO-8859-1', 'UTF-8');
        $textWidth = $this->GetStringWidth($text); // Ancho del texto
        $xPosition = ($pageWidth - $textWidth) / 2; // Calcular la posición X para centrar
        $this->Text($xPosition, 25, $text);
    
        // Número de expediente en la derecha del header
        $this->SetFont('Arial', 'B', 10);
        $this->SetXY($this->GetPageWidth() - 70, 12); // Posicionar cerca del borde derecho
        $this->Cell(60, 6, mb_convert_encoding('No. Expediente', 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
        $this->SetXY($this->GetPageWidth() - 70, 18); // Posicionar debajo
        $this->Cell(60, 6, mb_convert_encoding('HM-' . $this->dni, 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');
    }

    function Footer()
    {
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
        $this->SetFont('Arial', 'B', 8);
        $this->SetY(-15);
        $this->Cell(95, -30, mb_convert_encoding('Página ' . $this->PageNo() . ' / {nb}', 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');
        $this->Cell(95, -30, date('d/m/Y | g:i:a'), 0, 1, 'R');
    }
}

// Función utilitaria para asegurar espacio antes de añadir contenido
function ensureSpace($pdf, $heightNeeded, $columns, $widths)
{
    if ($pdf->GetY() + $heightNeeded > $pdf->GetPageHeight() - 30) { // 30 = espacio del footer
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 10);

        // Redibujar encabezados
        foreach ($columns as $i => $col) {
            $pdf->Cell($widths[$i], 7, mb_convert_encoding($col, 'ISO-8859-1'), 1, 0, 'C');
        }
        $pdf->Ln();
    }
}

date_default_timezone_set('America/Tegucigalpa');
$id = $_GET['id'] ?? null;
if (!$id) {
    die('Error: ID del paciente no proporcionado.');
}

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
        patients.idpa = :id
    LIMIT 1
");
$stmtPatient->bindParam(':id', $id, PDO::PARAM_INT);
$stmtPatient->execute();
$patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die('Error: Paciente no encontrado.');
}

// Crear el PDF
$pdf = new PDFWithHeaderFooter('P', 'mm', 'Letter');
$pdf->dni = $patient['dni'] ?? 'NoDNI'; // Aquí asignas el DNI del paciente
$pdf->AliasNbPages();
$pdf->AddPage();

// Datos del paciente
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 15, mb_convert_encoding('DATOS DEL PACIENTE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

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
$startY = $pdf->GetY() + 2; 
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

$pdf->Ln(8);

// Función para agregar secciones de registros con ajuste dinámico
function addSection($pdf, $connect, $title, $query, $columns, $widthRatios)
{
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':idpa', $_GET['id'], PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ancho disponible para las tablas
    $pageWidth = $pdf->GetPageWidth();
    $margins = 20; // Márgenes izquierdo y derecho (10 mm cada uno)
    $availableWidth = $pageWidth - $margins;

    // Calcular anchos de columnas basados en las proporciones (widthRatios)
    $widths = array_map(function ($ratio) use ($availableWidth) {
        return $availableWidth * $ratio;
    }, $widthRatios);

    // Título de la sección
    if ($pdf->GetY() > $pdf->GetPageHeight() - 30) { // Si no hay espacio para el título y las primeras filas
        $pdf->AddPage();
    }
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 8, mb_convert_encoding(mb_strtoupper($title, 'UTF-8'), 'ISO-8859-1'), 0, 1, 'C');

    // Si no hay datos, mostrar mensaje y salir
    if (!$data) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 10, 'No hay datos disponibles para esta sección.', 0, 1, 'C');
        return;
    }

    // Dibujar encabezados
    $pdf->SetFont('Arial', 'B', 10);
    foreach (array_keys($columns) as $i => $col) {
        $pdf->Cell($widths[$i], 7, mb_convert_encoding($columns[$col], 'ISO-8859-1'), 1, 0, 'C');
    }
    $pdf->Ln();

    // Dibujar filas
    $pdf->SetFont('Arial', '', 9);
    foreach ($data as $row) {
        // Asegurar espacio antes de dibujar la fila
        ensureSpace($pdf, 10, array_keys($columns), $widths);

        foreach (array_keys($columns) as $i => $col) {
            $pdf->Cell($widths[$i], 6, mb_convert_encoding($row[$col] ?? '', 'ISO-8859-1'), 1);
        }
        $pdf->Ln();

        // Si se alcanza el pie de página, crear una nueva página
        if ($pdf->GetY() > $pdf->GetPageHeight() - 30) {
            $pdf->AddPage();
            $pdf->SetY(30); // Ajustar para no solapar el header
            $pdf->SetFont('Arial', 'B', 10);

            // Dibujar los encabezados de nuevo
            foreach (array_keys($columns) as $i => $col) {
                $pdf->Cell($widths[$i], 7, mb_convert_encoding($columns[$col], 'ISO-8859-1'), 1, 0, 'C');
            }
            $pdf->Ln(3);
        }
    }
}

// Llamadas a addSection
addSection(
    $pdf,
    $connect,
    'CONSULTAS',
    "SELECT fere AS Fecha, mtcl AS Consulta 
     FROM consult 
     WHERE idpa = :idpa 
     ORDER BY fere ASC",
    ['Fecha' => 'Fecha', 'Consulta' => 'Consulta'], // Mapear columnas
    [0.3, 0.7] // Proporciones: 30% para Fecha, 70% para Consulta
);

addSection(
    $pdf,
    $connect,
    'EXAMENES REALIZADOS',
    "SELECT fere AS Fecha, antecedentes_familiares AS Detalle 
     FROM genogram 
     WHERE idpa = :idpa 
     ORDER BY fere ASC",
    ['Fecha' => 'Fecha', 'Detalle' => 'Detalle'],
    [0.3, 0.7] // Proporciones: 30% para Fecha, 70% para Detalle
);

addSection(
    $pdf,
    $connect,
    'TRATAMIENTOS',
    "SELECT fere AS Fecha, nomtra AS Tratamiento 
     FROM treatment 
     WHERE idpa = :idpa 
     ORDER BY fere ASC",
    ['Fecha' => 'Fecha', 'Tratamiento' => 'Tratamiento'],
    [0.3, 0.7] // Proporciones: 30% para Fecha, 70% para Tratamiento
);

// Agregar Signos Vitales del Paciente
addSection(
  $pdf,
  $connect,
  'SIGNOS VITALES',
  "SELECT 
      fecha AS Fecha, 
      hora AS Hora, 
      processed_by AS 'Procesado Por', 
      reviews_by AS 'Revisado Por',
      blood_pressure AS 'PA', 
      map_pressure AS 'PAM', 
      temperature AS 'TEMP', 
      heart_rate AS 'FC', 
      respiratory_rate AS 'FR', 
      oxygen_saturation AS 'SAT',
      weight AS 'PESO',
      stature AS 'TALLA',
      glucose AS 'GLUCOSA'
   FROM signos_vitales 
   WHERE idpa = :idpa 
   ORDER BY fecha DESC, hora DESC",
  [
      'Fecha' => 'Fecha', 
      'Hora' => 'Hora', 
      'Procesado Por' => 'Realizado', 
      'Revisado Por' => 'Revisado',
      'PA' => 'PA', 
      'PAM' => 'PAM', 
      'TEMP' => 'TEMP', 
      'FC' => 'FC', 
      'FR' => 'FR', 
      'SAT' => 'SAT',
      'PESO' => 'PESO',
      'TALLA' => 'TALLA',
      'GLUCOSA' => 'GLU'
  ],
  [0.08, 0.08, 0.12, 0.12, 0.08, 0.06, 0.06, 0.06, 0.06, 0.06, 0.07, 0.07, 0.08] // Anchos relativos (total 1.0)
);
$pdf->AddPage();

// Establecer una posición inicial más baja
$pdf->SetY(30);

// Título de la sección principal
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, mb_convert_encoding('DATOS DE HOSPITALIZACIÓN', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(3); // Espaciado entre el título y la tabla

// Configuración de las secciones de hospitalización
$sections = [
    'CONTROL DE OXÍGENO' => ['Inicio' => 'oxigenoInicio', 'Finaliza' => 'oxigenoFinaliza', 'Observacion' => 'oxigenoObservacion'],
    'CONTROL DE COLCHÓN DE AIRE' => ['Inicio' => 'colchonInicio', 'Finaliza' => 'colchonFinaliza', 'Observacion' => 'colchonObservacion'],
    'CONTROL DE RESERVORIO' => ['Inicio' => 'reservorioInicio', 'Finaliza' => 'reservorioFinaliza', 'Observacion' => 'reservorioObservacion'],
    'MONITORIZACIÓN' => ['Inicio' => 'monitorInicio', 'Finaliza' => 'monitorFinaliza', 'Observacion' => 'monitorObservacion'],
    'NEBULIZACIONES' => ['Inicio' => 'nebulizacionInicio', 'Finaliza' => 'nebulizacionFinaliza', 'Observacion' => 'nebulizacionObservacion'],
    'SUCCIÓN' => ['Inicio' => 'succionInicio', 'Finaliza' => 'succionFinaliza', 'Observacion' => 'succionObservacion']
];

// Márgenes y dimensiones de tabla
$leftMargin = 10;
$pageWidth = $pdf->GetPageWidth();
$tableWidth = $pageWidth - (2 * $leftMargin);
$columnWidths = [$tableWidth * 0.25, $tableWidth * 0.25, $tableWidth * 0.5];

// Iterar sobre las secciones
foreach ($sections as $sectionTitle => $fields) {
    // Título de la sección
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(0, 7, mb_convert_encoding($sectionTitle, 'ISO-8859-1', 'UTF-8'), 1, 1, 'C', true);

    // Encabezados
    $pdf->SetFont('Arial', 'B', 9);
    $headers = array_keys($fields);
    foreach ($headers as $i => $header) {
        $pdf->Cell($columnWidths[$i], 6, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    }
    $pdf->Ln();

    // Consultar datos de la sección
    $stmt = $connect->prepare("
        SELECT 
            {$fields['Inicio']} AS Inicio, 
            {$fields['Finaliza']} AS Finaliza, 
            {$fields['Observacion']} AS Observacion 
        FROM hospitalizacion 
        WHERE idpa = :idpa 
        ORDER BY fere DESC
    ");
    $stmt->bindParam(':idpa', $id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Verificar si hay datos
    if (empty($rows)) {
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(0, 6, 'No hay datos disponibles.', 1, 1, 'C');
        $pdf->Ln(5);
        continue;
    }

    // Dibujar las filas
    $pdf->SetFont('Arial', '', 9);
    foreach ($rows as $row) {
        foreach ($headers as $i => $header) {
            $pdf->Cell($columnWidths[$i], 6, mb_convert_encoding($row[$header] ?? '-', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
        }
        $pdf->Ln();

        // Agregar nueva página si excede el límite
        if ($pdf->GetY() > 260) {
            $pdf->AddPage();
            $pdf->SetY(50); // Ajustar el margen superior de la nueva página
        }
    }

    // Espacio entre secciones
    $pdf->Ln(5);
}

// Agregar nueva página para la gráfica de temperatura
$pdf->AddPage();

// Establecer una posición inicial más baja
$pdf->SetY(30);

// Título de la sección
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0, 0); // Asegurar el texto en negro
$pdf->Cell(0, 10, mb_convert_encoding('GRÁFICA DE TEMPERATURA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(3);

// Configuración de la gráfica
$graphWidth = 185;
$graphHeight = 60;
$graphX = 20;
$graphY = $pdf->GetY() + 5;

$graphData = [];
$stmtGraph = $connect->prepare("SELECT temps, turno, created_at, procesado_por, glucap_temp, spo_2, frecuenciac, fresp_temp, tensiona FROM grafica_temperatura WHERE idpa = :idpa ORDER BY created_at ASC");
$stmtGraph->bindParam(':idpa', $id, PDO::PARAM_INT);
$stmtGraph->execute();
$graphData = $stmtGraph->fetchAll(PDO::FETCH_ASSOC);

if (empty($graphData)) {
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 10, mb_convert_encoding('No hay suficientes datos para generar la gráfica.', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
} else {
    $temps = array_map('floatval', array_column($graphData, 'temps'));
    $maxTemp = max($temps);
    $minTemp = min($temps);

    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Rect($graphX, $graphY, $graphWidth, $graphHeight);

    // Líneas horizontales y etiquetas de temperatura
    for ($i = 0; $i <= 10; $i++) {
        $lineY = $graphY + ($graphHeight / 10) * $i;
        $pdf->Line($graphX, $lineY, $graphX + $graphWidth, $lineY);
        $labelTemp = $maxTemp - (($maxTemp - $minTemp) / 10) * $i;
        $pdf->Text($graphX - 10, $lineY + 2, round($labelTemp, 1));
    }

    // Dibujar la línea de temperatura
    $pdf->SetDrawColor(0, 0, 0);
    for ($index = 0; $index < count($temps) - 1; $index++) {
        $startPointX = $graphX + (($graphWidth - 5) / (count($temps) - 1)) * $index;
        $startPointY = $graphY + $graphHeight - (($temps[$index] - $minTemp) / ($maxTemp - $minTemp)) * $graphHeight;

        $endPointX = $graphX + (($graphWidth - 5) / (count($temps) - 1)) * ($index + 1);
        $endPointY = $graphY + $graphHeight - (($temps[$index + 1] - $minTemp) / ($maxTemp - $minTemp)) * $graphHeight;

        $pdf->Line($startPointX, $startPointY, $endPointX, $endPointY);
    }

    // Dibujar puntos y etiquetas de turnos
    foreach ($temps as $index => $temp) {
        $newPointX = $graphX + (($graphWidth - 5) / (count($temps) - 1)) * $index;
        $newPointY = $graphY + $graphHeight - (($temp - $minTemp) / ($maxTemp - $minTemp)) * $graphHeight;

        $turnoLabel = $graphData[$index]['turno'];
        if ($turnoLabel === 'A') {
            $pdf->SetFillColor(0, 0, 0);
        } elseif ($turnoLabel === 'B') {
            $pdf->SetFillColor(0, 255, 0);
        } elseif ($turnoLabel === 'C') {
            $pdf->SetFillColor(255, 0, 0);
        } else {
            $pdf->SetFillColor(200, 200, 200);
        }

        $pdf->Rect($newPointX - 2.5, $newPointY - 2.5, 7, 7, 'F');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Text($newPointX - 1.5, $newPointY + 2, mb_convert_encoding($turnoLabel, 'ISO-8859-1', 'UTF-8'));
    }
}

$pdf->Ln(70);

// Título de la tabla de detalles
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(0, 0, 0); // Texto en negro
$pdf->Cell(0, 10, mb_convert_encoding('DETALLES DE TEMPERATURA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(3);

// Encabezados de la tabla
$columns = ['TEMPERATURA (°C)', 'TURNO', 'FECHA Y HORA', 'PROCESADO POR', 'GLUCOMETRIA', 'SP02', 'FC', 'FR', 'P/A'];
$columnWidths = [30, 15, 30, 30, 25, 15, 15, 15, 20];

$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetTextColor(0, 0, 0); // Asegurar color negro para encabezados
foreach ($columns as $i => $col) {
    $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding($col, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
}
$pdf->Ln();

// Dibujar filas de la tabla
$pdf->SetFont('Arial', '', 8);
$rowCount = 0;
$rowsPerPage = 10;
foreach ($graphData as $row) {
    if ($rowCount == $rowsPerPage) {
        $pdf->AddPage();
        $pdf->SetY(30); // Ajustar para mantener coherencia visual
        $pdf->SetFont('Arial', 'B', 8);
        foreach ($columns as $i => $col) {
            $pdf->Cell($columnWidths[$i], 7, mb_convert_encoding($col, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C', true);
        }
        $pdf->Ln();
        $rowCount = 0;
    }

    $pdf->Cell($columnWidths[0], 6, mb_convert_encoding($row['temps'], 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[1], 6, mb_convert_encoding($row['turno'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[2], 6, mb_convert_encoding($row['created_at'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[3], 6, mb_convert_encoding($row['procesado_por'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[4], 6, mb_convert_encoding($row['glucap_temp'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[5], 6, mb_convert_encoding($row['spo_2'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[6], 6, mb_convert_encoding($row['frecuenciac'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[7], 6, mb_convert_encoding($row['fresp_temp'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell($columnWidths[8], 6, mb_convert_encoding($row['tensiona'] ?? 'N/A', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Ln();
    $rowCount++;
}

// Recalcular pie de página para todas las páginas
$pdf->AliasNbPages();

// Salida del PDF
$pdf->Output('D', 'historial clinico.pdf');
?>
