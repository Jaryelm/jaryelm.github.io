<?php
require('../../backend/bd/Conexion.php');
require('../../backend/fpdf/fpdf.php');
session_start();

class PDFWithFooter extends FPDF
{
    function Header() {
        $this->Image('../../backend/img/factura_logo.png', 10, 5, 50);
        $this->Ln(15);
    }

    function Footer() {
        $this->SetY(-30);
        $this->Image('../../backend/img/footer_factura.png', 0, $this->GetY(), $this->GetPageWidth(), 30);
    }

    // ✅ Función para evitar que el contenido se sobreponga con el footer
    function CheckPageBreak($height) {
        $marginBottom = 40; // 🔹 Espacio seguro para no sobreponer con el footer
        if ($this->GetY() + $height + $marginBottom > $this->PageBreakTrigger) {
            $this->AddPage();
            $this->Header(); // 🔹 Asegura que el encabezado se agregue en cada página
        }
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

// 🔹 Obtener datos de gastos quirófano
$stmtGastos = $connect->prepare("SELECT * FROM gastos_quirofano WHERE idpa = :idpa ORDER BY created_at DESC");
$stmtGastos->bindParam(':idpa', $idpa, PDO::PARAM_INT);
$stmtGastos->execute();
$gastos = $stmtGastos->fetchAll(PDO::FETCH_ASSOC); // ✅ Ahora sí obtiene todos los registros

// ✅ Si no hay registros, mostrar mensaje en el PDF en lugar de un error fatal
if (!$gastos || count($gastos) === 0) {  
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 7, mb_convert_encoding('No hay registros de gastos quirúrgicos.', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
    $pdf->Output('D', 'Gastos_Quirurgicos.pdf');
    exit; // Detener la ejecución después de generar el PDF
}

// 🔹 Crear PDF
$pdf = new PDFWithFooter('P', 'mm', 'Letter');
$pdf->AddPage();

$pdf->Ln(12);
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


// 🔹 Datos generales
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('DATOS GENERALES', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);

// Encabezados de la tabla
$pdf->Cell(65, 7, mb_convert_encoding('Médico Anestesiólogo', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(65, 7, mb_convert_encoding('Cirujano Principal', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
$pdf->Cell(65, 7, mb_convert_encoding('Procesado por', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');

// Datos almacenados en la base de datos
foreach ($gastos as $gasto) {
    $pdf->CheckPageBreak(10); // 🔹 Verifica si hay espacio antes de agregar contenido
    $pdf->Cell(65, 7, mb_convert_encoding($gasto['medico_referente'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(65, 7, mb_convert_encoding($gasto['cirujano_principal'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(65, 7, mb_convert_encoding($gasto['procesado_por'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
}

$pdf->Ln(5);

// 🔹 Sección de Insumos y Material Descartable
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('INSUMOS Y MATERIAL DESCARTABLE', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 7, 'Insumo', 1, 0, 'C');
$pdf->Cell(95, 7, 'Cantidad', 1, 1, 'C');

foreach ($gastos as $gasto) {
    $pdf->CheckPageBreak(10); // 🔹 Verifica si hay espacio antes de agregar contenido
    
    $pdf->Cell(100, 7, mb_convert_encoding($gasto['insumo_material_descartable'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(95, 7, mb_convert_encoding($gasto['cantidad_material_descartable'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
}
$pdf->Ln(5);

// 🔹 Medicamentos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('MEDICAMENTOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 7, 'Insumo', 1, 0, 'C');
$pdf->Cell(95, 7, 'Cantidad', 1, 1, 'C');
foreach ($gastos as $gasto) {
    $pdf->CheckPageBreak(10); // 🔹 Verifica si hay espacio antes de agregar contenido

    $pdf->Cell(100, 7, mb_convert_encoding($gasto['insumo_medicamentos'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(95, 7, mb_convert_encoding($gasto['cantidad_medicamentos'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
}
$pdf->Ln(5);

// 🔹 Anestésicos
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('ANESTÉSICOS', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 7, 'Insumo', 1, 0, 'C');
$pdf->Cell(95, 7, 'Cantidad', 1, 1, 'C');
foreach ($gastos as $gasto) {
    $pdf->CheckPageBreak(10); // 🔹 Verifica si hay espacio antes de agregar contenido
    $pdf->Cell(100, 7, mb_convert_encoding($gasto['insumo_anestesicos'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(95, 7, mb_convert_encoding($gasto['cantidad_anestesicos'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
}
$pdf->Ln(5);

// 🔹 Equipo Médico Quirúrgico
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 7, mb_convert_encoding('EQUIPO MÉDICO QUIRÚRGICO', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(100, 7, 'Insumo', 1, 0, 'C');
$pdf->Cell(95, 7, 'Cantidad', 1, 1, 'C');
foreach ($gastos as $gasto) {
    $pdf->CheckPageBreak(10); // 🔹 Verifica si hay espacio antes de agregar contenido
    $pdf->Cell(100, 7, mb_convert_encoding($gasto['insumo_equipo_medico'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 0, 'C');
    $pdf->Cell(95, 7, mb_convert_encoding($gasto['cantidad_equipo_medico'] ?? 'No registrado', 'ISO-8859-1', 'UTF-8'), 1, 1, 'C');
}
$pdf->Ln(10);

// 🔹 Firma
$pdf->Ln(5);
$pdf->Cell(95, 15, "_____________________________", 0, 0, 'C');
$pdf->Cell(95, 15, "_____________________________", 0, 1, 'C');
$pdf->Cell(95, 7, mb_convert_encoding("Firma y Sello del Médico", 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Cell(95, 7, mb_convert_encoding("Firma de quien Recibe", 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

// 🔹 Generar PDF
$pdf->Output('D', 'Gastos Quirurgicos.pdf');
?>