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
$stmtPostOp = $connect->prepare("SELECT * FROM recuperacion WHERE idpa = :idpa ORDER BY created_at DESC LIMIT 1");
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

// 🔹 Datos Generales
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('DATOS GENERALES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$datosGenerales = [
    'Diagnóstico' => $postOp['diagnostico'],
    'Cirugía Realizada' => $postOp['cirujano_realizada'],
    'Cirujano Principal' => $postOp['cirujano_principal'],
    'Anestesiólogo' => $postOp['anestesista'],
    'Tipo de Anestesia' => $postOp['tipo_anestesia'],
    'Fecha' => $postOp['fecha'],
    'Hora de Inicio' => $postOp['hora_inicio_cirugia'],
    'Hora de Fin' => $postOp['hora_fin_cirugia'],
];

foreach ($datosGenerales as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(116, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🔹 Cuidados Post Operatorios Inmediatos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('CUIDADOS POST OPERATORIOS INMEDIATOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$cuidados = [
    'Reflejos' => $postOp['reflejos'],
    'Cánula Endotraqueal' => $postOp['canula_endotraqueal'],
    'Oxígeno' => $postOp['oxigeno'],
    'Sonda Foley' => $postOp['sonda_foley'],
    'Sonda NSG' => $postOp['sonda_nsg'],
    'CVP' => $postOp['cvp'],
    'CVC' => $postOp['cvc'],
    'Drenos' => $postOp['drenos'],
    'Tipo' => $postOp['tipo_cuidado'],
];

foreach ($cuidados as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(116, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(20);

$pdf->Ln(5);

// 🔹 Signos Vitales
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('SIGNOS VITALES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

// Verificar si $postOp tiene múltiples registros o solo uno
$signosVitalesDatos = [];
if (isset($postOp[0])) { // Si es un array con múltiples registros
    foreach ($postOp as $registro) {
        $signosVitalesDatos[] = [
            'hora' => $registro['hora_signos'] ?? 'No Registrado',
            'pa'   => $registro['pa_signos'] ?? 'No Registrado',
            'fc'   => $registro['fc_signos'] ?? 'No Registrado',
            'fr'   => $registro['fr_signos'] ?? 'No Registrado',
            'ta'   => $registro['ta_signos'] ?? 'No Registrado',
            'spo2' => $registro['spo2_signos'] ?? 'No Registrado',
        ];
    }
} else { // Si solo hay un registro o ninguno
    $signosVitalesDatos[] = [
        'hora' => $postOp['hora_signos'] ?? 'No Registrado',
        'pa'   => $postOp['pa_signos'] ?? 'No Registrado',
        'fc'   => $postOp['fc_signos'] ?? 'No Registrado',
        'fr'   => $postOp['fr_signos'] ?? 'No Registrado',
        'ta'   => $postOp['ta_signos'] ?? 'No Registrado',
        'spo2' => $postOp['spo2_signos'] ?? 'No Registrado',
    ];
}

// **Generar encabezado con los tiempos siempre visibles**
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(40, 7, mb_convert_encoding('Parámetro', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');

// Asegurar que haya **siempre** columnas de 15, 30, 45 minutos
$tiempos = ['15', '30', '45']; 

foreach ($tiempos as $tiempo) {
    $pdf->Cell(52, 7, mb_convert_encoding($tiempo, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
}
$pdf->Ln();

// **Variables a mostrar en la tabla**
$variablesSignos = [
    'Hora'  => 'hora',
    'PA'    => 'pa',
    'FC'    => 'fc',
    'FR'    => 'fr',
    'TA'    => 'ta',
    'SpO2'  => 'spo2'
];

// **Dibujar la tabla asegurando siempre los tiempos 15, 30, 45 min**
$pdf->SetFont('Arial', '', 8);
foreach ($variablesSignos as $nombreVariable => $clave) {
    $pdf->Cell(40, 7, mb_convert_encoding($nombreVariable, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');

    // **Llenar los valores de la tabla asegurando siempre 3 columnas**
    for ($i = 0; $i < 3; $i++) {
        $valor = isset($signosVitalesDatos[$i][$clave]) ? $signosVitalesDatos[$i][$clave] : 'No Registrado';
        $pdf->Cell(52, 7, mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    }

    $pdf->Ln();
}

$pdf->Ln(5);

// 🔹 Medicamentos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('MEDICAMENTOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$medicamentos = [
    'Medicamento' => $postOp['medicamento'],
    'Dosis' => $postOp['dosis'],
    'Vía' => $postOp['via'],
    'Hora' => $postOp['hora_medicamento'],
];

foreach ($medicamentos as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(116, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🔹 Control de Líquidos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('CONTROL DE LÍQUIDOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

$controlLiquidos = [
    'Ingesta Oral' => $postOp['ingestas_orales'],
    'Ingesta IV' => $postOp['ingestas_iv'],
    'Orina' => $postOp['excretas_orina'],
    'Vómitos' => $postOp['excretas_vomitos'],
    'Succión' => $postOp['excretas_succion'],
];

foreach ($controlLiquidos as $label => $valor) {
    $pdf->Cell(80, 7, mb_convert_encoding("$label:", 'ISO-8859-1', 'UTF-8'), 1, 0, 'L');
    $pdf->Cell(116, 7, mb_convert_encoding($valor ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'L');
}
$pdf->Ln(5);

// 🔹 Observaciones de Enfermería
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, mb_convert_encoding('OBSERVACIONES DE ENFERMERÍA', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 8, mb_convert_encoding($postOp['observaciones'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'));
$pdf->Ln(5);

// Generar PDF
$pdf->Output('D', mb_convert_encoding('Recuperación.pdf', 'ISO-8859-1', 'UTF-8'));
?>
