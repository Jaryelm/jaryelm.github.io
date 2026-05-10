<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');
session_start();

class PDFWithHeaderFooter extends FPDF
{
    function Header()
    {
        // Agregar logo
        $this->Image('../../backend/img/factura_logo.png', 10, 5, 50);
        $this->Ln(20);
    }

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

// Obtener datos del período post operatorio
$stmtPostOp = $connect->prepare("SELECT * FROM anestesia WHERE idpa = :idpa ORDER BY created_at DESC LIMIT 1");
$stmtPostOp->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtPostOp->execute();
$postOp = $stmtPostOp->fetch(PDO::FETCH_ASSOC);

if (!$postOp) {
    die('Error: No se encontraron registros de recuperacion.');
}

// 🔹 Crear PDF
$pdf = new PDFWithHeaderFooter('P', 'mm', 'Letter');
$pdf->AddPage();

// Controlar el salto de página manualmente si el espacio es insuficiente
function checkPageBreak($pdf, $height = 10)
{
    if ($pdf->GetY() + $height > $pdf->GetPageHeight() - 30) { // Se restan 30 para dejar espacio al footer
        $pdf->AddPage();
    }
}


// Agregar logo e información del encabezado
$pdf->Image('../../backend/img/factura_logo.png', 10, 5, 50); 
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
$pdf->Ln(15);

// 🔹 Datos Generales de Anestesia
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('DATOS GENERALES DE ANESTESIA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$datosGenerales = [
    'Tiempo de Anestesia' => $postOp['tiempo_anestesia'] . ' min',
    'Observaciones' => $postOp['observaciones'],
];

foreach ($datosGenerales as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(115.5, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🔹 Variables Monitorizadas con Gráfica de Monitoreo
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('VARIABLES MONITORIZADAS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

// Definir posiciones iniciales y dimensiones
$x_start = 10;
$y_start = $pdf->GetY() + 5;
$col_width_variable = 20; // Ancho de la columna de variables
$col_width_value = 15; // Ancho de la columna de valores registrados
$col_width_time = 10.7; // Ancho de columnas de tiempos
$row_height = 7; // Altura de filas
$cycles = 5; // Cantidad de repeticiones del patrón 15, 30, 45
$total_columns = $cycles * 3; // Multiplicamos por la cantidad de repeticiones

// Encabezado con intervalos de tiempo
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell($col_width_variable, $row_height, mb_convert_encoding('Variables', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell($col_width_value, $row_height, mb_convert_encoding('Valores', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');

$time_intervals = [15, 30, 45]; // Secuencia base
for ($i = 0; $i < $cycles; $i++) {
    foreach ($time_intervals as $interval) {
        $pdf->Cell($col_width_time, $row_height, $interval, 1, 0, 'C');
    }
}
$pdf->Ln();

// Variables a monitorear con sus valores almacenados
$pdf->SetFont('Arial', '', 8);
$variablesMonitorizadas = [
    'T° (C)' => $postOp['temp'] ?? 'No registrado',
    'T.A.' => $postOp['tension_arterial'] ?? 'No registrado',
    'Pulso' => $postOp['pulso'] ?? 'No registrado',
    'F.R.' => $postOp['frecuencia_respiratoria'] ?? 'No registrado',
    'F.C.' => $postOp['frecuencia_cardiaca'] ?? 'No registrado'
];

// Lista completa de variables (algunas manuales)
$variables = [
    'T° (C)', 'T.A.', 'Pulso', 'F.R.', 'F.C.', 'R', 'IEQQUIR', 'ANEST', 'OPER', 'T.OPER', 'T.ANEST', 'P.REC', 'F.CF', 'TIEMPO'
];

foreach ($variables as $variable) {
    // Etiqueta de la variable
    $pdf->Cell($col_width_variable, $row_height, mb_convert_encoding($variable, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    
    // Valor registrado (si existe)
    $valorRegistrado = $variablesMonitorizadas[$variable] ?? '';
    $pdf->Cell($col_width_value, $row_height, mb_convert_encoding($valorRegistrado, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');

    // Rellenar el resto de la fila con celdas vacías para llenado manual
    for ($i = 0; $i < $total_columns; $i++) {
        $pdf->Cell($col_width_time, $row_height, '', 1, 0, 'C');
    }

    $pdf->Ln();
}
$pdf->Ln(5);

$pdf->AddPage();

// 🔹 Procedimientos Anestésicos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('PROCEDIMIENTOS ANESTÉSICOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$procedimientosAnestesicos = [
    'Diagnóstico' => $postOp['diagnostico'],
    'Operación Realizada' => $postOp['operacion'],
    'Método y Técnica Anestésica' => $postOp['metodo_anestesia'],
    'Mascarilla' => $postOp['mascarilla'] == 'Si' ? 'Sí' : 'No',
    'Cánula' => $postOp['canula'],
    'Tubo Endotraqueal' => !empty($postOp['tubo_endotraqueal']) ? $postOp['tubo_endotraqueal'] : 'No registrado',
    'Globo Inflable' => !empty($postOp['globo_inflable']) ? $postOp['globo_inflable'] : 'No registrado',
    'Complicaciones' => $postOp['complicaciones'] == 'Si' ? 'Sí' : 'No',
    'Sangre y Soluciones' => !empty($postOp['sangre_soluciones']) ? $postOp['sangre_soluciones'] : 'No registrado',
    'Fármacos y Soluciones Administradas' => $postOp['medicamentos'],
];

foreach ($procedimientosAnestesicos as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(115, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🔹 Casos Obstétricos (Si Aplica)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('CASOS OBSTÉTRICOS (SI APLICA)', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(195, 7, mb_convert_encoding($postOp['caso_obstetrico'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');

$pdf->Ln(5);

// 🔹 Datos del Recién Nacido (Si Aplica)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('DATOS DEL RECIÉN NACIDO (SI APLICA)', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$reciénNacido = [
    'Nombre' => $postOp['nombre_recien_nacido'],
    'Hora de Nacimiento' => $postOp['hora_nacimiento'],
    'Sexo' => $postOp['sexo'],
    'Peso (kg)' => $postOp['peso'],
    'Talla (cm)' => $postOp['talla'],
];

foreach ($reciénNacido as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(115, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🔹 Personal Médico Asignado
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('PERSONAL MÉDICO ASIGNADO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$personalMedico = [
    'Anestesiólogo' => $postOp['anestesiologo'],
    'Clave' => $postOp['clave'],
    'Cirujano' => $postOp['cirujano'],
    'Ayudante' => $postOp['ayudante'],
    'Instrumentista' => $postOp['instrumentista'],
    'Circulante' => $postOp['circulante'],
];

foreach ($personalMedico as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(115, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// Generar PDF
$pdf->Output('D', mb_convert_encoding('Anestesia.pdf', 'ISO-8859-1', 'UTF-8'));
?>
