<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');
session_start();

class PDFWithFooter extends FPDF
{
    function Header()
    {
        $this->Image('../../backend/img/factura_logo.png', 10, 5, 50);
        $this->Ln(25);
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
        patients.sex AS sexo,
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

// Obtener datos de la tabla
$stmtTransfusion = $connect->prepare("SELECT * FROM transfusion_hemoderivados WHERE idpa = :idpa");
$stmtTransfusion->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtTransfusion->execute();
$data = $stmtTransfusion->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die(mb_convert_encoding('Error: No se encontraron datos de la transfusión.', 'ISO-8859-1', 'UTF-8'));
}

$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

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

$pdf->Ln(10);

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 10, mb_convert_encoding('TRANSFUSIÓN DE HEMODERIVADOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln(5);

// 🩸 Tabla 1: Información General
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('INFORMACIÓN GENERAL', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$infoGeneral = [
    'Tipo RH' => $data['tipo_rh'],
    'Sexo' => $patient['sexo'],
    'Diagnóstico' => $data['diagnostico_hemoderivados'],
    'Médico Tratante' => $data['medico_tratante_hemoderivados'],
    'Enfermero Responsable' => $data['enfermero_responsable_hemoderivados'],
];

foreach ($infoGeneral as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(115, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🩸 Tabla 2: Componentes Transfundidos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('TIPO DE HEMODERIVADOS A TRANSFUNDIR', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$componentes = [
    'Sangre Completa' => $data['sangre_completa_hemoderivados'],
    'Glóbulos Rojos Empacados' => $data['globulos_rojos_hemoderivados'],
    'Plasma Normal' => $data['plasma_normal_hemoderivados'],
    'Plasma Fresco Cogelado' => $data['plasma_fresco_congelado_hemoderivados'],
    'Plaquetas' => $data['plaquetas_hemoderivados'],
    'Plaquetas Aféresis' => $data['plaquetas_aferesis_hemoderivados'],
    'Crio-Precipitado' => $data['crio_precipitado_hemoderivados'],
    'Otros' => $data['otros_hemoderivados'],
    'Cantidad Unidades' => $data['cantidad_unidades_hemoderivados'],
    'Hora de Inicio' => $data['hora_inicio_hemoderivados'],
    'Hora de Finalización' => $data['hora_finalizacion_hemoderivados'],
];

foreach ($componentes as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(115, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(19);

// 🩸 Tabla 3: Signos Vitales en Diferentes Etapas
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('ESTADO HEMODINAMICO DEL PACIENTE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$headers = ['ANTES DE TRANSFUNDIR', '30 MIN DE INICIAR', '1 HORA DE INICIAR', '2 HORAS DE INICIAR', '3 HORAS DE INICIAR'];
$keys = [
    ['pa_antes_transfundir', 'fc_antes_transfundir', 'ta_antes_transfundir', 'fr_antes_transfundir', 'spo2_antes_transfundir'],
    ['pa_30minutos_iniciar', 'fc_30minutos_iniciar', 'ta_30minutos_iniciar', 'fr_30minutos_iniciar', 'spo2_30minutos_iniciar'],
    ['pa_1hora_iniciar', 'fc_1hora_iniciar', 'ta_1hora_iniciar', 'fr_1hora_iniciar', 'spo2_1hora_iniciar'],
    ['pa_2horas_iniciar', 'fc_2horas_iniciar', 'ta_2horas_iniciar', 'fr_2horas_iniciar', 'spo2_2horas_iniciar'],
    ['pa_3horas_iniciar', 'fc_3horas_iniciar', 'ta_3horas_iniciar', 'fr_3horas_iniciar', 'spo2_3horas_iniciar']
];

// Primera fila: Encabezados
$pdf->SetFont('Arial', 'B', 6.5);
$pdf->Cell(38, 8, mb_convert_encoding('SIGNOS VITALES', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
foreach ($headers as $header) {
    $pdf->Cell(31.4, 8, mb_convert_encoding($header, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
}
$pdf->Ln();

// Segunda fila: Parámetros de los signos vitales
$pdf->SetFont('Arial', '', 10);
$metrics = ['PA', 'FC', 'TA', 'FR', 'SpO2'];
foreach ($metrics as $index => $metric) {
    $pdf->Cell(38, 8, mb_convert_encoding($metric, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    foreach ($keys as $values) {
        $pdf->Cell(31.4, 8, mb_convert_encoding($data[$values[$index]] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    }
    $pdf->Ln();
}
$pdf->Ln(5);

// 🩸 Tabla 4: Reacciones durante la Transfusión
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('REACCIONES DURANTE LA TRANSFUSIÓN:', 'ISO-8859-1', 'UTF-8'), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 8, mb_convert_encoding($data['transfusion_reacciones'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'));

$pdf->Output('D', mb_convert_encoding('Transfusión Hemoderivados.pdf', 'ISO-8859-1', 'UTF-8'));
?>
